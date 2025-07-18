<?php

/**
 * NameConstraints
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
 * NameConstraints
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class NameConstraints
{
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'permittedSubtrees' => [
                'constant' => 0,
                'optional' => true,
                'implicit' => true
            ] + GeneralSubtrees::MAP,
            'excludedSubtrees' => [
                'constant' => 1,
                'optional' => true,
                'implicit' => true
            ] + GeneralSubtrees::MAP
        ]
    ];
}
