<?php

use Tribe__Cli__Meta_Keys as Meta_Keys;
use Tribe__Tickets__Commerce__PayPal__Order as Order;
use function WP_CLI\Utils\format_items;
use function WP_CLI\Utils\make_progress_bar;

/**
 * Class Tribe__Cli__Commerce__Generator__PayPal__CLI
 *
 * @since 0.2.0
 */
class Tribe__Cli__Commerce__Generator__PayPal__CLI {

	/**
	 * @var string The current order status, a utility field
	 */
	protected $order_status;

	/**
	 * Generates the PayPal orders for a post.
	 *
	 * @since 0.2.0
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function generate_orders( array $args = array(), array $assoc_args = array() ) {
		$ticket_ids = $this->parse_ticket_ids( $args );

		list( $tickets_min, $tickets_max ) = $this->parse_attendees_min_max( $assoc_args );

		$orders_count = $this->parse_count( $assoc_args );
		$order_status = $this->parse_order_status( $assoc_args );

		$ticket_ids_list = implode( ', ', $ticket_ids );
		WP_CLI::log( "Generating {$orders_count} PayPal orders for tickets {$ticket_ids_list}" );

		$progress  = make_progress_bar( 'Generating orders', $orders_count );
		$generated = array();

		$this->hijack_request_flow();

		for ( $k = 0; $k < $orders_count; $k ++ ) {
			$user_id   = 0;
			$ticket_qty = array();
			foreach ( $ticket_ids as $ticket_id ) {
				$ticket_qty[ $ticket_id ] = mt_rand( $tickets_min, $tickets_max );
			}

			$items_data = array();
			$items_index = 1;
			$payment_gross  = 0;

			/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
			$paypal = tribe( 'tickets.commerce.paypal' );

			foreach ($ticket_ids as $ticket_id) {
				$this_ticket_qty = $ticket_qty[ $ticket_id ];
				$post_id   = (int) get_post_meta( $ticket_id, $paypal->event_key, true );

				if ( empty( $post_id ) ) {
					WP_CLI::error( "Ticket with ID {$ticket_id} is not related to a post, please edit the ticket and retry." );
				}

				if ( ! $post = get_post( $post_id ) ) {
					WP_CLI::error( "Ticket with ID {$ticket_id} is related to a post with ID {$post_id} but that post does not exist, please edit the ticket and retry." );
				}

				$ticket = $paypal->get_ticket( $post_id, $ticket_id );

				$this->backup_ticket_total_sales( $ticket_id );

				$inventory  = $ticket->inventory();
				$this_ticket_qty = -1 === $inventory ? $this_ticket_qty : min( $this_ticket_qty, (int) $inventory );

				if ( $this_ticket_qty === 0 ) {
					WP_CLI::log( "Not generating attendees for ticket {$ticket_id} as it ran out of inventory" );
					continue;
				}

				WP_CLI::log( "Generating an order for {$this_ticket_qty} attendees for ticket {$ticket_id}, current ticket inventory is {$inventory}" );
				$ticket_qty[ $ticket_id ] = $this_ticket_qty;

				$mc_gross = $ticket->price * $this_ticket_qty;
				$payment_gross += $mc_gross;

				$items_data[] = array(
					"item_name{$items_index}"   => "{$ticket->name} - {$post->post_title}",
					"item_number{$items_index}" => "{$post_id}:{$ticket_id}",
					"mc_handling{$items_index}" => '0.00',
					"mc_shipping{$items_index}" => '0.00',
					"mc_gross_{$items_index}"   => $this->signed_value( $mc_gross ),
					"tax{$items_index}"         => '0.00',
					"quantity{$items_index}"    => $this_ticket_qty,
				);
				$items_index++;
			}

			$items_data = call_user_func_array('array_merge',$items_data);

			$this->order_status = $order_status;

			$progress->tick();

			$faker = Faker\Factory::create();
			$faker->addProvider( new Faker\Provider\en_US\Address( $faker ) );

			$transaction_id = strtoupper( substr( md5( $faker->sentence ), 0, 17 ) );
			$receiver_id    = strtoupper( substr( md5( $faker->sentence ), 0, 13 ) );
			$payer_id       = strtoupper( substr( md5( $faker->sentence ), 0, 13 ) );
			$ipn_track_id   = substr( md5( $faker->sentence ), 0, 13 );

			$receiver_email = 'merchant@' . parse_url( home_url(), PHP_URL_HOST );
			$payment_date   = $faker->date( 'H:i:s M d, Y e' );

			$data = array(
				'last_name'              => $faker->lastName,
				'shipping_method'        => 'Default',
				'address_state'          => $faker->stateAbbr,
				'receiver_email'         => $receiver_email,
				'custom'                 => '{"user_id":' . $user_id . ',"tribe_handler":"tpp"}',
				'shipping_discount'      => '0.00',
				'receiver_id'            => $receiver_id,
				'charset'                => 'windows-1252',
				'payer_email'            => $faker->email,
				'protection_eligibility' => 'Eligible',
				'address_zip'            => $faker->postcode,
				'payment_fee'            => $this->signed_value( 0.09 ),
				'transaction_subject'    => '',
				'txn_id'                 => $transaction_id,
				'residence_country'      => 'US',
				'payment_status'         => ucwords( $order_status ),
				'mc_fee'                 => $this->signed_value( 0.09 ),
				'mc_gross'               => $this->signed_value( $payment_gross ),
				'insurance_amount'       => '0.00',
				'address_country'        => 'United States',
				'mc_currency'            => 'USD',
				'verify_sign'            => 'Au138tmgDC7.8B8qKvd-30AoY8IgAFfYkrYMbXOdLJmWDmKOip2XAIyQ',
				'business'               => $receiver_email,
				'address_city'           => $faker->city,
				'first_name'             => $faker->firstName,
				'address_name'           => $faker->name,
				'mc_shipping'            => '0.00',
				'notify_version'         => '3.8',
				'test_ipn'               => '1',
				'ipn_track_id'           => $ipn_track_id,
				'payment_gross'          => $this->signed_value( $payment_gross ),
				'address_country_code'   => 'US',
				'address_street'         => $faker->streetAddress,
				'payment_type'           => 'instant',
				'payer_id'               => $payer_id,
				'discount'               => '0.00',
				'payment_date'           => $payment_date,
				'mc_handling'            => '0.00',
			);

			$data = array_merge( $data, $items_data );

			if ( $order_status === Tribe__Tickets__Commerce__PayPal__Stati::$refunded ) {
				// complete the order to be refunded before the refund
				$data['payment_status'] = ucwords( Tribe__Tickets__Commerce__PayPal__Stati::$completed );
				$this->order_status     = Tribe__Tickets__Commerce__PayPal__Stati::$completed;
				$this->update_fees( $data );
				$this->generate_tickets( Tribe__Tickets__Commerce__PayPal__Stati::$completed, $data );

				$data['payment_status'] = ucwords( Tribe__Tickets__Commerce__PayPal__Stati::$refunded );
				$this->order_status     = Tribe__Tickets__Commerce__PayPal__Stati::$refunded;
				$this->update_fees( $data );
				$data['reason_code']   = 'refund';
				$data['parent_txn_id'] = $transaction_id;
				$data['txn_id']        = strtoupper( substr( md5( $faker->sentence ), 0, 17 ) );
			}

			$this->generate_tickets( $order_status, $data );

			$generated[] = array(
				'Order ID'        => $transaction_id,
				'Attendees count' => $ticket_qty,
			);
		}

		$progress->finish();
		WP_CLI::success( "Generated {$orders_count} orders for post {$post_id}" );
		format_items( 'table', $generated, array( 'Order ID', 'Attendees count' ) );
	}

	/**
	 * Parses, validating it, the user-proviced post ID.
	 *
	 * @since 0.2.0
	 *
	 * @param array $args
	 *
	 * @return int
	 *
	 * @throws \WP_CLI\ExitException
	 */
	protected function parse_post_id( array $args ) {
		$post_id = (int) $args[0];
		$post    = get_post( $post_id );

		if ( empty( $post ) ) {
			WP_CLI::error( "There is no post with an ID of {$post_id}" );
		}

		// willingly let orders be created for posts on which Tickets might not be enabled
		// but avoid nonsense
		$forbidden_post_types = array(
			Tribe__Tickets__Commerce__PayPal__Main::ATTENDEE_OBJECT,
		);

		if ( in_array( $post->post_type, $forbidden_post_types ) ) {
			WP_CLI::error( "You cannot create PayPal orders for posts of the {$post->post_type} type" );
		}

		return $post_id;
	}

	/**
	 * Parses, validating and checking it, the user-provided ticket ID(s).
	 *
	 * @since 0.2.0
	 *
	 * @param array $assoc_args
	 * @param int   $post_id
	 *
	 * @return array
	 *
	 * @throws \WP_CLI\ExitException
	 */
	protected function parse_ticket_ids( array $args ) {
		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		$ticket_ids = explode( ',', trim( $args[0] ) );

		$available_ticket_ids = $paypal->get_tickets_ids();

		foreach ( $ticket_ids as $ticket_id ) {
			if ( in_array( $ticket_id, $available_ticket_ids ) ) {
				continue;
			}
			WP_CLI::error( "There is no ticket with an ID of {$ticket_id}" );
		}

		return array_map( 'intval', $ticket_ids );
	}

	/**
	 * Parses, validating and sanity-checking them, the user-provided
	 * attendee min and max values.
	 *
	 * @since 0.2.0
	 *
	 * @param array $assoc_args
	 *
	 * @return array
	 *
	 * @throws \WP_CLI\ExitException
	 */
	protected function parse_attendees_min_max( array $assoc_args ) {
		if ( ! (
				filter_var( $assoc_args['attendees_min'], FILTER_VALIDATE_INT )
				&& filter_var( $assoc_args['attendees_max'], FILTER_VALIDATE_INT )
			)
		     || (int) $assoc_args['attendees_min'] > $assoc_args['attendees_max']
		     || 0 > $assoc_args['attendees_min']
		     || 0 > $assoc_args['attendees_max']
		) {
			WP_CLI::error( 'Attendees min and max should be positive integers that make sense' );
		}

		return array( (int) $assoc_args['attendees_min'], $assoc_args['attendees_max'] );
	}

	/**
	 * Parse and validates the user-provided orders count.
	 *
	 * @since 0.2.0
	 *
	 * @param array $assoc_args
	 *
	 * @return int
	 *
	 * @throws \WP_CLI\ExitException
	 */
	protected function parse_count( array $assoc_args ) {
		if ( ! filter_var( $assoc_args['count'], FILTER_VALIDATE_INT ) || (int) $assoc_args['count'] < 1 ) {
			WP_CLI::error( 'The count parameter should be a positive integer' );
		}

		return (int) $assoc_args['count'];
	}

	/**
	 * Parses and validate the user-provided PayPal order status.
	 *
	 * @since 0.2.0
	 *
	 * @param array $assoc_args
	 *
	 * @return string
	 *
	 * @throws \WP_CLI\ExitException
	 */
	protected function parse_order_status( array $assoc_args ) {
		$order_status = trim( $assoc_args['order_status'] );

		$supported_stati = array(
			Tribe__Tickets__Commerce__PayPal__Stati::$completed,
			Tribe__Tickets__Commerce__PayPal__Stati::$pending,
			Tribe__Tickets__Commerce__PayPal__Stati::$refunded,
			Tribe__Tickets__Commerce__PayPal__Stati::$denied,
		);

		if ( ! in_array( $order_status, $supported_stati ) ) {
			WP_CLI::error( "The {$order_status} order status is not valid or suported" );
		}

		return $order_status;
	}

	/**
	 * Hijack some PayPal related hooks to make all work.
	 *
	 * @since 0.2.0
	 */
	protected function hijack_request_flow() {
		// all transactions are valid, we are generating fake numbers
		add_filter( 'tribe_tickets_commerce_paypal_validate_transaction', '__return_true' );

		// mark all generated attendees as generated
		add_action( 'event_tickets_tpp_attendee_created', function ( $attendee_id ) {
			update_post_meta( $attendee_id, Meta_Keys::$generated_meta_key, 1 );
		} );

		add_filter( 'tribe_tickets_tpp_order_postarr', function ( $postarr ) {
			$postarr['meta_input'][ Meta_Keys::$generated_meta_key ] = 1;

			return $postarr;
		} );

		// no, do not send emails to the fake attendees
		add_filter( 'tribe_tickets_tpp_send_mail', '__return_false' );

		// do not `die` after generating tickets
		add_filter( 'tribe_exit', function () {
			return '__return_true';
		} );
	}

	/**
	 * Backups the total sales for a ticket before the generation kicks in.
	 *
	 * @since 0.2.0
	 *
	 * @param int $ticket_id
	 */
	protected function backup_ticket_total_sales( $ticket_id ) {
		$backup_key        = Meta_Keys::$total_sales_backup_meta_key;
		$saved_total_sales = get_post_meta( $ticket_id, $backup_key, true );
		if ( '' === $saved_total_sales ) {
			update_post_meta( $ticket_id, $backup_key, get_post_meta( $ticket_id, 'total_sales', true ) );
		}
	}

	/**
	 * Applies a signum to a number depending on the order status.
	 *
	 * Some order stati will require a negative value, e.g. refunds.
	 *
	 * @since 0.2.0
	 *
	 * @param int $fee
	 *
	 * @return string
	 */
	protected function signed_value( $fee ) {
		if ( Tribe__Tickets__Commerce__PayPal__Stati::$refunded === $this->order_status ) {
			return '-' . $fee;
		}

		return '' . $fee;
	}

	/**
	 * Updates the fees in the data depending on the current order status.
	 *
	 * @since 0.2.0
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function update_fees( array $data ) {
		$fee_fields = array(
			'payment_fee',
			'mc_fee',
			'mc_gross',
			'payment_gross',
		);

		foreach ( $fee_fields as $field ) {
			if ( ! isset( $data[ $field ] ) ) {
				continue;
			}

			$data[ $field ] = $this->signed_value( abs( (float) $data[ $field ] ) );
		}

		return $data;
	}

	/**
	 * Generate the tickets using the PayPal code API.
	 *
	 * @since 0.2.0
	 *
	 * @param array  $transaction_data
	 * @param string $order_status
	 */
	protected function generate_tickets( $order_status, $transaction_data ) {
		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		$fake_transaction_data = $gateway->parse_transaction( $transaction_data );

		add_filter( 'tribe_tickets_commerce_paypal_get_transaction_data', function () use ( $fake_transaction_data ) {
			return $fake_transaction_data;
		} );


		$paypal->generate_tickets( $order_status, false );
	}

	/**
	 * Removes generated PayPal orders for a ticket or a ticketed post.
	 *
	 * @since 0.2.0
	 *
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function reset_orders( array $args = array(), array $assoc_args = array() ) {
		$post_id = $this->parse_post_id( $args );

		$post = get_post($post_id);

		$pre_deleted_attendees_count = (int) get_post_meta( $post_id, '_tribe_deleted_attendees_count', true );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		$is_ticket = true;
		if ( ! $post->post_type === $paypal->ticket_object ) {
			$is_ticket = false;
			WP_CLI::log( "Removing generated PayPal orders from post {$post_id}" );
			$orders = Order::find_by( array( 'post_id' => $post_id, 'posts_per_page' => - 1 ) );
		} else{
			$ticket_id = $post_id;
			WP_CLI::log( "Removing generated PayPal orders for ticket {$post_id}" );
			$orders = Order::find_by( array( 'ticket_id' => $post_id, 'posts_per_page' => - 1 ) );
		}

		if ( empty( $orders ) ) {
			WP_CLI::success( "There are no orders for {$post_id}" );
		}

		$generated_orders = array_filter( $orders, function ( Order $order ) {
			return (bool) $order->get_meta( Meta_Keys::$generated_meta_key );
		} );

		if ( empty( $generated_orders ) ) {
			WP_CLI::success( "No generated orders found for {$post_id}" );
		}

		$progress_bar = make_progress_bar( 'Removing generated orders', count( $generated_orders ) );

		$restored_total_sales_ticket_ids = array();
		/** @var Order $orders */
		foreach ( $generated_orders as $order ) {
			$post_ids = $order->get_related_post_ids();

			$attendees = $order->get_attendees();

			if ( $is_ticket ) {
				$attendees = array_filter( $attendees, function ( array $attendee ) use ( $ticket_id ) {
					return isset( $attendee['product_id'] ) && $attendee['product_id'] == $ticket_id;
				} );
			} else {
				$attendees = array_filter( $attendees, function ( array $attendee ) use ( $post_ids ) {
					return isset( $attendee['event_id'] ) && in_array( $attendee['event_id'], $post_ids );
				} );
			}

			if ( count( $post_ids ) === 1 ) {
				$order->delete( true, true );
			} else {
				foreach ( $attendees as $attendee ) {
					$order->remove_attendee( $attendee['attendee_id'] );
				}
				$order->update();
			}

			foreach ( $attendees as $attendee ) {
				wp_delete_post( $attendee['attendee_id'], true );
			}

			$ticket_ids = $order->get_ticket_ids();
			foreach ( $ticket_ids as $ticket_id ) {
				if ( in_array( $ticket_id, $restored_total_sales_ticket_ids ) ) {
					continue;
				}
				$original_total_sales = (int) get_post_meta( $ticket_id, Meta_Keys::$total_sales_backup_meta_key, true );
				update_post_meta( $ticket_id, 'total_sales', $original_total_sales );
				delete_post_meta( $ticket_id, Meta_Keys::$total_sales_backup_meta_key );
				$restored_total_sales_ticket_ids[] = $ticket_id;
			}

			$progress_bar->tick();
		}

		$progress_bar->finish();

		/** @var Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );
		$paypal->clear_attendees_cache( $post_id );
		update_post_meta( $post_id, '_tribe_deleted_attendees_count', $pre_deleted_attendees_count );
	}
}