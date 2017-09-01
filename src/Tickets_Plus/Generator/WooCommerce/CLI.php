<?php

class Tribe__CLI__Tickets_Plus__Generator__WooCommerce__CLI extends Tribe__CLI__Tickets__Generator__RSVP__CLI {

	/**
	 * @var \Tribe__CLI__WooCommerce__Orders_Generator
	 */
	protected $orders_generator;

	public function __construct( Tribe__CLI__WooCommerce__Orders_Generator $orders_generator ) {
		$this->orders_generator = $orders_generator;
	}

	public function generate_orders( array $generator_args = null, array $assoc_args = null ) {
		$post_id = $generator_args[0];
		$post    = get_post( absint( $post_id ) );

		if ( empty( $post ) ) {
			WP_CLI::error( sprintf( __( 'Post with ID %d does not exist.', 'tribe-cli' ), $post_id ) );
		}

		$count = $assoc_args['count'];
		if ( ! filter_var( $count, FILTER_VALIDATE_INT ) || (int) $count <= 0 ) {
			WP_CLI::error( __( 'Count should be a value greater than 0', 'tribe-cli' ) );
		}

		if ( ! filter_var( $assoc_args['tickets_min'], FILTER_VALIDATE_INT ) || (int) $assoc_args['tickets_min'] <= 0 ) {
			WP_CLI::error( __( 'Tickets min should be a value greater than 0', 'tribe-cli' ) );
		}

		if (
			! filter_var( $assoc_args['tickets_max'], FILTER_VALIDATE_INT )
			|| (int) $assoc_args['tickets_max'] <= 0
			|| (int) $assoc_args['tickets_max'] < $assoc_args['tickets_min']
		) {
			WP_CLI::error( __( 'Tickets max should be a value greater than 0 and greater or equal the tickets minimum value.', 'tribe-cli' ) );
		}

		$legit_stati = array( 'completed', 'processing', 'failed' );
		$stati       = array_merge( $legit_stati, array( 'random' ) );

		if ( ! in_array( $assoc_args['ticket_status'], $stati ) ) {
			WP_CLI::error( __( 'Ticket status must be a valid WooCommerce order status or be omitted.', 'tribe-cli' ) );
		}

		$tickets      = Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance();
		$post_tickets = $tickets->get_tickets_ids( $post_id );

		if (
			isset( $assoc_args['ticket_id'] )
			&& ( ! filter_var( $assoc_args['ticket_id'], FILTER_VALIDATE_INT )
			     || ! in_array( $assoc_args['ticket_id'], $post_tickets )
			)
		) {
			WP_CLI::error( __( 'The specified ticket ID does not exist, is not associated to the specified event or is not a valid value.' ) );
		}

		$create_users = true;
		if ( isset( $assoc_args['no_create_users'] ) ) {
			$create_users = false;
		}

		$post_tickets = isset( $assoc_args['ticket_id'] )
			? array( (int) $assoc_args['ticket_id'] )
			: $post_tickets;

		$sales = array();
		foreach ( $post_tickets as $ticket_id ) {
			$sales[ $ticket_id ] = 0;
		}

		if ( empty( $post_tickets ) ) {
			WP_CLI::error( __( 'The specified post should have at least one WooCommerce ticket assigned.', 'tribe-cli' ) );
		}

		$tickets_min   = (int) $assoc_args['tickets_min'];
		$tickets_max   = (int) $assoc_args['tickets_max'];
		$ticket_status = 'random' === $assoc_args['ticket_status']
			? $legit_stati[ array_rand( $legit_stati ) ]
			: $assoc_args['ticket_status'];

		$counts = array();
		for ( $i = 0; $i < $count; $i ++ ) {
			$counts[ $i ] = random_int( $tickets_min, $tickets_max );
		}

		$counts_sum = array_sum( $counts );

		$progress_bar = \WP_CLI\Utils\make_progress_bar(
			sprintf( __( 'Generating %1$d ticket orders for post %2$d', 'tribe-cli' ), $counts_sum, $post_id ), $counts_sum
		);

		$generator_args = array(
			'create_users' => $create_users,
		);

		foreach ( $counts as $n => $tickets_count ) {
			$this_args = $generator_args;

			$this_args['ticket_status'] = $ticket_status;
			$this_args['tickets_min']   = $tickets_count;
			$this_args['tickets_max']   = $tickets_count;

			$ticket_id     = $post_tickets[ array_rand( $post_tickets ) ];
			$ticket_status = ! empty( $ticket_status ) ? $ticket_status : $legit_stati[ array_rand( $legit_stati ) ];

			$this->orders_generator->generate_orders( 1, array( $ticket_id ), $this_args );

			$progress_bar->tick( $tickets_count );
		}

		WP_CLI::success( sprintf( __( 'Generated %1$d WooCommerce orders for post %2$d', 'tribe-cli' ), $counts_sum, $post_id ) );
	}

	public function reset_orders( array $args = null, array $assoc_args = null ) {
		// @todo this needs to actually delete orders
		//		$post_id = $args[0];
		//		$post    = get_post( absint( $post_id ) );
		//
		//		if ( empty( $post ) ) {
		//			WP_CLI::error( sprintf( __( 'Post with ID %d does not exist.', 'tribe-cli' ), $post_id ) );
		//		}
		//
		//		$tickets      = Tribe__Tickets__RSVP::get_instance();
		//		$post_tickets = $tickets->get_tickets_ids( $post_id );
		//
		//		if ( empty( $post_tickets ) ) {
		//			WP_CLI::error( __( 'The specified post should have at least one ticket assigned.', 'tribe-cli' ) );
		//		}
		//
		//		if ( isset( $assoc_args['ticket_id'] ) && ( ! filter_var( $assoc_args['ticket_id'],
		//					FILTER_VALIDATE_INT ) || ! get_post( $assoc_args['ticket_id'] ) ) ) {
		//			WP_CLI::error( __( 'The specified ticket ID does not exist.', 'tribe-cli' ) );
		//		}
		//
		//		if ( isset( $assoc_args['ticket_id'] ) && ! in_array( $assoc_args['ticket_id'], $post_tickets ) ) {
		//			WP_CLI::error( __( 'The specified ticket ID is not assigned to the specified post.', 'tribe-cli' ) );
		//		}
		//
		//		$post_tickets = isset( $assoc_args['ticket_id'] ) ? array( (int) $assoc_args['ticket_id'] ) : $post_tickets;
		//
		//		$attendees = $tickets->get_attendees_by_id( $post_id );
		//
		//		$progress_bar = \WP_CLI\Utils\make_progress_bar( sprintf( __( 'Deleting RSVP attendees', 'tribe-cli' ) ),
		//			count( $attendees ) );
		//
		//		foreach ( $post_tickets as $ticket ) {
		//			$this_ticket_attendees = array_filter( $attendees, function ( array $attendee ) use ( $ticket ) {
		//				return isset( $attendee['product_id'] ) && $attendee['product_id'] == $ticket;
		//			} );
		//			foreach ( $this_ticket_attendees as $attendee ) {
		//				wp_delete_post( $attendee['order_id'], true );
		//				$progress_bar->tick();
		//			}
		//			update_post_meta( $ticket, 'total_sales', 0 );
		//		}
	}
}