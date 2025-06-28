<?php
/**
 * @license WTFPL
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types = 1);

namespace WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Source;

use WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\PagesInterface;

interface SourceInterface
{
    /**
     * Get name of file or source
     */
    public function getName(): string;

    /**
     * Get pdf content
     */
    public function getContents(): string;

    /**
     * Get pages to fetch from source
     */
    public function getPages(): PagesInterface;
}
