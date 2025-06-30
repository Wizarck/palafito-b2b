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
