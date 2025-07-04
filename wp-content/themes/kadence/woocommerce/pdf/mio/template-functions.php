<?php
/**
 * Use this file for all your template filters and actions.
 * Requires PDF Invoices & Packing Slips for WooCommerce 1.4.13 or higher
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter('wpo_wcpdf_filename', function($filename, $document_type, $order_ids, $context, $args) {
    if (count($order_ids) === 1) {
        $order = wc_get_order($order_ids[0]);
        $user_id = $order ? $order->get_customer_id() : '';
        $display_name = '';
        if ($user_id) {
            $user = get_userdata($user_id);
            if ($user) {
                $display_name = $user->display_name;
            }
        }
        $display_name = sanitize_file_name($display_name);
        if ($document_type === 'packing-slip') {
            $albaran_number = 'A-' . $order->get_order_number();
            $filename = $albaran_number . ' - ' . $display_name . '.pdf';
        } elseif ($document_type === 'invoice') {
            $invoice = function_exists('wcpdf_get_invoice') ? wcpdf_get_invoice($order) : null;
            if ($invoice && $invoice->exists() && $invoice->get_number()) {
                $invoice_number = $invoice->get_number()->get_formatted();
            } else {
                $invoice_number = $order->get_order_number();
            }
            $filename = $invoice_number . ' - ' . $display_name . '.pdf';
        }
    }
    return $filename;
}, 20, 5);

// Forzar que la fecha del albarán (packing slip) sea siempre la de entrega (_wcpdf_packing-slip_date)
add_filter('wpo_wcpdf_packing-slip_date', function($date, $document_type, $order, $context, $formatted, $document) {
    if ($order && is_object($order)) {
        $order_id = is_callable([$order, 'get_id']) ? $order->get_id() : $order->ID;
        $meta_date = get_post_meta($order_id, '_wcpdf_packing-slip_date', true);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[PALAFITO][packing-slip-date] order_id: ' . $order_id . ' | meta_date: ' . print_r($meta_date, true) . ' | formatted: ' . print_r($formatted, true));
        }
        if ($meta_date) {
            $timestamp = is_numeric($meta_date) ? $meta_date : strtotime($meta_date);
            if (class_exists('WC_DateTime')) {
                try {
                    $date_obj = new WC_DateTime();
                    $date_obj->setTimestamp($timestamp);
                    $result = $formatted ? $date_obj->date_i18n(wc_date_format()) : $date_obj;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[PALAFITO][packing-slip-date] returning: ' . print_r($result, true));
                    }
                    return $result;
                } catch (Exception $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[PALAFITO][packing-slip-date] Exception: ' . $e->getMessage());
                    }
                    return $meta_date;
                }
            }
            return $meta_date;
        }
    }
    return $date;
}, 10, 6);

// DISABLED: Sincronización automática de fecha del albarán
// Esta funcionalidad ha sido COMPLETAMENTE DESACTIVADA para evitar conflictos.
// La fecha de entrega se gestiona ÚNICAMENTE desde el hook de cambio de estado
// en el plugin palafito-wc-extensions cuando el pedido pasa a estado "entregado".
//
// Razón: El hook wpo_wcpdf_save_document se ejecutaba en otros estados (como 'processing')
// causando que la fecha se estableciera prematuramente.
//
// La lógica de fechas ahora está centralizada en:
// wp-content/plugins/palafito-wc-extensions/class-palafito-wc-extensions.php
// Método: handle_custom_order_status_change()

/*
CÓDIGO DESACTIVADO - NO ELIMINAR (para referencia futura)

add_action('wpo_wcpdf_save_document', function($document, $order) {
    if ($document->get_type() === 'packing-slip') {
        // SOLO sincronizar si el pedido está en estado "entregado"
        if ($order->get_status() === 'entregado') {
            $date_obj = $document->get_date();
            if ($date_obj instanceof WC_DateTime) {
                $timestamp = $date_obj->getTimestamp();
                update_post_meta($order->get_id(), '_wcpdf_packing-slip_date', $timestamp);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[PALAFITO][template-functions] Syncing date for entregado order: ' . $order->get_id() . ' timestamp: ' . $timestamp);
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[PALAFITO][template-functions] Skipping sync for order ' . $order->get_id() . ' in status: ' . $order->get_status());
            }
        }
    }
}, 10, 2);

FIN CÓDIGO DESACTIVADO */
