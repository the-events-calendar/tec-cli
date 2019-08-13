<?php

namespace Tribe\CLI\Service_Providers;

use Tribe\CLI\Payouts\Command;

/**
 * Class Payouts
 *
 * @since TBD
 *
 */
class Payouts extends Base {

	/**
	 * Minimum required version of Event Tickets Plus
	 */
	const REQUIRED_COMMUNITY_TICKETS_VERSION = '4.6.0';

	/**
	 * Returns each plugin required by this one to run
	 *
	 * @since TBD
	 *
	 * @return array {
	 *      List of required plugins.
	 *
	 *      @type string $short_name   Shortened title of the plugin.
	 *      @type string $class        Main PHP class.
	 *      @type string $thickbox_url URL to download plugin.
	 *      @type string $min_version  Optional. Minimum version of plugin needed.
	 *      @type string $ver_compare  Optional. Constant that stored the currently active version.
	 * }
	 */
	protected function get_requisite_plugins() {
		return [
			[
				'short_name'        => 'The Events Calendar: Community Events Tickets',
				'class'             => 'Tribe__Events__Community__Tickets__Main',
				'external_download' => true,
				'thickbox_url'      => 'https://theeventscalendar.com/product/community-tickets/',
				'min_version'       => self::REQUIRED_COMMUNITY_TICKETS_VERSION,
				'ver_compare'       => 'Tribe__Events__Community__Tickets__Main::VERSION',
			],
		];
	}

	/**
	 * Returns the display name of this functionality.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_display_name() {
		return 'Tribe Payouts WP-CLI Tools';
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		if ( ! $this->should_run() ) {
			// Display notice indicating which plugins are required
			add_action( 'admin_notices', [ $this, 'admin_notices' ] );

			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'tribe payouts', $this->container->make( Command::class ), array( 'shortdesc' => $this->get_display_name() ) );

			// avoid sending emails for fake orders
			add_filter( 'woocommerce_email_classes', array( $this, 'filter_woocommerce_email_classes' ), 999 );
		}

	}

	/**
	 * Filters the classes of emails WooCommerce will send to avoid sending tickets confirmations
	 * for generated tickets.
	 *
	 * @since TBD
	 *
	 * @param array $classes An array of classes that WooCommerce will call to send confirmation emails.
	 *
	 * @return array The filtered classes array
	 */
	public function filter_woocommerce_email_classes( $classes ) {
		unset( $classes['Tribe__Tickets__Woo__Email'] );

		return [];
	}
}
