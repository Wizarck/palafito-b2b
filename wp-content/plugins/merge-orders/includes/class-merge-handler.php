<?php

namespace Vibe\Merge_Orders;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

use InvalidArgumentException;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;

/**
 * Class Merge_Handler
 *
 * @package Vibe\Merge_Orders
 */
class Merge_Handler {

	/**
	 * The valid strategies for merging line items
	 *
	 * 'default'  - Merge line items for the same product only if they share the same unit price
	 * 'merge'    - Always merge line items for the same product
	 * 'separate' - Never merge line items for the same product
	 */
	const PRODUCT_LINE_ITEM_STRATEGIES = array(
		'default',
		'merge',
		'separate',
	);

	/**
	 * The order to be merged into
	 *
	 * @var WC_Order
	 */
	protected $target;

	/**
	 * The orders to be merged into the target order
	 *
	 * @var WC_Order[]
	 */
	protected $orders;

	/**
	 * Creates an instance of Merge_Handler
	 *
	 * @param WC_Order $target The order to be merged into
	 * @param WC_Order[] $orders The orders to be merged into the target order
	 *
	 * @throws InvalidArgumentException If $target is not an instance of WC_Order or $orders is not an array of
	 *                                  WC_Order instances.
	 */
	public function __construct( WC_Order $target, array $orders ) {
		if ( ! $target instanceof WC_Order ) {
			throw new InvalidArgumentException( '$target must be an instance of WC_Order' );
		}

		if ( is_array( $orders ) ) {
			$orders = array_filter( $orders, function ( $order ) {
				return $order instanceof WC_Order;
			} );
		} else {
			$orders = array();
		}

		if ( empty( $orders ) ) {
			throw new InvalidArgumentException( '$orders must be an non-empty array of WC_Order instances' );
		}

		$this->target = $target;
		$this->orders = $orders;
	}

	/**
	 * Returns the strategy to use for merging line items
	 *
	 * @return string The strategy to be used to merge the line items
	 *
	 * @uses Merge_Handler::PRODUCT_LINE_ITEM_STRATEGIES As the valid strategies for merging line items
	 */
	public function get_product_line_item_strategy() {
		/**
		 * Allow changing the product line item strategy
		 *
		 * @param string $strategy The strategy to be used to merge the line items
		 * @param WC_Order $target The order to be merged into
		 * @param WC_Order[] $orders The orders to be merged into the target order
		 *
		 * @since 1.0.0
		 */
		$strategy = apply_filters( Merge_Orders::hook_prefix( 'product_item_strategy' ), 'default', $this->target, $this->orders );

		return in_array( $strategy, self::PRODUCT_LINE_ITEM_STRATEGIES )
			? $strategy
			: 'default';
	}

	/**
	 * Merges all source orders in to the target order
	 *
	 * @return bool True if the merge was successful, false otherwise
	 *
	 * @uses Merge_Handler::$target As the order to be merged into
	 * @uses Merge_Handler::$orders As the orders to be merged into the target order
	 */
	public function merge() {
		Merge_Orders::logger()->log( sprintf( 'Starting merge of %1$s into target order %2$s', implode( ', ', wp_list_pluck( $this->orders, 'id' ) ), $this->target->get_id() ) );

		/**
		 * Fired before orders have started merging
		 *
		 * @param WC_Order $target The order to be merged into
		 * @param WC_Order[] $orders The orders to be merged into the target order
		 *
		 * @since 1.0.0
		 */
		do_action( Merge_Orders::hook_prefix( 'before_orders_merged' ), $this->target, $this->orders );

		$item_strategy = $this->get_product_line_item_strategy();

		Merge_Orders::logger()->log( "Using strategy '{$item_strategy}'" );

		try {
			foreach ( $this->orders as &$order ) {
				$this->merge_orders( $order, $this->target, $item_strategy );

				/**
				 * Allow changing the status orders that have been merged are assigned
				 *
				 * @param string $status Status to change the order to
				 * @param WC_Order $order The order that has been merged and will have its status changed
				 * @param WC_Order $target The order that was merged into
				 *
				 * @since 1.0.0
				 */
				$status = apply_filters(
					Merge_Orders::hook_prefix( 'merged_order_status' ),
					'merged',
					$order,
					$this->target
				);

				$order->set_status( $status );
				$order->recalculate_coupons();
				$order->calculate_shipping();
				$order->update_taxes();
				$order->calculate_totals( false );
				$order->save();
				$order->save_meta_data();
			}

			$this->target->calculate_shipping();

			// --- INICIO LÓGICA PERSONALIZADA ---
			$this->process_shipping_lines();
			$this->process_invoice_notes();
			// --- FIN LÓGICA PERSONALIZADA ---

			// Update the tax totals without recalculating tax items
			$this->target->update_taxes();
			$this->target->calculate_totals(false);
			$this->target->add_meta_data( Orders::MERGED_INTO_KEY, 'yes' );
			$this->target->save_meta_data();

			$this->add_merging_notes();

			// Nota interna de merge
			$merged_numbers = array_map(function($order) { return $order->get_order_number(); }, $this->orders);
			$target_number = $this->target->get_order_number();
			$note = sprintf('Merge realizado en el pedido %s de los pedidos: %s', $target_number, implode(', ', $merged_numbers));
			$this->target->add_order_note($note, 0, true);

			Merge_Orders::logger()->log( 'Orders merged successfully' );

			/**
			 * Fired after orders have finished merging and merging notes have been added
			 *
			 * @param WC_Order $target
			 * @param WC_Order $target The order that was merged into
			 * @param WC_Order[] $orders The orders that were into the target order
			 *
			 * @since 1.0.0
			 */
			do_action( Merge_Orders::hook_prefix( 'after_orders_merged' ), $this->target, $this->orders );

			return true;
		} catch (\Exception $e) {
			// Rollback: dejar todo igual y añadir nota de error
			$error_note = 'Error al intentar mergear pedidos: ' . $e->getMessage();
			$this->target->add_order_note($error_note, 0, true);
			throw $e;
		}
	}

	/**
	 * Merges two orders together using the given item strategy for merging line items
	 *
	 * @param WC_Order $source_order The order to be merged into the target order
	 * @param WC_Order $target_order The order the source order should be merged into
	 * @param string $item_strategy The strategy to use for merging line items
	 */
	protected function merge_orders( WC_Order $source_order, WC_Order $target_order, $item_strategy = 'default' ) {
		$attempt_item_merge = 'separate' !== $item_strategy;

		if ( $attempt_item_merge ) {
			$target_items = array();

			foreach ( $this->target->get_items( 'line_item' ) as $target_item ) {
				if ( $target_item instanceof WC_Order_Item_Product && $target_item->get_product_id() ) {
					$target_items[ $target_item->get_product_id() ][] = $target_item;
				}
			}
		}

		$item_types = apply_filters( Merge_Orders::hook_prefix( 'item_types' ), array( 'line_item', 'tax', 'shipping', 'fee', 'coupon' ), $source_order, $target_order );

		foreach ( $source_order->get_items( $item_types ) as $line_item ) {
			/**
			 * Allow the line item to be modified before anything is done with it
			 *
			 * @param WC_Order_Item $line_item    The item about to be merged
			 * @param WC_Order      $source_order The order the line item is from
			 * @param WC_Order      $target       The order the line item is being merged into
			 *
			 * @since 1.0.0
			 */
			$line_item = apply_filters( Merge_Orders::hook_prefix('pre_merge_line_item' ), $line_item, $source_order, $target_order );

			$merged_item = false;

			// Attempt to merge product line items
			if ( $attempt_item_merge && $line_item instanceof WC_Order_Item_Product && $line_item->get_product_id() ) {
				$target_item = $this->find_matching_target_item( $line_item, $target_items, $item_strategy );

				if ( $target_item ) {
					Merge_Orders::logger()->log( "Combining source line item {$line_item->get_id()} with target line item {$target_item->get_id()}" );

					// Merge into the target item
					$this->merge_line_items( $line_item, $target_item );
					$merged_item = true;
				}
			}

			// If not been able to merge with another item then add it separately
			if ( ! $merged_item ) {
				$reduced_stock = $line_item->get_meta( '_reduced_stock' );
				$target_item = clone $line_item;
				$target_item->set_id( 0 );

				if ( $reduced_stock ) {
					// Retain stock level reduction if there was one
					$target_item->add_meta_data( '_reduced_stock', $reduced_stock );
				}

				Merge_Orders::logger()->log( "Adding copy of line item {$line_item->get_id()} to target order" );
				$target_order->add_item( $target_item );
			}

			/**
			 * Allow the removal of source line items to be stopped
			 *
			 * @param bool          $var           Whether to remove line item from source order, defaults to true
			 * @param WC_Order_Item $line_item     The item that has been merged and is about to be removed from source
			 * @param WC_Order      $source_order  The order the line item is from
			 * @param WC_Order      $target_order  The order the line item has been merged into
			 * @param string        $item_strategy The strategy that was used when merging the line item
			 *
			 * @since 1.1.0
			 */
			if ( apply_filters( Merge_Orders::hook_prefix( 'remove_source_items' ), true, $line_item, $source_order, $target_order, $item_strategy ) ) {
				Merge_Orders::logger()->log( "Removing line item {$line_item->get_id()} from source order {$source_order->get_id()}" );

				// Should finally be safe to remove the item from the source order
				$source_order->remove_item( $line_item->get_id() );
				$source_order->save();
			}
		}

		if ( apply_filters( Merge_Orders::hook_prefix( 'merge_notes' ), false, $source_order, $target_order ) ) {
			$this->merge_order_notes($source_order, $target_order);
		}
	}

	/**
	 * Merges the order numbers of one order into another
	 *
	 * @param WC_Order $source_order The order to be merged into the target order
	 * @param WC_Order $target_order The order the source order should be merged into
	 */
	protected function merge_order_notes( WC_Order $source_order, WC_Order $target_order ) {
		$notes = wc_get_order_notes( array( 'order_id' => $source_order->get_id() ) );

		foreach ( $notes as $note ) {
			$commentData = array(
				'comment_ID'      => $note->id,
				'comment_post_ID' => $target_order->get_id()
			);

			wp_update_comment( $commentData );
		}
	}

	/**
	 * Finds any line item in array of target items which matches the source item for merging
	 *
	 * @param WC_Order_Item_Product $source_item
	 * @param array $target_items
	 * @param string $item_strategy
	 *
	 * @return WC_Order_Item_Product|null
	 */
	protected function find_matching_target_item( WC_Order_Item_Product $source_item, array $target_items, $item_strategy = 'default' ) {
		$check_unit_price = 'default' === $item_strategy;
		$match = null;

		// Filter the target items to those for the right product
		$product_id = $source_item->get_product_id();
		$target_items = isset( $target_items[ $product_id ] ) ? $target_items[ $product_id ] : array();

		/**
		 * Looking through potential target items for a match
		 *
		 * @var WC_Order_Item_Product $target_item The potential target item
		 */
		foreach ( $target_items as $target_item ) {
			if ( $target_item->get_variation_id() === $source_item->get_variation_id() ) {

				if ( $check_unit_price ) {
					$source_qty = $source_item->get_quantity();
					$target_qty = $target_item->get_quantity();

					$subtotal_match = ( $target_item->get_subtotal() / $target_qty ) === ( $source_item->get_subtotal() / $source_qty );
					$total_match    = ( $target_item->get_total() / $target_qty ) === ( $source_item->get_total() / $source_qty );

					if ( $subtotal_match && $total_match ) {
						$match = $target_item;
						break;
					}
				} else {
					$match = $target_item;
					break;
				}
			}
		}

		return $match;
	}

	/**
	 * Merges the given source line item into the target item by increasing quantity and totals
	 *
	 * @param WC_Order_Item_Product $source_item The line item to merge into the target item
	 * @param WC_Order_Item_Product $target_item The line item to merge the source item into
	 */
	protected function merge_line_items( WC_Order_Item_Product $source_item, WC_Order_Item_Product $target_item ) {
		$reduced_stock = $source_item->get_meta( '_reduced_stock' );

		// Merge stock reduction levels if the source item had one set
		if ( $reduced_stock ) {
			$target_reduced_stock = $target_item->get_meta( '_reduced_stock' );
			$target_reduced_stock = $target_reduced_stock ? $target_reduced_stock : 0;
			$target_item->update_meta_data( '_reduced_stock', $target_reduced_stock + $reduced_stock );
		}

		// Update $target_item quantity and totals to merge
		$target_item->set_quantity( $target_item->get_quantity() + $source_item->get_quantity() );

		$this->merge_item_taxes( $source_item, $target_item );

		$total        = (string) ( (float) $target_item->get_total() + (float) $source_item->get_total() );
		$subtotal     = (string) ( (float) $target_item->get_subtotal() + (float) $source_item->get_subtotal() );

		$target_item->set_total( $total );
		$target_item->set_subtotal( $subtotal );

		$target_item->save();
		$target_item->save_meta_data();
	}

	/**
	 * Merges the different taxes for two line items and assigns the combined taxes to the target item
	 *
	 * @param WC_Order_Item_Product $source_item The line item whose taxes should be merged into the target item
	 * @param WC_Order_Item_Product $target_item The line item to merge the source item's taxes into
	 */
	protected function merge_item_taxes( WC_Order_Item_Product $source_item, WC_Order_Item_Product $target_item ) {
		$source_taxes = $source_item->get_taxes('edit');
		$target_taxes = $target_item->get_taxes('edit');

		$combined_taxes = array();
		$tax_total_names = array_keys( $source_taxes + $target_taxes );

		foreach ($tax_total_names as $tax_total_name) {
			$source_tax_totals = isset( $source_taxes[ $tax_total_name ] ) ? $source_taxes[ $tax_total_name ] : array();
			$target_tax_totals = isset( $target_taxes[ $tax_total_name ] ) ? $target_taxes[ $tax_total_name ] : array();
			$tax_rate_ids = array_keys( $source_tax_totals + $target_tax_totals );
			sort( $tax_rate_ids );

			foreach ($tax_rate_ids as $tax_rate_id) {
				$source_tax = isset( $source_tax_totals[ $tax_rate_id ] ) ? $source_tax_totals[ $tax_rate_id ] : 0;
				$target_tax = isset( $target_tax_totals[ $tax_rate_id ] ) ? $target_tax_totals[ $tax_rate_id ] : 0;
				$combined_taxes[ $tax_total_name ][ $tax_rate_id ] = $source_tax + $target_tax;
			}
		}

		$target_item->set_taxes( $combined_taxes );
	}

	/**
	 * Add order notes the target and merged orders to record a merge
	 *
	 * @uses Merge_Handler::$target As the order that was merged into
	 * @uses Merge_Handler::$orders As the orders that were merged the target order
	 */
	public function add_merging_notes() {
		/**
		 * Allow disabling merging notes
		 *
		 * @param bool $enabled Whether or not adding merging notes is enabled
		 *
		 * @since 1.0.0
		 */
		if ( ! apply_filters( Merge_Orders::hook_prefix( 'add_merging_notes' ), true ) ) {
			return;
		}

		$message_for_merged = sprintf(
		/* translators: 1: Link to the order 2: The order number of the order merged into */
			__( 'Order merged into <a href="%1$s">#%2$s</a>.', 'merge-orders' ),
			get_edit_post_link( $this->target->get_id() ),
			$this->target->get_order_number()
		);

		foreach ( $this->orders as $merged_order ) {
			$message = sprintf(
			/* translators: 1: Link to the order merged 2: The order number of the order merged */
				__( 'Order <a href="%1$s">#%2$s</a> merged.', 'merge-orders' ),
				get_edit_post_link( $merged_order->get_id() ),
				$merged_order->get_order_number()
			);

			$this->target->add_order_note( $message, 0, false );
			$merged_order->add_order_note( $message_for_merged, 0, false );
		}
	}

	/**
	 * Procesa las líneas de envío según la lógica B2B: elimina duplicados de envío gratuito o los elimina si hay envíos de pago.
	 */
	private function process_shipping_lines() {
		$shipping_items = $this->target->get_items('shipping');
		$paid_shipping = [];
		$free_shipping = [];

		foreach (
			$shipping_items as $item_id => $item
		) {
			$data = $item->get_data();
			$method_id = isset($data['method_id']) ? strtolower($data['method_id']) : '';
			$method_title = strtolower($item->get_name());
			$total = isset($data['total']) ? floatval($data['total']) : 0.0;
			// Considerar envío gratuito si el método contiene "free" o el total es 0
			if (strpos($method_id, 'free') !== false || strpos($method_title, 'free') !== false || $total == 0.0) {
				$free_shipping[$item_id] = $item;
			} else {
				$paid_shipping[$item_id] = $item;
			}
		}

		if (count($paid_shipping) > 0) {
			// Si hay envíos de pago, eliminar todos los gratuitos
			foreach ($free_shipping as $item_id => $item) {
				$this->target->remove_item($item_id);
			}
		} elseif (count($free_shipping) > 1) {
			// Si solo hay gratuitos, dejar solo uno
			$first = true;
			foreach ($free_shipping as $item_id => $item) {
				if ($first) {
					$first = false;
					continue;
				}
				$this->target->remove_item($item_id);
			}
		}
		$this->target->save();
	}

	/**
	 * Procesa las notas de cliente de los pedidos originales y las concatena en la nota de factura del pedido final.
	 */
	private function process_invoice_notes() {
		$feria = [];
		$obrador = [];
		$otras = [];
		$all_orders = array_merge([$this->target], $this->orders);
		$notas_a_procesar = [];
		foreach ($all_orders as $order) {
			$factura = $order->get_meta('_wcpdf_invoice_notes');
			if ($factura) {
				$notas_a_procesar[] = $factura;
			} elseif ($order->get_customer_note()) {
				$notas_a_procesar[] = $order->get_customer_note();
			}
		}
		$c_all = [];
		foreach ($notas_a_procesar as $nota) {
			$lines = preg_split('/\r?\n/', $nota);
			$last_c = null;
			for ($i = 0; $i < count($lines); $i++) {
				$line = trim($lines[$i]);
				if (preg_match('/C\d{5}/i', $line, $match)) {
					$last_c = strtoupper($match[0]);
					$c_all[] = $last_c;
				}
				// Detectar bloque Feria
				if ($this->is_feria($this->normalize_note($line)) && $last_c) {
					// Buscar la primera línea no vacía después de "Feria"
					$feria_name = '';
					for ($j = $i + 1; $j < count($lines); $j++) {
						$next_line = trim($lines[$j]);
						if ($next_line !== '') {
							$feria_name = $next_line;
							break;
						}
					}
					if ($feria_name) {
						$feria[] = $last_c . ' - ' . $feria_name;
					} else {
						$feria[] = $last_c;
					}
					$last_c = null;
				}
				// Detectar bloque Obrador
				if ($this->is_obrador($this->normalize_note($line)) && $last_c) {
					$obrador[] = $last_c;
					$last_c = null;
				}
			}
			// Si hay un CXXXXX que no fue asociado a Feria/Obrador
			if ($last_c) {
				$otras[] = $last_c;
			}
		}
		// Ordenar los CXXXXX ascendentemente
		sort($feria, SORT_NATURAL);
		sort($obrador, SORT_NATURAL);
		sort($otras, SORT_NATURAL);
		$lines = [];
		if ($feria) {
			if (count($feria) == 1) {
				$lines[] = 'Feria: ' . $feria[0];
			} else {
				$lines[] = "Feria:\n" . implode("\n", $feria);
			}
		}
		if ($obrador) {
			$lines[] = 'Obrador: ' . implode(', ', $obrador);
		}
		if ($otras) {
			$lines[] = implode(', ', $otras);
		}
		$final_note = implode("\n", $lines);
		if ($final_note) {
			$this->target->update_meta_data('_wcpdf_invoice_notes', $final_note);
			$this->target->save_meta_data();
		}
		// Duplicados: si hay CXXXXX repetidos en cualquier bloque, añadir nota interna
		$dupes = array_unique(array_diff_assoc($c_all, array_unique($c_all)));
		if (!empty($dupes)) {
			$msg = 'Atención: Se detectaron CXXXXX duplicados en la nota de factura durante el merge: ' . implode(', ', $dupes);
			$this->target->add_order_note($msg, 0, true);
		}
	}

	/**
	 * Normaliza una nota para búsqueda flexible (case-insensitive, sin tildes, sin espacios extra, sin símbolos raros).
	 */
	private function normalize_note($note) {
		$note = strtolower($note);
		$note = preg_replace('/[áàäâ]/u', 'a', $note);
		$note = preg_replace('/[éèëê]/u', 'e', $note);
		$note = preg_replace('/[íìïî]/u', 'i', $note);
		$note = preg_replace('/[óòöô]/u', 'o', $note);
		$note = preg_replace('/[úùüû]/u', 'u', $note);
		$note = preg_replace('/[^a-z0-9 c]/u', '', $note); // solo letras, números, espacios y c
		$note = preg_replace('/\s+/', ' ', $note);
		$note = trim($note);
		return $note;
	}
	private function is_feria($note) {
		return preg_match('/feria\b/', $note);
	}
	private function is_obrador($note) {
		return preg_match('/obrador\b/', $note);
	}
}
