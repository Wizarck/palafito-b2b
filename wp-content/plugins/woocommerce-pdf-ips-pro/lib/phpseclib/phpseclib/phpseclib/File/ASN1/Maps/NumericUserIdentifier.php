<?php

/**
 * NumericUserIdentifier
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPO\WC\PDF_Invoices_Pro\Vendor\phpseclib3\File\ASN1\Maps;

use WPO\WC\PDF_Invoices_Pro\Vendor\phpseclib3\File\ASN1;

/**
 * NumericUserIdentifier
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class NumericUserIdentifier
{
    const MAP = ['type' => ASN1::TYPE_NUMERIC_STRING];
}
