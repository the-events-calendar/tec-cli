<?php
namespace Tribe\CLI\Payouts\Generator;

use Tribe\CLI\Meta_Keys;
use Tribe\CLI\WooCommerce\Orders_Generator;
use Tribe__Events__Community__Tickets__Main;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Main;
use WP_CLI;

/**
 * Class CLI
 *
 * @since TBD
 */
class CLI {

	/**
	 * @var Orders_Generator
	 */
	protected $orders_generator;

	/**
	 * Array of generated users
	 *
	 * @var array $users
	 */
	protected $users = [];

	/**
	 * CLI constructor.
	 *
	 * @since TBD
	 *
	 * @param Orders_Generator $orders_generator
	 */
	public function __construct( Orders_Generator $orders_generator ) {
		$this->orders_generator = $orders_generator;

		$wc_emails = \WC_Emails::instance();

		foreach ( $wc_emails->get_emails() as $email_id => $email ) {
			$key   = 'woocommerce_' . $email->id . '_settings';
			$value = get_option( $key );

			if ( isset( $value['enabled'] ) ) {
				$value['enabled'] = 'no';

				update_option( $key, $value );
			}
		}
	}

	/**
	 * Generate WC orders with payouts
	 *
	 * @since TBD
	 *
	 * @param array $generator_args
	 * @param array $assoc_args
	 * @return void
	 */
	public function generate_payouts( array $generator_args = null, array $assoc_args = null ) {
		$post_id    = $generator_args[0];
		$post       = get_post( absint( $post_id ) );
		$counts_sum = 0;

		if ( empty( $post ) ) {
			WP_CLI::error( sprintf( __( 'Post with ID %d does not exist.', 'tribe-cli' ), $post_id ) );
		}

		$count = empty( $assoc_args['count'] ) ? 1 : $assoc_args['count'];

		if ( ! filter_var( $count, FILTER_VALIDATE_INT ) || (int) $count <= 0 ) {
			WP_CLI::error( __( 'Count should be a numeric value greater than 0', 'tribe-cli' ) );
		}

		if (
			empty( $assoc_args['tickets_min'] )
			|| ! filter_var( $assoc_args['tickets_min'], FILTER_VALIDATE_INT )
			|| (int) $assoc_args['tickets_min'] <= 0
		) {
			$assoc_args['tickets_min'] = 1;
		}

		if (
			empty( $assoc_args['tickets_max'] )
			|| ! filter_var( $assoc_args['tickets_max'], FILTER_VALIDATE_INT )
			|| (int) $assoc_args['tickets_max'] <= 0
			|| (int) $assoc_args['tickets_max'] < $assoc_args['tickets_min']
		) {
			$assoc_args['tickets_max'] = $assoc_args['tickets_min'];
		}

		$legit_stati = [ 'paid', 'pending', 'failed' ];

		if ( empty( $assoc_args['status'] ) ) {
			$assoc_args['status'] = 'random';
		} elseif ( ! in_array( $assoc_args['status'], $legit_stati, true ) ) {
			WP_CLI::error( __( 'Status must be a valid Payouts status or be omitted.', 'tribe-cli' ) );
		}

		$order_stati = [
			'paid' => 'completed',
			'pending' => 'processing',
			'failed' => 'failed',
		];

		// Generate orders
		$tickets      = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
		$post_tickets = $tickets->get_tickets_ids( $post_id );

		if (
			isset( $assoc_args['ticket_id'] )
			&& (
				! filter_var( $assoc_args['ticket_id'], FILTER_VALIDATE_INT )
				|| ! in_array( $assoc_args['ticket_id'], $post_tickets, true )
			)
		) {
			WP_CLI::error( __( 'The specified ticket ID does not exist, is not associated to the specified event or is not a valid value.' ) );
		}

		$create_users = isset( $assoc_args['no_create_users'] ) ? falswe  : true;

		$post_tickets = isset( $assoc_args['ticket_id'] )
			? [ (int) $assoc_args['ticket_id'] ]
			: $post_tickets;

		if ( empty( $post_tickets ) ) {
			WP_CLI::error( __( 'The specified post should have at least one WooCommerce ticket assigned.', 'tribe-cli' ) );
		}

		$tickets_min   = (int) $assoc_args['tickets_min'];
		$tickets_max   = (int) $assoc_args['tickets_max'];
		$assoc_args['ticket_status'] = empty( $assoc_args['ticket_status'] ) ? 'random' : $assoc_args['ticket_status'];
		$ticket_status = 'random' === $assoc_args['ticket_status']
			? $legit_stati[ array_rand( $legit_stati ) ]
			: $assoc_args['ticket_status'];

		$counts = [];
		for ( $i = 0; $i < $count; $i ++ ) {
			$counts[ $i ] = random_int( $tickets_min, $tickets_max );
		}

		$counts_sum = array_sum( $counts );

		$progress_bar = WP_CLI\Utils\make_progress_bar(
			sprintf( __( 'Generating %1$d ticket orders for post %2$d', 'tribe-cli' ), $counts_sum, $post_id ), $counts_sum
		);

		$generator_args = [
			'create_users' => $create_users,
			'payment_method' => 'PayPal',
		];

		add_filter( 'tribe_community_tickets_add_fee_to_all_tickets', '__return_true' );
		add_filter( 'tribe_ticket_generation_delay', function( $delay, $order_id) { return 'now'; }, 20, 2 );
		foreach ( $counts as $n => $tickets_count ) {
			$this_args = $generator_args;

			$this_args['ticket_status'] = $ticket_status;
			$this_args['tickets_min']   = $tickets_count;
			$this_args['tickets_max']   = $tickets_count;

			$ticket_id     = $post_tickets[ array_rand( $post_tickets ) ];
			$ticket_status = ! empty( $ticket_status ) ? $ticket_status : $legit_stati[ array_rand( $legit_stati ) ];

			$order_ids = $this->orders_generator->generate_orders( 1, [ $ticket_id ], $this_args );

			$progress_bar->tick( $tickets_count );
		}
		add_filter( 'tribe_community_tickets_add_fee_to_all_tickets', '__return_false' );

		// Payouts are generated automatically when the orders are
		WP_CLI::success( sprintf( __( 'Generated %1$d Orders with Payouts for post %2$d', 'tribe-cli' ), $counts_sum, $post_id ) );
	}

	/**
	 * Remove payouts, orders, generated users, and attendees/tickets for a specified event
	 *
	 * @since TBD
	 *
	 * @param array $args An array of arguments, most importantly the post ID.
	 * @param array $assoc_args
	 */
	public function reset_payouts( array $args = null, array $assoc_args = null ) {
		$post_id = $args[0];
		$post    = get_post( absint( $post_id ) );

		if ( empty( $post ) ) {
			WP_CLI::error( sprintf( __( 'Post with ID %d does not exist.', 'tribe-cli' ), $post_id ) );
		}

		$tickets      = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
		$post_tickets = $tickets->get_tickets_ids( $post_id );

		if ( empty( $post_tickets ) ) {
			WP_CLI::error( __( 'The specified post should have at least one WooCommerce ticket assigned.', 'tribe-cli' ) );
		}

		if ( isset( $assoc_args['ticket_id'] ) && ( ! filter_var( $assoc_args['ticket_id'],
					FILTER_VALIDATE_INT ) || ! get_post( $assoc_args['ticket_id'] ) ) ) {
			WP_CLI::error( __( 'The specified ticket ID does not exist.', 'tribe-cli' ) );
		}

		if ( isset( $assoc_args['ticket_id'] ) && ! in_array( $assoc_args['ticket_id'], $post_tickets, true ) ) {
			WP_CLI::error( __( 'The specified ticket ID is not assigned to the specified post.', 'tribe-cli' ) );
		}

		$post_tickets = isset( $assoc_args['ticket_id'] ) ? [ (int) $assoc_args['ticket_id'] ] : $post_tickets;

		$attendees = $tickets->get_attendees_by_id( $post_id );

		$progress_bar = WP_CLI\Utils\make_progress_bar(
			sprintf( __( 'Deleting WooCommerce tickets orders.', 'tribe-cli' ) ),
			count( $attendees )
		);

		// delete generated orders by ticket/attendee
		foreach ( $post_tickets as $ticket ) {
			$this_ticket_attendees = array_filter(
				$attendees, function ( array $attendee ) use ( $ticket ) {
					$for_this_ticket = isset( $attendee['product_id'] ) && $attendee['product_id'] == $ticket;
					$generated       = ! empty( get_post_meta( $attendee['order_id'], Meta_Keys::$generated_meta_key, true ) );

					return $for_this_ticket && $generated;
				}
			);

			foreach ( $this_ticket_attendees as $attendee ) {
				wp_delete_post( $attendee['order_id'], true );
				$this->delete_payout( $attendee['order_id'] );

				$progress_bar->tick();
			}
		}
		$progress_bar->finish();

		// Due to optional delayed generation, attendees may not have been created yet.
		global $wpdb;
		$order_ids_for_event = [];
		foreach ( $post_tickets as $ticket ) {
			$sql = $wpdb->prepare( "
						SELECT DISTINCT order_item.order_id
						FROM {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta,
								{$wpdb->prefix}woocommerce_order_items as order_item,
								{$wpdb->prefix}posts as p
						WHERE  order_item.order_item_id = order_item_meta.order_item_id
						AND order_item.order_id = p.ID
						AND order_item_meta.meta_key LIKE '_product_id'
						AND order_item_meta.meta_value = '%s'
						ORDER BY order_item.order_item_id DESC
						",
						$ticket
					);

			$order_ids = $wpdb->get_results( $sql );

			foreach ( $order_ids as $order_id ) {
				$order_ids_for_event[] = $order_id->order_id;
			}
		}

		if ( ! empty( $order_ids_for_event ) ) {
			$progress_bar = WP_CLI\Utils\make_progress_bar(
				sprintf( __( 'Deleting "orphan" generated orders.', 'tribe-cli' ) ),
				count( $order_ids_for_event )
			);

			foreach ( $order_ids_for_event as $order_id ) {
				if ( ! get_post_meta( $order_id, '_tribe_cli_generated', true ) ) {
					continue;
				}

				$this->delete_payout( $order_id );

				$WC_Order = new \WC_Order($order_id);
				$WC_Order->delete( true );

				$progress_bar->tick();
			}

			$progress_bar->finish();
		}

		$user_query = new \WP_User_Query( [
			'role'       => 'customer',
			'meta_key'   => Meta_Keys::$generated_meta_key,
			'meta_value' => 1,
			'fields'     => 'ID',
			'paged'      => false,
		] );

		$users = $user_query->get_results();

		if ( ! empty( $users ) ) {
			$progress_bar = WP_CLI\Utils\make_progress_bar(
				sprintf( __( 'Deleting the generated users.', 'tribe-cli' ) ),
				count( $users )
			);

			foreach ( $users as $user_id ) {
				wp_delete_user( $user_id );
				$progress_bar->tick();
			}

			$progress_bar->finish();
		}

	}

	/**
	 * Deletes payouts associated with an order
	 *
	 * @since TBD
	 *
	 * @param int $order_id
	 */
	public function delete_payout( $order_id ) {
		$repository = tribe_payouts();
		$repository->by( 'order', $order_id );
		$repository->set_found_rows( true );
		$found = $repository->found();

		if ( 0 >= $found ) {
			return;
		}

		$ids = $repository->get_ids();

		foreach( $ids as $id ) {
			$repository->delete( $id );
		}
	}

	/**
	 * Generates a number of orders for the specified WooCommerce product IDs.
	 *
	 * @since TBD
	 */
	public function generate_orders( $count, array $product_ids, array $args ) {
		/** @var WooCommerce $woocommerce */
		global $woocommerce;
		$order_ids = [];

		set_time_limit( 0 );

		$woocommerce->init();
		$woocommerce->frontend_includes();

		// Class instances
		require_once WC()->plugin_path() . '/includes/abstracts/abstract-wc-session.php';
		$woocommerce->session  = new \WC_Session_Handler();
		$woocommerce->cart     = new \WC_Cart();                                    // Cart class, stores the cart contents
		$woocommerce->customer = new \WC_Customer();                                // Customer class, handles data such as customer location

		$woocommerce->countries = new \WC_Countries();
		$woocommerce->checkout  = new \WC_Checkout();
		//$woocommerce->product_factory = new WC_Product_Factory();                      // Product Factory to create new product instances
		$woocommerce->order_factory = new \WC_Order_Factory();                        // Order Factory to create new order instances
		$woocommerce->integrations  = new \WC_Integrations();                         // Integrations class


		// clear cart
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		$woocommerce->cart->empty_cart();

		for ( $x = 0; $x < $count; $x ++ ) {
			$order_min_quantity = $args['tickets_min'];
			$order_max_quantity = $args['tickets_max'];
			$num_products       = mt_rand( $order_min_quantity, $order_max_quantity );
			$create_user        = isset( $args['create_users'] ) ? (bool) $args['create_users'] : true;

			if ( $create_user ) {
				$user_id = $this->create_user();
			} else {
				$user_id = $this->get_random_user();
			}

			// add random products to cart
			for ( $i = 0; $i < $num_products; $i ++ ) {
				$idx        = mt_rand( 0, count( $product_ids ) - 1 );
				$product_id = $product_ids[ $idx ];
				$woocommerce->cart->add_to_cart( $product_id, 1 );
			}

			$available_gateways = $woocommerce->payment_gateways()->get_available_payment_gateways();
			$payment_method     = emnpty($args['gateway'] ) ? array_rand( $available_gateways ) : $args['gateway'];

			// process checkout
			$data     = [
				'billing_country'    => get_user_meta( $user_id, 'billing_country', true ),
				'billing_first_name' => get_user_meta( $user_id, 'billing_first_name', true ),
				'billing_last_name'  => get_user_meta( $user_id, 'billing_last_name', true ),
				'billing_company'    => '',
				'billing_address_1'  => get_user_meta( $user_id, 'billing_address_1', true ),
				'billing_address_2'  => '',
				'billing_city'       => get_user_meta( $user_id, 'billing_city', true ),
				'billing_state'      => get_user_meta( $user_id, 'billing_state', true ),
				'billing_postcode'   => get_user_meta( $user_id, 'billing_postcode', true ),
				'billing_email'      => get_user_meta( $user_id, 'billing_email', true ),
				'billing_phone'      => get_user_meta( $user_id, 'billing_phone', true ),

				'payment_method' => $payment_method,

				'shipping_country'    => get_user_meta( $user_id, 'shipping_country', true ),
				'shipping_first_name' => get_user_meta( $user_id, 'shipping_first_name', true ),
				'shipping_last_name'  => get_user_meta( $user_id, 'shipping_last_name', true ),
				'shipping_company'    => '',
				'shipping_address_1'  => get_user_meta( $user_id, 'shipping_address_1', true ),
				'shipping_address_2'  => '',
				'shipping_city'       => get_user_meta( $user_id, 'shipping_city', true ),
				'shipping_state'      => get_user_meta( $user_id, 'shipping_state', true ),
				'shipping_postcode'   => get_user_meta( $user_id, 'shipping_postcode', true ),
				'shipping_email'      => get_user_meta( $user_id, 'shipping_email', true ),
				'shipping_phone'      => get_user_meta( $user_id, 'shipping_phone', true ),
			];
			$checkout = new \WC_Checkout();

			$woocommerce->cart->calculate_totals();

			$order_id = $checkout->create_order( $data );

			if ( $order_id ) {
				update_post_meta( $order_id, '_payment_method', 'bacs' );
				update_post_meta( $order_id, '_payment_method_title', 'Bacs' );

				update_post_meta( $order_id, '_shipping_method', 'free_shipping' );
				update_post_meta( $order_id, '_shipping_method_title', 'Free Shipping' );

				update_post_meta( $order_id, '_customer_user', absint( $user_id ) );
				update_post_meta( $order_id, Meta_Keys::$generated_meta_key, 1 );

				foreach ( $data as $key => $value ) {
					update_post_meta( $order_id, '_' . $key, $value );
				}

				do_action( 'woocommerce_checkout_order_processed', $order_id, $data );
				// This triggers both attendee and payout generation!
				do_action( 'woocommerce_order_status_changed', $order_id );

				$order = new \WC_Order( $order_id );

				$status = $args['status'];

				// avoid sending emails
				update_post_meta( $order_id, '_tribe_mail_sent', true );

				if ( $status == 'failed' ) {
					$order->update_status( $status );
				} else {
					$order->payment_complete();
					$order->update_status( $status );
				}

				$order_ids[] = $order_id;
			}

			// clear cart
			$woocommerce->cart->empty_cart();
		}

		return $order_ids;
	}

	/**
	 * Inserts a random customer user in the database.
	 *
	 * @since TBD
	 *
	 * @return int|\WP_Error
	 */
	public function create_user() {
		$faker = Faker\Factory::create();
		$faker->addProvider( new Faker\Provider\en_US\Address( $faker ) );
		$faker->addProvider( new Faker\Provider\en_US\PhoneNumber( $faker ) );

		//set user from Faker Press and use in all fields to keep the user the same
		$user_default = [
			'login'      => $faker->userName,
			'email'      => $faker->email,
			'first_name' => $faker->firstName,
			'last_name'  => $faker->lastName,
			'country'    => $faker->country,
			'address_1'  => $faker->streetAddress,
			'city'       => $faker->city,
			'state'      => $faker->state,
			'postcode'   => $faker->postcode,
			'phone'      => $faker->phoneNumber,
		];

		$user = [
			'user_login' => $user_default['login'],
			'user_pass'  => 'password',
			'user_email' => $user_default['email'],
			'first_name' => $user_default['first_name'],
			'last_name'  => $user_default['last_name'],
			'role'       => 'customer',
		];

		$user_id = wp_insert_user( $user );

		// billing/shipping address
		$meta = [
			'billing_country'                          => $user_default['country'],
			'billing_first_name'                       => $user_default['first_name'],
			'billing_last_name'                        => $user_default['last_name'],
			'billing_address_1'                        => $user_default['address_1'],
			'billing_city'                             => $user_default['city'],
			'billing_state'                            => $user_default['state'],
			'billing_postcode'                         => $user_default['postcode'],
			'billing_email'                            => $user_default['email'],
			'billing_phone'                            => $user_default['phone'],
			'shipping_country'                         => $user_default['country'],
			'shipping_first_name'                      => $user_default['first_name'],
			'shipping_last_name'                       => $user_default['last_name'],
			'shipping_address_1'                       => $user_default['address_1'],
			'shipping_city'                            => $user_default['city'],
			'shipping_state'                           => $user_default['state'],
			'shipping_postcode'                        => $user_default['postcode'],
			'shipping_email'                           => $user_default['email'],
			'shipping_phone'                           => $user_default['phone'],
			Meta_Keys::$generated_meta_key => 1,
		];

		foreach ( $meta as $key => $value ) {
			update_user_meta( $user_id, $key, $value );
		}

		return $user_id;
	}

	/**
	 * Returns the ID of a random subscriber user from the database.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_random_user() {
		if ( ! $this->users ) {
			$this->users = get_users( [ 'role' => 'Subscriber', 'fields' => 'ID' ] );
		}

		if ( empty( $this->users ) ) {
			WP_CLI::error( __( 'At least on Subscriber user must exist if not creating users.', 'tribe-cli' ) );
		}

		$length = count( $this->users );
		$idx    = rand( 0, $length - 1 );

		return $this->users[ $idx ];
	}
}
