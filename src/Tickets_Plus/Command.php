<?php
namespace Tribe\CLI\Tickets_Plus;

/**
 * Class Command
 *
 * @since 0.1.0
 */
class Command extends \WP_CLI_Command {

	/**
	 * @var Generator\WooCommerce\CLI
	 */
	protected $wc_generator;

	/**
	 * Command constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Generator\WooCommerce\CLI $wc_generator
	 */
	public function __construct( Generator\WooCommerce\CLI $wc_generator ) {
		parent::__construct();
		$this->wc_generator = $wc_generator;
	}

	/**
	 * Generates WooCommerce orders for a WooCommerce ticketed post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : orders will be attached to this post
	 *
	 * [--count=<count>]
	 * : the number of orders to generate
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--tickets_min=<tickets_min>]
	 * : the minimum number of tickets per order
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--tickets_max=<tickets_max>]
	 * : the maximum number of tickets per order
	 * ---
	 * default: 3
	 * ---
	 *
	 * [--ticket_status=<ticket_status>]
	 * : the order status of the tickets
	 * ---
	 * default: random
	 * options:
	 *      - completed
	 *      - processing
	 *      - failed
	 *      - random
	 * ---
	 *
	 * [--ticket_id=<ticket_id>]
	 * : the ID of the ticket orders should be assigned to
	 *
	 * [--no_create_users]
	 * : use available subscribers to make orders and avoid creating users
	 *
	 * ## EXAMPLES
	 *
	 *      wp tribe event-tickets-plus generate-wc-orders 23
	 *      wp tribe event-tickets-plus generate-wc-orders 23 --count=89
	 *      wp tribe event-tickets-plus generate-wc-orders 23 --tickets_min=3
	 *      wp tribe event-tickets-plus generate-wc-orders 23 --tickets_min=3 --tickets_max=10
	 *      wp tribe event-tickets-plus generate-wc-orders 23 --tickets_min=3 --tickets_max=10 --ticket_status=no
	 *      wp tribe event-tickets-plus generate-wc-orders 23 --ticket_id=89
	 *      wp tribe event-tickets-plus generate-wc-orders 23 --ticket_id=89 --create_users=no
	 *
	 * @subcommand generate-wc-orders
	 *
	 * @since 0.1.0
	 */
	public function generate_wc_orders( array $args = null, array $assoc_args = null ) {
		$this->wc_generator->generate_orders( $args, $assoc_args );
	}

	/**
	 * Removes all WC orders from a WooCommerce ticketed post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : orders will be removed from this ticketed post
	 *
	 * [--ticket_id=<ticket_id>]
	 * : only remove orders for this WooCommerce ticket attached to the post
	 *
	 * ## EXAMPLES
	 *
	 *      wp tribe event-tickets-plus reset-wc-orders 23
	 *      wp tribe event-tickets-plus reset-wc-orders 23 --ticket_id=89
	 *
	 * @subcommand reset-wc-orders
	 *
	 * @since 0.1.0
	 */
	public function reset_wc_orders( array $args = null, array $assoc_args = null ) {
		$this->wc_generator->reset_orders( $args, $assoc_args );
	}
}
