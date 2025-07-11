<?php

/**
 * The renderer interface.
 *
 * @package WooCommerce\PayPalCommerce\AdminNotices\Renderer
 */
declare (strict_types=1);
namespace WooCommerce\PayPalCommerce\AdminNotices\Renderer;

/**
 * Interface RendererInterface
 */
interface RendererInterface {

	/**
	 * Renders the messages.
	 *
	 * @return bool
	 */
	public function render(): bool;
	/**
	 * Enqueues common assets required for the admin notice behavior.
	 *
	 * @return void
	 */
	public function enqueue_admin(): void;
}
