<?php
namespace WPO\WC\PDF_Invoices_Pro;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\\WPO\\WC\\PDF_Invoices_Pro\\Multilingual_Html' ) ) :

class Multilingual_Html {
	
	public $selected_plugin = '';

	public function __construct() {
		$pro_settings                   = get_option( 'wpo_wcpdf_settings_pro', array() );
		$multilingual_supported_plugins = WPO_WCPDF_Pro()->functions->multilingual_supported_plugins();
		
		if ( ! empty( $pro_settings['document_language'] ) ) {
			$document_language_option = sanitize_text_field( $pro_settings['document_language'] );
			
			if ( isset( $multilingual_supported_plugins[$document_language_option] ) && 'html' === $multilingual_supported_plugins[$document_language_option]['support'] ) {
				$this->selected_plugin = $document_language_option;
			}
			
			if ( ! empty( $this->selected_plugin ) && $this->is_plugin_still_active( $this->selected_plugin ) ) {
				$this->init();
			}
		}
	}
	
	public function init() {
		$this->proprietary_translation_tweaks();
		
		add_filter( 'wpo_wcpdf_get_html', array( $this, 'translate_html' ), 99, 2 );
	}
	
	private function is_plugin_still_active( $slug ) {
		$is_active = false;
		
		if ( empty( $slug ) ) {
			return $is_active;
		}
		
		$multilingual_supported_plugins = WPO_WCPDF_Pro()->functions->multilingual_supported_plugins();
		
		if ( ! empty( $multilingual_supported_plugins[$slug]['function'] ) && function_exists( $multilingual_supported_plugins[$slug]['function'] ) ) {
			$is_active = true;
		}
		
		if ( ! $is_active ) {
			$pro_settings = get_option( 'wpo_wcpdf_settings_pro', array() );
			if ( ! empty( $pro_settings['document_language'] ) && $slug == $pro_settings['document_language'] ) {
				unset( $pro_settings['document_language'] );
				update_option( 'wpo_wcpdf_settings_pro', $pro_settings );
				$this->selected_plugin = '';
			}
		}
		
		return $is_active;
	}
	
	public function translate_html( $original_html, $document ) {
		if ( empty( $this->selected_plugin ) ) {
			return $original_html;
		}
		
		if ( empty( $document ) ) {
			return $original_html;
		}
	
		$order = $document->order;
		
		if ( empty( $order ) ) {
			return $original_html;
		}

		if ( is_callable( [ $order, 'get_type' ] ) && $order->get_type() == 'shop_order_refund' ) {
			$order = wc_get_order( $order->get_parent_id() );
			if ( empty( $order ) ) {
				return $original_html;
			}
		}
		
		$woocommerce_order_language = '';
		
		// check if HTML needs translation (different language)
		$needs_translation = false;
		switch ( $this->selected_plugin ) {
			case 'weglot':
				$woocommerce_order_language = $order->get_meta( 'weglot_language', true );
				if ( function_exists( 'weglot_get_original_language' ) && weglot_get_original_language() != $woocommerce_order_language ) {
					$needs_translation = true;
				}
				break;
			case 'translatepress':
				$woocommerce_order_language = $order->get_meta( 'trp_language', true );
				if ( class_exists( 'TRP_Translate_Press' ) ) {
					$trp          = \TRP_Translate_Press::get_trp_instance();
					$trp_settings = $trp->get_component( 'settings' );
					$settings     = $trp_settings->get_settings();
					
					if ( ! empty( $settings ) && isset( $settings['default-language'] ) && $settings['default-language'] != $woocommerce_order_language ) {
						$needs_translation = true;
					}
				}
				break;
			case 'gtranslate':
				$woocommerce_order_language = $order->get_meta( 'wcpdf_pro_gtranslate_order_language', true );
				if ( ! empty( $woocommerce_order_language ) && is_null( $_SERVER['HTTP_X_GT_LANG'] ) ) {
					$_SERVER['HTTP_X_GT_LANG'] = $woocommerce_order_language;
				}
				
				$data = get_option( 'GTranslate' );
				if ( isset( $data['default_language'] ) && $data['default_language'] != $woocommerce_order_language ) {
					$needs_translation = true;
				}
				break;
		}
		
		// if no need for translation bail
		if ( ! apply_filters( 'wpo_wcpdf_pro_multilingual_html_needs_translation', $needs_translation, $document, $this ) ) {
			return $original_html;
		}
		
		$woocommerce_order_language = apply_filters( 'wpo_wcpdf_pro_multilingual_html_order_language', $woocommerce_order_language, $document, $this );

		// HTML translation
		$translated_html = '';
		switch ( $this->selected_plugin ) {
			case 'weglot':
				if ( function_exists( 'weglot_get_service' ) ) {
					$weglot_pdf_translate_service = weglot_get_service( 'Pdf_Translate_Service_Weglot' );
					if ( $weglot_pdf_translate_service && is_callable( [ $weglot_pdf_translate_service, 'translate_pdf' ] ) ) {
						$translated_html = $weglot_pdf_translate_service->translate_pdf( $original_html, $woocommerce_order_language );
						$translated_html = isset( $translated_html['content'] ) ? $translated_html['content'] : $translated_html;
					}
				}
				break;
			case 'translatepress':
				if ( function_exists( 'trp_translate' ) && ! empty( $woocommerce_order_language ) ) {
					$translated_html = trp_translate( $original_html, $woocommerce_order_language, false );
				}
				break;
			case 'gtranslate':
				if ( function_exists( 'gt_translate_invoice_pdf' ) ) {
					$translated_html = gt_translate_invoice_pdf( $original_html );	
					
					if ( ! is_null( $_SERVER['HTTP_X_GT_LANG'] ) ) {
						$_SERVER['HTTP_X_GT_LANG'] = null;
					}
				}
				break;
		}
				
		if ( empty( $translated_html ) || ! is_string( $translated_html ) ) {
			return $original_html;
		}
		
		return apply_filters( 'wpo_wcpdf_pro_multilingual_html_translated', $translated_html, $original_html, $woocommerce_order_language, $document, $this );
	}
	
	public function proprietary_translation_tweaks() {
		switch ( $this->selected_plugin ) {
			case 'weglot':
				if ( class_exists( '\WeglotWP\Third\Woocommercepdf\WCPDF_Weglot' ) ) {
					$weglot_class = new \WeglotWP\Third\Woocommercepdf\WCPDF_Weglot();
					
					if ( $weglot_class && is_callable( array( $weglot_class, 'translate_invoice_pdf' ) ) ) {
						remove_filter( 'wpo_wcpdf_before_dompdf_render', array( $weglot_class, 'translate_invoice_pdf' ), 10, 4 );
						remove_filter( 'wpo_wcpdf_after_mpdf_write', array( $weglot_class, 'translate_invoice_pdf' ), 10, 4 );
					}
				}
				break;
			case 'translatepress':
				remove_filter( 'trp_stop_translating_page', 'trp_woo_pdf_invoices_and_packing_slips_compatibility_dont_translate_pdf', 10, 2 );
				break;
			case 'gtranslate':
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'set_gtranslate_order_language' ), 10, 1 );
				break;
		}
	}
	
	public function set_gtranslate_order_language( $order_id ) {
		if ( empty( $order_id ) ) {
			return;
		}
		
		if ( 'gtranslate' != $this->selected_plugin ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! empty( $order ) && ! empty( $_SERVER['HTTP_X_GT_LANG'] ) ) {
			$order->add_meta_data( 'wcpdf_pro_gtranslate_order_language', esc_attr( $_SERVER['HTTP_X_GT_LANG'] ), true );
			$order->save_meta_data();
		}
	}
	
}

endif; // class_exists

return new Multilingual_Html();