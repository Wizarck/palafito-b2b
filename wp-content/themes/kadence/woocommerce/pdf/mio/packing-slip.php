<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>

<table class="head container">
	<tr>
		<td class="header">
		<?php
			if ( $this->has_header_logo() ) {
				do_action( 'wpo_wcpdf_before_shop_logo', $this->get_type(), $this->order );
				$this->header_logo();
				do_action( 'wpo_wcpdf_after_shop_logo', $this->get_type(), $this->order );
			} else {
				$this->title();
			}
		?>
		</td>
		<td class="shop-info">
			<?php do_action( 'wpo_wcpdf_before_shop_name', $this->get_type(), $this->order ); ?>
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<?php do_action( 'wpo_wcpdf_after_shop_name', $this->get_type(), $this->order ); ?>
			<?php do_action( 'wpo_wcpdf_before_shop_address', $this->get_type(), $this->order ); ?>
			<div class="shop-address">
			<?php
				// Dirección de empresa personalizada (sin nombre de empresa)
				$shop_nif = get_option('palafito_shop_nif', 'B87654321');
				$shop_address = get_option('woocommerce_store_address');
				$shop_address2 = get_option('woocommerce_store_address_2');
				$shop_postcode = get_option('woocommerce_store_postcode');
				$shop_city = get_option('woocommerce_store_city');
				$shop_country_code = get_option('woocommerce_default_country');
				$shop_country_parts = explode(':', $shop_country_code);
				$shop_country_key = $shop_country_parts[0];
				$shop_country = WC()->countries->countries[$shop_country_key] ?? $shop_country_key;
				$shop_email = 'hola@palafitofood.com';
				$lines = [];
				$lines[] = 'NIF: ' . $shop_nif;
				$lines[] = $shop_address . ($shop_address2 ? ', ' . $shop_address2 : '');
				$lines[] = trim($shop_postcode . ' ' . $shop_city . ' - ' . $shop_country);
				$lines[] = 'Email: ' . $shop_email;
				echo implode("<br>", array_filter($lines));
			?>
			</div>
			<?php do_action( 'wpo_wcpdf_after_shop_address', $this->get_type(), $this->order ); ?>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>

<?php if ( $this->has_header_logo() ) : ?>
	<h1 class="document-type-label"><?php $this->title(); ?></h1>
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

<table class="order-data-addresses">
	<tr>
		<td class="address billing-address">
			<h3><?php $this->billing_address_title(); ?></h3>
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
			<?php
				// Dirección de facturación personalizada
				$user_id = $this->order->get_customer_id();
				$display_name = '';
				if ($user_id) {
					$user = get_userdata($user_id);
					if ($user) {
						$display_name = $user->display_name;
					}
				}
				$nif = get_post_meta($this->order->get_id(), '_billing_rfc', true);
				$address = $this->order->get_billing_address_1();
				$address2 = $this->order->get_billing_address_2();
				$cp = $this->order->get_billing_postcode();
				$city = $this->order->get_billing_city();
				$country = $this->order->get_billing_country();
				$country_name = WC()->countries->countries[$country] ?? $country;
				$phone = $this->order->get_billing_phone();
				$lines = [];
				if ($display_name) $lines[] = $display_name;
				if ($nif) $lines[] = 'NIF: ' . $nif;
				if ($address) $lines[] = $address . ($address2 ? ', ' . $address2 : '');
				$lines[] = trim($cp . ' ' . $city . ' - ' . $country_name);
				if ($phone && isset( $this->settings['display_phone'] ) ) $lines[] = 'Teléfono: ' . $phone;
				echo implode("<br>", array_filter($lines));
			?>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php endif; ?>
		</td>
		<td class="address shipping-address">
			<?php if ( $this->show_shipping_address() ) : ?>
				<h3><?php $this->shipping_address_title(); ?></h3>
				<?php do_action( 'wpo_wcpdf_before_shipping_address', $this->get_type(), $this->order ); ?>
				<p>
				<?php
					// Dirección de envío personalizada
					$user_id = $this->order->get_customer_id();
					$display_name = '';
					if ($user_id) {
						$user = get_userdata($user_id);
						if ($user) {
							$display_name = $user->display_name;
						}
					}
					$address = $this->order->get_shipping_address_1();
					$address2 = $this->order->get_shipping_address_2();
					$cp = $this->order->get_shipping_postcode();
					$city = $this->order->get_shipping_city();
					$country = $this->order->get_shipping_country();
					$country_name = WC()->countries->countries[$country] ?? $country;
					$phone = $this->order->get_billing_phone();
					$lines = [];
					if ($display_name) $lines[] = $display_name;
					if ($address) $lines[] = $address . ($address2 ? ', ' . $address2 : '');
					$lines[] = trim($cp . ' ' . $city . ' - ' . $country_name);
					if ($phone && isset( $this->settings['display_phone'] ) ) $lines[] = 'Teléfono: ' . $phone;
					echo implode("<br>", array_filter($lines));
				?>
				</p>
				

			<?php endif; ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
				<?php if ( $this->get_shipping_method() ) : ?>
					<tr class="shipping-method">
						<th><?php $this->shipping_method_title(); ?></th>
						<td><?php $this->shipping_method(); ?></td>
					</tr>
				<?php endif; ?>
				<tr class="order-number">
					<th><?php $this->order_number_title(); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th>Fecha de entrega:</th>
					<td>
						<?php
						$order_id = $this->order->get_id();
						$meta_key = '_wcpdf_packing_slip_date';
						$fecha_albaran = get_post_meta($order_id, $meta_key, true);
						if (!$fecha_albaran || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_albaran)) {
							$fecha_albaran = date('Y-m-d');
							update_post_meta($order_id, $meta_key, $fecha_albaran);
						}
						// Convertir a WC_DateTime para compatibilidad y formateo
						if (class_exists('WC_DateTime')) {
							try {
								$fecha_obj = new WC_DateTime($fecha_albaran);
								if ($fecha_obj instanceof WC_DateTime) {
									echo esc_html($fecha_obj->date_i18n('d/m/Y'));
								} else {
									echo esc_html($fecha_obj->format('d/m/Y'));
								}
							} catch (Exception $e) {
								echo esc_html($fecha_albaran);
							}
						} else {
							echo esc_html(date('d/m/Y', strtotime($fecha_albaran)));
						}
						?>
					</td>
				</tr>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
			</table>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

<table class="order-details">
	<thead>
		<tr>
			<th class="product" style="white-space: nowrap;">Producto</th>
			<th class="unit-price" style="white-space: nowrap;">Precio unit.</th>
			<th class="quantity" style="white-space: nowrap;">Cantidad</th>
			<th class="price" style="white-space: nowrap;">Total</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $this->get_order_items() as $item_id => $item ) : ?>
			<tr class="<?php echo esc_html( $item['row_class'] ); ?>">
				<td class="product">
					<p class="item-name" style="white-space: nowrap;"><?php echo esc_html( $item['name'] ); ?></p>
					<?php do_action( 'wpo_wcpdf_before_item_meta', $this->get_type(), $item, $this->order ); ?>
					<div class="item-meta">
						<?php if ( ! empty( $item['sku'] ) ) : ?>
							<p class="sku"><span class="label"><?php $this->sku_title(); ?></span> <?php echo esc_attr( $item['sku'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $item['weight'] ) ) : ?>
							<p class="weight"><span class="label"><?php $this->weight_title(); ?></span> <?php echo esc_attr( $item['weight'] ); ?><?php echo esc_attr( get_option( 'woocommerce_weight_unit' ) ); ?></p>
						<?php endif; ?>
						<!-- ul.wc-item-meta -->
						<?php if ( ! empty( $item['meta'] ) ) : ?>
							<?php echo wp_kses_post( $item['meta'] ); ?>
						<?php endif; ?>
						<!-- / ul.wc-item-meta -->
					</div>
					<?php do_action( 'wpo_wcpdf_after_item_meta', $this->get_type(), $item, $this->order ); ?>
				</td>
				<td class="unit-price" style="width: auto;"><?php echo $item['single_line_total']; ?></td>
				<td class="quantity"><?php echo esc_html( $item['quantity'] ); ?></td>
				<td class="price"><?php echo $item['order_price']; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<table class="notes-totals">
	<tbody>
		<tr class="no-borders">
			<td class="no-borders notes-cell">
				<?php do_action( 'wpo_wcpdf_before_document_notes', $this->get_type(), $this->order ); ?>
				<?php if ( $this->get_document_notes() ) : ?>
					<div class="document-notes">
						<h3><?php $this->notes_title(); ?></h3>
						<?php $this->document_notes(); ?>
					</div>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_document_notes', $this->get_type(), $this->order ); ?>
<?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>
<?php if ( $this->get_shipping_notes() ) : ?>
	<div class="customer-notes">
						<h3><?php $this->customer_notes_title(); ?></h3>
		<?php $this->shipping_notes(); ?>
	</div>
<?php endif; ?>
<?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>
			</td>
			<td class="no-borders totals-cell">
				<table class="totals">
					<tfoot>
						<?php foreach ( $this->get_woocommerce_totals() as $key => $total ) : ?>
							<tr class="<?php echo esc_attr( $key ); ?>">
								<th class="description"><?php echo $total['label']; ?></th>
								<td class="price"><span class="totals-price"><?php echo $total['value']; ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tfoot>
				</table>
			</td>
		</tr>
	</tbody>
</table>

<?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>
		<div id="footer">
			Inscrita en el Registre Mercantil de Barcelona, Hoja B-524363 Tomo 46577 Folio 26
		</div>
<div class="bottom-spacer"></div>

<?php if ( $this->get_footer() ) : ?>
	<htmlpagefooter name="docFooter"><!-- required for mPDF engine -->
		<div id="footer">
			<!-- hook available: wpo_wcpdf_before_footer -->
			<?php $this->footer(); ?>
			<!-- hook available: wpo_wcpdf_after_footer -->
		</div>
	</htmlpagefooter><!-- required for mPDF engine -->
<?php endif; ?>

<?php
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	error_log( '[PALAFITO][packing-slip.php] Número de albarán: ' . print_r( $this->get_number(), true ) );
	error_log( '[PALAFITO][packing-slip.php] Fecha de albarán: ' . print_r( $this->get_date(), true ) );
}
?>

<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>
