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

// Forzar que la fecha del albarán (packing slip) sea siempre la del metabox personalizado
add_filter('wpo_wcpdf_packing-slip_date', function($date, $document_type, $order, $context, $formatted, $document) {
    if ($order && is_object($order)) {
        $order_id = is_callable([$order, 'get_id']) ? $order->get_id() : $order->ID;
        $meta_date = get_post_meta($order_id, '_wcpdf_packing_slip_date', true);
        if ($meta_date) {
            if (class_exists('WC_DateTime')) {
                try {
                    $date_obj = new WC_DateTime($meta_date);
                    return $formatted ? $date_obj->date_i18n(wc_date_format()) : $date_obj;
                } catch (Exception $e) {
                    return $meta_date;
                }
            }
            return $meta_date;
        }
    }
    return $date;
}, 10, 6);
