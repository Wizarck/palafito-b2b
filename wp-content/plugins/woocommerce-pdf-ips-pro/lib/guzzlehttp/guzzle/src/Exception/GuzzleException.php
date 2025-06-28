<?php
/**
 * @license MIT
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */
namespace WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\Exception;

use Throwable;

if (interface_exists(Throwable::class)) {
    interface GuzzleException extends Throwable
    {
    }
} else {
    /**
     * @method string getMessage()
     * @method \Throwable|null getPrevious()
     * @method mixed getCode()
     * @method string getFile()
     * @method int getLine()
     * @method array getTrace()
     * @method string getTraceAsString()
     */
    interface GuzzleException
    {
    }
}
