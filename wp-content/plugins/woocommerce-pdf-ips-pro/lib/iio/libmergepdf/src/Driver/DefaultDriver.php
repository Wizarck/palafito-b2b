<?php
/**
 * @license WTFPL
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types = 1);

namespace WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Driver;

use WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Source\SourceInterface;

final class DefaultDriver implements DriverInterface
{
    /**
     * @var DriverInterface
     */
    private $wrapped;

    public function __construct(DriverInterface $wrapped = null)
    {
        $this->wrapped = $wrapped ?: new Fpdi2Driver;
    }

    public function merge(SourceInterface ...$sources): string
    {
        return $this->wrapped->merge(...$sources);
    }
}
