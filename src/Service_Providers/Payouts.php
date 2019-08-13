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
			\WP_CLI::add_command( 'tribe payouts', $this->container->make( Command::class ), [ 'shortdesc' => $this->get_display_name() ] );
		} else {
			return;
		}

		// avoid sending emails for fake orders
		add_filter( 'woocommerce_email_classes', [ $this, 'filter_woocommerce_email_classes' ], 999 );
		add_action( 'woocommerce_email', 'unhook_all_wc_emails' );
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

		return $classes;
	}

	/**
	 * Unhook all WC emails - we manipulate order status a lot - it creates a lot of emails.
	 * Taken straight from the WC documentation: https://docs.woocommerce.com/document/unhookremove-woocommerce-emails/
	 *
	 * @since TBD
	 *
	 * @param \WC_Emails $email_class Email object.
	 */
	public function unhook_all_wc_emails( $email_class ) {
		/**
		 * Hooks for sending emails during store events
		 **/
		remove_action( 'woocommerce_low_stock_notification', [ $email_class, 'low_stock' ] );
		remove_action( 'woocommerce_no_stock_notification', [ $email_class, 'no_stock' ] );
		remove_action( 'woocommerce_product_on_backorder_notification', [ $email_class, 'backorder' ] );

		// New order emails
		$wc_new_order = $email_class->emails['WC_Email_New_Order'];

		remove_action( 'woocommerce_order_status_pending_to_processing_notification', [ $wc_new_order, 'trigger' ] );
		remove_action( 'woocommerce_order_status_pending_to_completed_notification', [ $wc_new_order, 'trigger' ] );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', [ $wc_new_order, 'trigger' ] );
		remove_action( 'woocommerce_order_status_failed_to_processing_notification', [ $wc_new_order, 'trigger' ] );
		remove_action( 'woocommerce_order_status_failed_to_completed_notification', [ $wc_new_order, 'trigger' ] );
		remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', [ $wc_new_order, 'trigger' ] );

		// Processing order emails
		$wc_processing_order = $email_class->emails['WC_Email_Customer_Processing_Order'];

		remove_action( 'woocommerce_order_status_pending_to_processing_notification', [
			$wc_processing_order,
			'trigger',
		] );
		remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', [
			$wc_processing_order,
			'trigger',
		] );

		// Completed order emails
		$wc_completed_order = $email_class->emails['WC_Email_Customer_Completed_Order'];

		remove_action( 'woocommerce_order_status_completed_notification', [ $wc_completed_order, 'trigger' ] );

		// Note emails
		$wc_customer_note = $email_class->emails['WC_Email_Customer_Note'];

		remove_action( 'woocommerce_new_customer_note_notification', [ $wc_customer_note, 'trigger' ] );
	}
}
