<?php
namespace WPO\WC\PDF_Invoices_Pro;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( '\\WPO\\WC\\PDF_Invoices_Pro\\Language_Switcher' ) ) :

class Language_Switcher {
	
	/**
	 * Locale of the order
	 * @var String
	 */
	public $order_locale;

	/**
	 * Language (slug) of the order
	 * @var String
	 */
	public $order_lang;

	public function __construct( $document ) {
		// clean up after ourselves
		add_action( 'wpo_wcpdf_template_order_processed', array( $this, 'remove_filters' ) );

		// get order locale and set order_lang, order_locale properties
		$this->set_order_lang_locale( $document->get_type(), $document );
	}

	/**
	 * Switch language/translations
	 */
	public function switch_language( $document_type, $document ) {
		global $sitepress;

		// bail if we don't have an order_locale
		if ( empty( $this->order_locale ) ) {
			return;
		}

		// reload text domains
		if ( class_exists( '\\Polylang' ) && function_exists( 'switch_to_locale' ) ) { // WP4.7+
			switch_to_locale( $this->order_locale );
		}

		if ( apply_filters( 'wpo_wcpdf_force_reload_text_domains', false ) || ( ! class_exists( '\\SitePress' ) && ! class_exists( '\\Polylang' ) ) ) {
			// apply filters for plugin locale
			add_filter( 'locale', array( $this, 'plugin_locale' ), 10, 2 );
			add_filter( 'plugin_locale', array( $this, 'plugin_locale' ), 10, 2 );
			add_filter( 'theme_locale', array( $this, 'plugin_locale' ), 10, 2 );

			// force reload text domains
			$this->reload_text_domains();
		}

		// allow third party plugins to reload their textdomains too
		do_action( 'wpo_wcpdf_reload_text_domains', $this->order_locale );

		// reload country name translations
		WC()->countries = new \WC_Countries();

		// WPML specific
		if ( class_exists( '\\SitePress' ) ) {
			// filters to ensure correct locale
			add_filter( 'icl_current_string_language', array( $this, 'wpml_admin_string_language' ), 9, 2);
			add_filter( 'wcml_get_order_items_language', array( $this, 'wcml_order_items_string_language' ), 999, 2 );
			add_filter( 'wcml_should_save_adjusted_order_item_in_language', '__return_false' );
			add_filter( 'wcml_should_translate_order_items', '__return_true' );
			add_filter( 'wcml_should_translate_shipping_method_title', '__return_true' );
			
			$sitepress->switch_lang( $this->order_lang );
			//$my_current_lang = apply_filters( 'wpml_current_language', NULL ); // debug only

		// PLL specific
		} elseif ( class_exists( '\\Polylang' ) && function_exists( 'PLL' ) && ! empty( \PLL()->model ) && method_exists( \PLL()->model, 'get_language' ) && did_action( 'pll_init' ) ) {
			// set PLL locale to order locale to translate product names correctly
			\PLL()->curlang = \PLL()->model->get_language( $this->order_locale );

			// // load Polylang translated string
			// static $cache; // Polylang string translations cache object to avoid loading the same translations object several times
			// // Cache object not found. Create one...
			// if ( empty( $cache ) ) {
			// 	$cache = new \PLL_Cache();
			// }

			// if (false === $mo = $cache->get( $this->order_locale ) ) {
			// 	$mo = new \PLL_MO();
			// 	$mo->import_from_db( $GLOBALS['polylang']->model->get_language( $this->order_locale ) );
			// 	$GLOBALS['l10n']['pll_string'] = &$mo;
			// 	// Add to cache
			// 	$cache->set( $this->order_locale, $mo );
			// }

		// Non-multilingual setups
		} else {
			if ( function_exists( 'switch_to_locale' ) ) {
				switch_to_locale( $this->order_locale );
			}
		}
		
		$GLOBALS['wp_locale'] = new \WP_Locale(); // ensures correct translation of dates e.a.
	}

	/**
	 * Set order_lang and order_locale properties
	 */
	public function set_order_lang_locale( $document_type, $document ) {
		if ( empty( $document->order ) ) {
			return;
		}

		// get document language setting
		$document_language = isset( WPO_WCPDF_Pro()->functions->pro_settings['document_language'] ) ? WPO_WCPDF_Pro()->functions->pro_settings['document_language'] : 'order';

		// WPML specific
		if ( class_exists( '\\SitePress' ) ) {
			global $sitepress;
			
			// use order language
			if ( $document_language == 'order' ) {
				$order_lang = $document->order->get_meta( 'wpml_language' );
				
				if ( empty( $order_lang ) && $document_type == 'credit-note' && 'shop_order_refund' === WPO_WCPDF()->order_util->get_order_type( $document->order_id ) ) {
					$parent_order = wc_get_order( $document->order->get_parent_id() );
					$order_lang   = $parent_order->get_meta( 'wpml_language' );
					unset( $parent_order );
				}
				
				if ( $order_lang == '' ) {
					$order_lang = $sitepress->get_default_language();
				}
			} elseif ( apply_filters( 'wpml_language_is_active', NULL, $document_language ) ) {
				$order_lang = $document_language;
			// use site language
			} else {
				$order_lang = $sitepress->get_default_language();
			}

			$this->order_lang   = apply_filters( 'wpo_wcpdf_wpml_language', $order_lang, $document->order_id, $document_type );
			$this->order_locale = $sitepress->get_locale( $this->order_lang );

		// Polylang specific
		} elseif ( class_exists( '\\Polylang' )  && function_exists( 'PLL' ) && ! empty( \PLL()->model ) && method_exists( \PLL()->model, 'get_languages_list' ) && did_action( 'pll_init' ) ) {
			if ( ! function_exists( 'pll_get_post_language' ) ) {
				return;
			}
			
			// use order language
			if ( $document_language == 'order' ) {
				$order_id = $document->order_id;
				if ( 'shop_order_refund' === WPO_WCPDF()->order_util->get_order_type( $order_id ) ) {
					$order_id = $document->order->get_parent_id();
				}
				
				$order_locale = pll_get_post_language( $order_id, 'locale' );
				$order_lang   = pll_get_post_language( $order_id, 'slug' );
				
				if ( $order_lang == '' ) {
					$order_locale = pll_default_language( 'locale' );
					$order_lang   = pll_default_language( 'slug' );
				}
			} elseif ( ! in_array( $document_language, array( 'admin', 'user' ) ) ) {
				$order_lang = $document_language;
				foreach ( \PLL()->model->get_languages_list() as $language ) {
					if ( $language->slug == $order_lang ) {
						$order_locale = $language->locale;
					}
				}
			}

			// use site default language
			if ( empty( $order_locale ) ) {
				$order_locale = pll_default_language( 'locale' );
				$order_lang   = pll_default_language( 'slug' );
			}

			$this->order_locale = apply_filters( 'wpo_wcpdf_pll_locale', $order_locale, $document->order_id, $document_type );
			$this->order_lang   = apply_filters( 'wpo_wcpdf_pll_language', $order_lang, $document->order_id, $document_type );

		// Non-multilingual setups
		} else {
			// use order language
			if ( $document_language == 'order' ) {
				$order_locale = $document->order->get_meta( 'wcpdf_order_locale' );
				
				if ( empty( $order_locale ) && $document_type == 'credit-note' && 'shop_order_refund' === WPO_WCPDF()->order_util->get_order_type( $document->order_id ) ) {
					$parent_order     = wc_get_order( $document->order->get_parent_id() );
					$order_locale     = $parent_order->get_meta( 'wcpdf_order_locale' );
					unset( $parent_order );
				}
			} elseif ( ! in_array( $document_language, array( 'admin', 'user' ) ) ) {
				$order_locale = $document_language;
			}

			// use site default language
			if ( empty( $order_locale ) ) {
				$order_locale = get_locale();
			}

			$this->order_locale = $order_locale;
		}

		$this->order_lang   = apply_filters( 'wpo_wcpdf_multilingual_order_lang', $this->order_lang, $document );
		$this->order_locale = apply_filters( 'wpo_wcpdf_multilingual_order_locale', $this->order_locale, $document );
	}

	/**
	 * Force reload textdomains
	 */
	public function reload_text_domains() {
		// prevent Polylang (2.2.6+) mo file override
		if ( class_exists( '\\Polylang' ) && function_exists( 'PLL' ) && ! empty( \PLL()->filters ) && method_exists( \PLL()->filters, 'load_textdomain_mofile' ) ) {
			remove_filter( 'load_textdomain_mofile', array( \PLL()->filters, 'load_textdomain_mofile' ) );
		}

		// // from WP_Locale_Switcher - not sure if this works at all?
		// $domains = $GLOBALS['l10n'] ? array_keys( $GLOBALS['l10n'] ) : array();
		// $force_loaded = array( 'woocommerce', 'woocommerce-pdf-invoices-packing-slips', 'wpo_wcpdf_pro', 'default' );
		// foreach ( $domains as $domain ) {
		// 	// skip ones that we already force load
		// 	if ( in_array($domain, $force_loaded) ) {
		// 		continue;
		// 	}
		// 	unload_textdomain( $domain );
		// 	get_translations_for_domain( $domain );
		// }

		// unload text domains
		unload_textdomain( 'woocommerce' );
		unload_textdomain( 'woocommerce-pdf-invoices-packing-slips' );
		unload_textdomain( 'wpo_wcpdf' );
		unload_textdomain( 'wpo_wcpdf_pro' );

		// reload text domains
		WC()->load_plugin_textdomain();
		WPO_WCPDF()->translations();
		WPO_WCPDF_Pro()->translations();

		// WP Core
		unload_textdomain( 'default' );
		load_default_textdomain( $this->order_locale );
	}

	/**
	 * set locale for plugins (used in locale and plugin_locale filters)
	 * @param  string $locale Locale
	 * @param  string $domain text domain
	 * @return string $locale Locale
	 */
	public function plugin_locale( $locale, $domain = '' ) {
		$locale = $this->order_locale;
		return $locale;
	}

	/**
	 * WPML specific filter for admin string language
	 * @param  string $current_language language slug
	 * @param  [type] $name             [description]
	 * @return string $current_language language slug
	 */
	public function wpml_admin_string_language ( $current_language, $name ) {
		if ( !empty( $this->order_lang ) ) {
			return $this->order_lang;
		} else {
			return $current_language;
		}
	}

	/**
	 * WCML specific filter for order items string language
	 * @param  string $language language slug
	 * @param  object $order	order object
	 * @return string $language language slug
	 */
	public function wcml_order_items_string_language ( $language, $order ) {
		if ( ! empty( $this->order_lang ) && ! empty($order) ) {
			return $this->order_lang;
		} else {
			return $language;
		}
	}

	/**
	 * Remove language/locale filters after PDF creation
	 */
	public function remove_filters() {
		global $sitepress;
		// WPML specific
		if ( class_exists('\\SitePress') ) {
			remove_filter( 'icl_current_string_language', array( $this, 'wpml_admin_string_language' ) );
			remove_filter( 'wcml_get_order_items_language', array( $this, 'wcml_order_items_string_language' ), 999, 2 );
			remove_filter( 'wcml_should_save_adjusted_order_item_in_language', '__return_false' );
			remove_filter( 'wcml_should_translate_order_items', '__return_true' );
			remove_filter( 'wcml_should_translate_shipping_method_title', '__return_true' );
		}

		if ( apply_filters( 'wpo_wcpdf_force_reload_text_domains', false ) ) {
			remove_filter( 'locale', array( $this, 'plugin_locale' ) );
			remove_filter( 'plugin_locale', array( $this, 'plugin_locale' ) );
			remove_filter( 'theme_locale', array( $this, 'plugin_locale' ) );

			// force reload text domains
			$this->reload_text_domains();
		}
	}

	/**
	 * Filter admin setting texts to apply translations
	 */
	public function translate_setting_texts () {
		// WPML specific
		if ( class_exists( '\\SitePress' ) || class_exists( '\\Polylang' ) ) {
			add_filter( 'wpo_wcpdf_header_logo_id', array( $this, 'wpml_header_logo_id' ), 8, 2 );
			add_filter( 'wpo_wcpdf_header_logo_id', array( $this, 'wpml_translated_media_id' ), 9, 2 );
			add_filter( 'wpo_wcpdf_shop_name_settings_text', array( $this, 'wpml_shop_name_text' ), 9, 2 );
			add_filter( 'wpo_wcpdf_shop_address_settings_text', array( $this, 'wpml_shop_address_text' ), 9, 2 );
			add_filter( 'wpo_wcpdf_footer_settings_text', array( $this, 'wpml_footer_text' ), 9, 2 );
			add_filter( 'wpo_wcpdf_extra_1_settings_text', array( $this, 'wpml_extra_1_text' ), 9, 2 );
			add_filter( 'wpo_wcpdf_extra_2_settings_text', array( $this, 'wpml_extra_2_text' ), 9, 2 );
			add_filter( 'wpo_wcpdf_extra_3_settings_text', array( $this, 'wpml_extra_3_text' ), 9, 2 );
			add_filter( 'wpo_wcpdf_shop_vat_label_settings_text', array( $this, 'wpml_vat_label_text' ), 9, 2 );
			add_filter( 'wpo_wcpdf_shop_coc_label_settings_text', array( $this, 'wpml_coc_label_text' ), 9, 2 );
		}
	}

	public function wpml_translated_media_id( $media_id, $document = null ) {
		$media_id = apply_filters( 'wpml_object_id', $media_id, 'attachment', true );
		return $media_id;
	}

	/**
	 * Get string translations
	 * 
	 * @param string|int $header_logo_id
	 * @param object     $document
	 * 
	 * @return int|bool Returnes the logo ID as integer if found, false otherwise.
	 */
	public function wpml_header_logo_id ( $header_logo_id, object $document = null ) {
		$header_logo_id = absint( $this->get_string_translation( 'header_logo', $header_logo_id, $document ) );
		
		return ( 0 === $header_logo_id ) ? false : $header_logo_id;
	}
	public function wpml_shop_name_text( string $shop_name, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return $this->get_string_translation( 'shop_name', $shop_name, $document );
	}
	public function wpml_shop_address_text( string $shop_address, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return wpautop( $this->get_string_translation( 'shop_address', $shop_address, $document ) );
	}
	public function wpml_footer_text( string $footer, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return wpautop( $this->get_string_translation( 'footer', $footer, $document ) );
	}
	public function wpml_extra_1_text( string $extra_1, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return wpautop( $this->get_string_translation( 'extra_1', $extra_1, $document ) );
	}
	public function wpml_extra_2_text( string $extra_2, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return wpautop( $this->get_string_translation( 'extra_2', $extra_2, $document ) );
	}
	public function wpml_extra_3_text( string $extra_3, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return wpautop( $this->get_string_translation( 'extra_3', $extra_3, $document ) );
	}
	public function wpml_vat_label_text( string $vat_label, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return $this->get_string_translation( 'shop_vat_label', $vat_label, $document );
	}
	public function wpml_coc_label_text( string $coc_label, ?\WPO\WC\PDF_Invoices\Documents\Order_Document $document = null ): string {
		return $this->get_string_translation( 'shop_coc_label', $coc_label, $document );
	}

	/**
	 * Get string translation for string name, using $woocommerce_wpml helper function
	 */
	public function get_string_translation ( $string_name, $default, $document ) {
		global $woocommerce_wpml, $sitepress;

		// check internal settings first
		$translated = $this->get_i18n_setting( $string_name, $default, $document );
		if ( $translated !== false ) {
			return $translated;
		}
		
		// fallback to 1.X method
		if ( ! empty( $this->order_lang ) && ( class_exists( '\\SitePress' ) || class_exists( '\\Polylang' ) ) ) {
			$translations = get_option( 'wpo_wcpdf_translations' );
			$internal_string = 'wpo_wcpdf_template_settings['.$string_name.']';
			if ( ! empty( $translations[$internal_string][$this->order_lang] ) ) {
				return wptexturize( $translations[$internal_string][$this->order_lang] );
			}

			// fall back to string translations
			if ( class_exists( '\\SitePress' ) ) {
				$full_string_name = '[wpo_wcpdf_template_settings]'.$string_name;
				if ( isset( $woocommerce_wpml->emails ) && is_callable( array( $woocommerce_wpml->emails, 'wcml_get_email_string_info' ) ) && function_exists( 'icl_t' ) ) {
					if( $string_data = $woocommerce_wpml->emails->wcml_get_email_string_info( $full_string_name ) ) {
						$string = icl_t( $string_data[0]->context, $full_string_name ,$string_data[0]->value );
						return wptexturize( $string );
					}
				}
			} elseif ( class_exists( '\\Polylang' ) && function_exists( '\\pll_translate_string' ) ) {
				// we don't rely on $default, it has been filtered throught wpautop &
				// wptexturize when the apply_filter function was invoked
				if ( ! empty( $document->settings[$string_name][$this->order_lang] ) ) {
					$string = pll_translate_string( $document->settings[$string_name][$this->order_lang], $this->order_locale );
					return wptexturize( $string );
				}
			}
		}

		// no translations found, try to at least return a string
		if ( is_array( $default ) ) {
			return array_shift( $default );
		} elseif ( is_string( $default ) ) {
			return $default;
		} else {
			return '';
		}
	}

	public function get_i18n_setting( $setting_key, $default, $document, $lang = null ) {
		if ( ! empty( $document ) ) {
			$setting = $document->get_setting( $setting_key, $default );

			// check if we have a value for this setting
			if ( ! empty( $setting ) && is_array( $setting ) ) {
				// check if we have a translation for this setting in the document language
				if ( ! empty( $document->order ) && isset( $setting[$this->order_lang] ) ) {
					return wptexturize( $setting[$this->order_lang] );
				// use provided language
				} elseif( ! empty( $lang ) && isset( $setting[$lang] ) ) {
					return wptexturize( $setting[$lang] );
				// fallback to default 1
				} elseif ( ! empty( $default ) ) {
					return wptexturize( $default );
				// fallback to default 2
				} elseif ( isset( $setting['default'] ) ) {
					return wptexturize( $setting['default'] );
				// fallback to first language
				} else {
					$translation = reset( $setting );
					return wptexturize( $translation );
				}
			}
		}

		// no translation
		return false;
	}


} // end class

endif; // end class_exists