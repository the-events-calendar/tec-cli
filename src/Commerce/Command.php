<?php

/**
 * Class Tribe__Cli__Commerce__Command
 *
 * @since 0.1.0
 */
class Tribe__Cli__Commerce__Command extends WP_CLI_Command {

	/**
	 * @var \Tribe__Cli__Commerce__Generator__PayPal__CLI
	 */
	protected $paypal;

	/**
	 * Tribe__CLI__Commerce__Command constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param \Tribe__Cli__Tickets__Generator__RSVP__CLI $paypal
	 */
	public function __construct( Tribe__Cli__Commerce__Generator__PayPal__CLI $paypal ) {
		parent::__construct();
		$this->paypal = $paypal;
	}

	/**
	 * Generates PayPal orders for a ticketed post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : PayPal orders will be attached to this post
	 *
	 * [--count=<count>]
	 * : the number of PayPal orders to generate
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--attendees_min=<attendees_min>]
	 * : the minimum number of attendees per PayPal order
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--attendees_max=<attendees_max>]
	 * : the maximum number of attendees per PayPal order
	 * ---
	 * default: 3
	 * ---
	 *
	 * [--order_status=<order_status>]
	 * : the status of the PayPal orders
	 * ---
	 * default: completed
	 * options:
	 *      - completed
	 *      - pending
	 *      - denied
	 *      - refunded
	 * ---
	 *
	 * [--ticket_id=<ticket_id>]
	 * : the ID of the ticket PayPal orders should be generated for
	 *
	 * ## EXAMPLES
	 *
	 *      wp commerce generate-paypal-orders 23
	 *      wp commerce generate-paypal-orders 23 --count=89
	 *      wp commerce generate-paypal-orders 23 --attendees_min=3
	 *      wp commerce generate-paypal-orders 23 --attendees_min=3 --attendees_max=10
	 *      wp commerce generate-paypal-orders 23 --attendees_min=3 --attendees_max=10 --order_status=denied
	 *      wp commerce generate-paypal-orders 23 --ticket_id=89
	 *
	 * @subcommand generate-paypal-orders
	 *
	 * @since 0.1.0
	 */
	public function generate_paypal_orders( array $args = null, array $assoc_args = null ) {
		$this->paypal->generate_orders( $args, $assoc_args );
	}
}