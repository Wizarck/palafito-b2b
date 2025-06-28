<?php
/**
 * @license MIT
 *
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

/*
 * This file is part of Guzzle Factory.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WPO\WC\PDF_Invoices_Pro\Vendor\GrahamCampbell\GuzzleFactory;

use WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\Client;
use WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\Exception\ConnectException;
use WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\Exception\TransferException;
use WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\HandlerStack;
use WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\Middleware;
use WPO\WC\PDF_Invoices_Pro\Vendor\Psr\Http\Message\RequestInterface;
use WPO\WC\PDF_Invoices_Pro\Vendor\Psr\Http\Message\ResponseInterface;

/**
 * This is the guzzle factory class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
final class GuzzleFactory
{
    /**
     * The default connect timeout.
     *
     * @var int
     */
    const CONNECT_TIMEOUT = 10;

    /**
     * The default transport timeout.
     *
     * @var int
     */
    const TIMEOUT = 15;

    /**
     * The default backoff multiplier.
     *
     * @var int
     */
    const BACKOFF = 1000;

    /**
     * The default 4xx retry codes.
     *
     * @var int[]
     */
    const CODES = [429];

    /**
     * Create a new guzzle client.
     *
     * @param array      $options
     * @param int|null   $backoff
     * @param int[]|null $codes
     *
     * @return \WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\Client
     */
    public static function make(array $options = [], int $backoff = null, array $codes = null)
    {
        $config = array_merge(['connect_timeout' => self::CONNECT_TIMEOUT, 'timeout' => self::TIMEOUT], $options);
        $config['handler'] = self::handler($backoff, $codes, $options['handler'] ?? null);

        return new Client($config);
    }

    /**
     * Create a new guzzle handler stack.
     *
     * @param int|null                      $backoff
     * @param int[]|null                    $codes
     * @param \WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\HandlerStack|null $stack
     *
     * @return \WPO\WC\PDF_Invoices_Pro\Vendor\GuzzleHttp\HandlerStack
     */
    public static function handler(int $backoff = null, array $codes = null, HandlerStack $stack = null)
    {
        $stack = $stack ?: HandlerStack::create();

        $stack->push(Middleware::retry(function ($retries, RequestInterface $request, ResponseInterface $response = null, TransferException $exception = null) use ($codes) {
            return $retries < 3 && ($exception instanceof ConnectException || ($response && ($response->getStatusCode() >= 500 || in_array($response->getStatusCode(), $codes === null ? self::CODES : $codes, true))));
        }, function ($retries) use ($backoff) {
            return (int) pow(2, $retries) * ($backoff === null ? self::BACKOFF : $backoff);
        }));

        return $stack;
    }
}
