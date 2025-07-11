<?php
/**
 * @license WTFPL
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types = 1);

namespace WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf;

use WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Driver\DriverInterface;
use WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Driver\DefaultDriver;
use WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Source\SourceInterface;
use WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Source\FileSource;
use WPO\WC\PDF_Invoices_Pro\Vendor\iio\libmergepdf\Source\RawSource;

/**
 * Merge existing pdfs into one
 *
 * Note that your PDFs are merged in the order that you add them
 */
final class Merger
{
    /**
     * @var SourceInterface[] List of pdf sources to merge
     */
    private $sources = [];

    /**
     * @var DriverInterface
     */
    private $driver;

    public function __construct(DriverInterface $driver = null)
    {
        $this->driver = $driver ?: new DefaultDriver;
    }

    /**
     * Add raw PDF from string
     */
    public function addRaw(string $content, PagesInterface $pages = null): void
    {
        $this->sources[] = new RawSource($content, $pages);
    }

    /**
     * Add PDF from file
     */
    public function addFile(string $filename, PagesInterface $pages = null): void
    {
        $this->sources[] = new FileSource($filename, $pages);
    }

    /**
     * Add files using iterator
     *
     * @param iterable<string> $iterator Set of filenames to add
     * @param PagesInterface $pages Optional pages constraint used for every added pdf
     */
    public function addIterator(iterable $iterator, PagesInterface $pages = null): void
    {
        foreach ($iterator as $filename) {
            $this->addFile($filename, $pages);
        }
    }

    /**
     * Merges loaded PDFs
     */
    public function merge(): string
    {
        return $this->driver->merge(...$this->sources);
    }

    /**
     * Reset internal state
     */
    public function reset(): void
    {
        $this->sources = [];
    }
}
