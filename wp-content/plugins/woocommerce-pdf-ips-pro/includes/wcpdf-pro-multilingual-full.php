<?php
namespace WPO\WC\PDF_Invoices_Pro;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( '\\WPO\\WC\\PDF_Invoices_Pro\\Multilingual_Full' ) ) :

class Multilingual_Full {

	/**
	 * The language used before creating a PDF
	 * @var String
	 */
	public $previous_language;

	public function __construct() {
		// load switcher class
		include_once( 'wcpdf-pro-order-language-switcher.php' );

		// on non-multilingual setups
		if( ! class_exists('\\SitePress') && ! class_exists('\\Polylang') ) {
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'set_order_locale' ) );
		}

		// add actions
		add_action( 'wpo_wcpdf_before_pdf', array( $this, 'store_language' ), 10, 2 );
		add_action( 'wpo_wcpdf_before_html', array( $this, 'switch_language' ), 10, 2 );
		add_action( 'wpo_wcpdf_after_pdf', array( $this, 'reset_language' ), 10, 1 );

		// helper filters
		add_filter( 'wpo_wcpdf_order_taxes', array( $this, 'wpml_tax_labels' ), 10, 2 );

		// PLL translations for customizer labels
		if ( function_exists('pll_register_string') ) {
			$this->pll_st_register_premium_templates_columns_totals_labels();
		}
		add_filter( 'wpo_wcpdf_template_editor_settings', array( $this, 'string_translate_premium_templates_label_text' ), 20, 4 );

		// force reloading textdomains if user language is not site default
		if ( $this->get_current_user_locale_setting() && ( class_exists('\\Polylang') || class_exists('\\SitePress') ) ) {
			add_filter('wpo_wcpdf_force_reload_text_domains','__return_true');
		}

		if (class_exists('\\SitePress')) {
			add_filter('wpo_wcpdf_allow_reload_attachment_translations', '__return_false' );
		}
	}

	public function set_order_locale( $order_id ) {
		if( empty( $order_id ) ) return;

		$order = wc_get_order( $order_id );

		if( ! empty( $order ) ) {
			// for non-multilingual setups we use the default site language (PDF language might be overridden by Pro tab setting)
			$order->add_meta_data( 'wcpdf_order_locale', get_locale(), true );
			$order->save_meta_data();
		}
	}

	public function get_languages() {
		if ( function_exists( 'icl_get_languages' ) ) {
			$icl_get_languages = icl_get_languages( 'skip_missing=0' );
			$languages         = array();
			foreach ( $icl_get_languages as $lang => $data ) {
				$languages[$data['language_code']] = $data['native_name'];
			}
			return $languages;
		} else {
			return false;
		}
	}
	
	public function get_order_lang( $order_id, $document_type = null ) {
		if ( empty( $order_id ) ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		if ( empty( $order ) ) {
			return null;
		}

		if ( is_callable( array( $order, 'get_type' ) ) && $order->get_type() == 'shop_order_refund' ) {
			$order = wc_get_order( $order->get_parent_id() );
			if ( empty( $order ) ) {
				return null;
			}
		}

		// get document language setting
		$document_language = isset( WPO_WCPDF_Pro()->functions->pro_settings['document_language'] ) ? WPO_WCPDF_Pro()->functions->pro_settings['document_language'] : 'order';

		// WPML specific
		if ( class_exists( '\\SitePress' ) ) {
			global $sitepress;
			if ( $document_language == 'order' ) {
				// USE ORDER LANGUAGE
				$order_lang = $order->get_meta( 'wpml_language' );
				if ( $order_lang == '' ) {
					$order_lang = $sitepress->get_default_language();
				}
			} elseif ( apply_filters( 'wpml_language_is_active', NULL, $document_language ) ) {
				$order_lang = $document_language;
			}

			if ( empty( $order_lang ) ) {
				$order_lang = $sitepress->get_default_language();
			}

			return apply_filters( 'wpo_wcpdf_wpml_language', $order_lang, $order->get_id(), $document_type );

		// Polylang specific
		} elseif ( class_exists( '\\Polylang' ) && did_action( 'pll_init' ) ) {
			if ( ! function_exists( 'pll_get_post_language' ) ) {
				return null;
			}
			if ( $document_language == 'order' ) {
				// USE ORDER LANGUAGE
				$order_lang = pll_get_post_language( $order->get_id(), 'slug' );
			} elseif ( ! in_array( $document_language, array( 'admin', 'user' ) ) ) {
				$order_lang = $document_language;
			}

			if ( empty( $order_lang ) ) {
				// USE SITE DEFAULT LANGUAGE
				$order_lang = pll_default_language( 'slug' );
			}

			return apply_filters( 'wpo_wcpdf_pll_language', $order_lang, $order->get_id(), $document_type );

		// Non-multilingual setups
		} else {
			return null;
		}
	}

	public function store_language( $document_type, $document ) {
		// WPML specific
		if (class_exists('\\SitePress')) {
			global $sitepress;
			$this->previous_language = $sitepress->get_current_language();

		// Polylang specific
		} elseif (class_exists('\\Polylang') && did_action( 'pll_init' ) ) {
			// helper filter to handle reloading textdomains
			add_action( 'change_locale', array( $this, 'reload_textdomains' ), 999 ); // Since WP 4.7
			if ( function_exists( 'determine_locale' ) ) { // WP5.0+
				$this->previous_language = determine_locale();
			} else {
				$this->previous_language = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			}
		
		// Non-multilingual setups
		} else {
			// helper filter to handle reloading textdomains
			add_action( 'change_locale', array( $this, 'reload_textdomains' ), 999 ); // Since WP 4.7
			if ( function_exists( 'determine_locale' ) ) { // WP5.0+
				$this->previous_language = determine_locale();
			} else {
				$this->previous_language = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
			}
		}
		
	}

	/**
	 * WPML compatibility helper function: set wpml language before pdf creation
	 */
	public function switch_language( $document_type, $document ) {
		if (empty($document->order)) { // bulk document, no need to switch (this is done per individual document)
			return;
		}

		$language_switcher = new Language_Switcher($document);
		// switch language
		$language_switcher->switch_language( $document_type, $document );

		// make sure country translations are reloaded
		add_filter( 'woocommerce_countries', array( $this, 'reload_countries' ), 999 );

		// filter setting texts to use settings field translations
		$language_switcher->translate_setting_texts();
	}

	/**
	 * Set locale/language to default after PDF creation
	 */
	public function reset_language() {
		remove_action( 'change_locale', array( $this, 'reload_textdomains' ), 999 ); // Since WP 4.7
		remove_filter( 'woocommerce_countries', array( $this, 'reload_countries' ), 999 );
		global $sitepress;
		// WPML specific
		if ( class_exists('\\SitePress') ) {
			$sitepress->switch_lang( $this->previous_language );
		// Polylang specific
		} elseif ( class_exists('\\Polylang') && function_exists( 'PLL' ) && ! empty( \PLL()->model ) && method_exists( \PLL()->model, 'get_language' ) && did_action( 'pll_init' ) ) {
			// $language_switcher = new Language_Switcher();
			// $language_switcher->reload_text_domains();
			// set PLL locale to order locale to translate product names correctly
			\PLL()->curlang = \PLL()->model->get_language( $this->previous_language );
			if ( function_exists( 'switch_to_locale ') ) { // WP4.7+
				switch_to_locale( $this->previous_language );
			}
		}
	}

	public function reload_textdomains( $locale = '' ) {
		// prevent Polylang (2.2.6+) mo file override, our admin_ajax call is incorrectly determined as frontend
		if ( class_exists( '\\Polylang' ) && function_exists( 'PLL' ) && ! empty( \PLL()->filters ) && method_exists( \PLL()->filters, 'load_textdomain_mofile' ) ) {
			remove_filter( 'load_textdomain_mofile', array( \PLL()->filters, 'load_textdomain_mofile' ) );
		}
		// unload text domains
		unload_textdomain( 'woocommerce' );
		unload_textdomain( 'woocommerce-pdf-invoices-packing-slips' );
		unload_textdomain( 'wpo_wcpdf' );
		unload_textdomain( 'wpo_wcpdf_pro' );

		// reload text domains
		WC()->load_plugin_textdomain();
		WPO_WCPDF()->translations();
		WPO_WCPDF_Pro()->translations();
	}

	public function reload_countries( $countries ) {
		if ( file_exists( WC()->plugin_path() . '/i18n/countries.php' ) ) {
			$countries = include WC()->plugin_path() . '/i18n/countries.php';
		}
		return $countries;
	}

	/**
	 * Parse tax labels to ensure they are translated for credit notes too
	 * @param  array   $taxes    total tax rows
	 * @param  object  $document WCPDF Order Document object
	 * @return array   $taxes    total tax rows
	 */
	public function wpml_tax_labels( $taxes, $document ) {
		if ( isset( $document->order ) && class_exists( '\\SitePress' ) ) {
			$order_type = method_exists( $document->order, 'get_type' ) ? $document->order->get_type() : $document->order->order_type;
			
			// only for refund orders!
			if ( 'shop_order_refund' === $order_type ) {
				foreach ( $taxes as $key => $tax ) {
					$tax_label              = __( $taxes[ $key ]['label'], 'woocommerce-pdf-invoices-packing-slips' );
					$taxes[ $key ]['label'] = apply_filters('wpml_translate_single_string', $tax_label, 'admin_texts_woocommerce_tax', $tax_label );
				}
			}
		}
		return $taxes;
	}

	public function pll_st_register_premium_templates_columns_totals_labels() {
		$settings = get_option('wpo_wcpdf_editor_settings');
		if ( empty($settings) || !is_array($settings) || !function_exists( 'pll_register_string') || !function_exists( 'pll__' ) ) {
			return;
		}
		foreach ($settings as $setting_key => $setting) {
			if ( strpos($setting_key, '_columns') !== false || strpos($setting_key, '_totals') !== false || strpos($setting_key, '_custom') !== false) {
				foreach ($setting as $key => $value) {
					if ( empty( $value['type'] ) ) {
						continue;
					}
					$settings_name = str_replace('fields_', '', $setting_key);
					if ( !empty($value['label']) ) {
						$name = "wpo_wcpdf_templates_{$settings_name}_{$value['type']}";
						pll_register_string(
							$name,
							$value['label'],
							'PDF Invoices & Packing Slips for WooCommerce - Premium Templates'
						);
					}
					if ( !empty($value['text']) ) {
						$name = "wpo_wcpdf_templates_{$settings_name}_{$value['type']}_text_{$key}";
						pll_register_string(
							$name,
							$value['text'],
							'PDF Invoices & Packing Slips for WooCommerce - Premium Templates',
							true
						);
					}
				}
			}
		}
	}

	public function string_translate_premium_templates_label_text( $settings, $template_type, $settings_name, $document = null ) {
		if( $settings_name == 'custom' ) {
			$textdomain = 'wpo_wcpdf_templates_custom_blocks';
		} else {
			$textdomain = "wpo_wcpdf_templates_{$settings_name}";
		}

		foreach ($settings as &$setting) {
			// label
			if ( !empty($setting['label']) ) {
				// WPML
				if( class_exists('\\SitePress') ) {
					$setting['label'] = __( $setting['label'], $textdomain );
				// Polylang
				} elseif( function_exists('pll__') ) {
					$setting['label'] = pll__( $setting['label'] );
				}
			}
			// text
			if ( !empty($setting['text']) ) {
				// WPML
				if( class_exists('\\SitePress') ) {
					$setting['text'] = __( $setting['text'], $textdomain );
				// Polylang
				} elseif( function_exists('pll__') ) {
					$setting['text'] = pll__( $setting['text'] );
				}
			}
		}
		return $settings;
	}

	/**
	 * Get locale setting from user profile (site-default = empty)
	 * Used to determine whether to force reloading textdomains
	 */
	public function get_current_user_locale_setting() {
		$user = false;
		if ( function_exists( 'wp_get_current_user' ) ) {
			$user = wp_get_current_user();
		}

		if ( ! $user ) {
			return false;
		} else {
			return $user->locale;
		}
	}

} // end class

endif; // end class_exists

return new Multilingual_Full();