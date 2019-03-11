<?php
namespace Tribe\CLI\Service_Providers;

/**
 * Class Tickets_Plus
 *
 * @since 0.1.0
 *
 */
class Tickets_Plus extends Base {

	/**
	 * Minimum required version of Event Tickets Plus
	 */
	const REQUIRED_TICKETS_PLUS_VERSION = '4.5.0';

	/**
	 * Returns each plugin required by this one to run
	 *
	 * @since 0.1.0
	 *
	 * @return array {
	 *      List of required plugins.
	 *
	 * @param string $short_name   Shortened title of the plugin
	 * @param string $class        Main PHP class
	 * @param string $thickbox_url URL to download plugin
	 * @param string $min_version  Optional. Minimum version of plugin needed.
	 * @param string $ver_compare  Optional. Constant that stored the currently active version.
	 *                             }
	 */
	protected function get_requisite_plugins() {
		return array(
			array(
				'short_name'        => 'Event Tickets Plus',
				'class'             => 'Tribe__Tickets_Plus__Main',
				'external_download' => true,
				'thickbox_url'      => 'https://theeventscalendar.com/product/wordpress-event-tickets-plus/',
				'min_version'       => self::REQUIRED_TICKETS_PLUS_VERSION,
				'ver_compare'       => 'Tribe__Tickets_Plus__Main::VERSION',
			),
		);
	}

	/**
	 * Returns the display name of this functionality.
	 *
	 * @return string
	 */
	protected function get_display_name() {
		return 'Event Tickets Plus WP-CLI Tools';
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 0.1.0
	 */
	public function register() {
		if ( ! $this->should_run() ) {
			// Display notice indicating which plugins are required
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'event-tickets-plus', $this->container->make( 'Tribe\\CLI\\Tickets_Plus\\Command' ), array( 'shortdesc' => $this->get_display_name() ) );
		}

		// avoid sending emails for fake orders
		add_filter( 'woocommerce_email_classes', array( $this, 'filter_woocommerce_email_classes' ), 999 );
	}

	/**
	 * Filters the classes of emails WooCommerce will send to avoid sending tickets confirmations
	 * for generated tickets.
	 *
	 * @since 0.1.0
	 *
	 * @param array $classes An array of classes that WooCommerce will call to send confirmation emails.
	 *
	 * @return array The filtered classes array
	 */
	public function filter_woocommerce_email_classes( $classes ) {
		unset( $classes['Tribe__Tickets__Woo__Email'] );

		return $classes;
	}
}
