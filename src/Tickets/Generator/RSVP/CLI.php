<?php

class Tribe__CLI__Tickets__Generator__RSVP__CLI {

	public function generate_attendees( array $args = null, array $assoc_args = null ) {
		global $tribe_cli_container;

		$post_id = $args[0];
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

		if ( ! in_array( $assoc_args['ticket_status'], array( 'random', 'yes', 'no' ) ) ) {
			WP_CLI::error( __( 'Ticket status must be "yes", "no" or omitted.', 'tribe-cli' ) );
		}

		$tickets      = Tribe__Tickets__RSVP::get_instance();
		$post_tickets = $tickets->get_tickets_ids( $post_id );

		if (
			isset( $assoc_args['ticket_id'] )
			&& ( ! filter_var( $assoc_args['ticket_id'], FILTER_VALIDATE_INT )
			     || ! in_array( $assoc_args['ticket_id'], $post_tickets )
			)
		) {
			WP_CLI::error( __( 'The specified ticket ID does not exist, is not associated to the specified event or is not a valid value.' ) );
		}

		$post_tickets = isset( $assoc_args['ticket_id'] )
			? array( (int) $assoc_args['ticket_id'] )
			: $post_tickets;

		$sales = array();
		foreach ( $post_tickets as $ticket_id ) {
			$sales[ $ticket_id ] = 0;
		}

		if ( empty( $post_tickets ) ) {
			WP_CLI::error( __( 'The specified post should have at least one ticket assigned.', 'tribe-cli' ) );
		}

		$tickets_min   = (int) $assoc_args['tickets_min'];
		$tickets_max   = (int) $assoc_args['tickets_max'];
		$ticket_status = $assoc_args['ticket_status'];
		$stati         = array( 'yes', 'no' );

		$counts = array();
		for ( $i = 0; $i < $count; $i ++ ) {
			$counts[ $i ] = random_int( $tickets_min, $tickets_max );
		}

		$counts_sum = array_sum( $counts );

		$progress_bar = \WP_CLI\Utils\make_progress_bar(
			sprintf( __( 'Generating %1$d RSVP attendees for post %2$d', 'tribe-cli' ), $counts_sum, $post_id ), $counts_sum
		);

		foreach ( $counts as $n => $tickets_count ) {
			$faker          = Faker\Factory::create();
			$attendee_name  = $faker->name;
			$attendee_email = $faker->email;
			$ticket_id      = $post_tickets[ array_rand( $post_tickets ) ];
			$rsvp_status    = 'random' !== $ticket_status ? $ticket_status : $stati[ array_rand( $stati ) ];

			for ( $i = 1; $i <= $tickets_count; $i ++ ) {
				$postarr = array(
					'post_title'     => "{$attendee_name} | {$i}",
					'post_status'    => 'publish',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_name'      => sanitize_title( $attendee_name ),
					'post_type'      => Tribe__Tickets__RSVP::ATTENDEE_OBJECT,
				);

				$attendee_id = wp_insert_post( $postarr );

				if ( empty( $attendee_id ) ) {
					WP_CLI::error( __( 'There was an error while inserting the attendee post...' ) );
				}

				$order_id = md5( time() . rand() );

				$meta = array(
					Tribe__Tickets__RSVP::ATTENDEE_PRODUCT_KEY           => $ticket_id,
					Tribe__Tickets__RSVP::ATTENDEE_EVENT_KEY             => $post_id,
					Tribe__Tickets__RSVP::ATTENDEE_RSVP_KEY              => $rsvp_status,
					$tickets->security_code                              => $tickets->generate_security_code( $attendee_id ),
					$tickets->order_key                                  => $order_id,
					Tribe__Tickets__RSVP::ATTENDEE_OPTOUT_KEY            => '',
					$tickets->full_name                                  => $attendee_name,
					$tickets->email                                      => $attendee_email,
					'_tribe_tickets_attendee_user_id'                    => 0,
					'_tribe_rsvp_attendee_ticket_sent'                   => 1,
					$tribe_cli_container->getVar( 'generated-meta-key' ) => 1,
				);

				foreach ( $meta as $key => $value ) {
					update_post_meta( $attendee_id, $key, $value );
				}

				$sales[ $ticket_id ] += 1;

				$progress_bar->tick();
			}
		}

		foreach ( $sales as $ticket_id => $qty ) {
			$current_sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
			$updated_sales = $current_sales + $qty;
			update_post_meta( $ticket_id, 'total_sales', $updated_sales );
		}

		WP_CLI::success( sprintf( __( 'Generated %1$d RSVP attendees for post %2$d', 'tribe-cli' ), $counts_sum, $post_id ) );
	}

	public function reset_attendees( array $args = null, array $assoc_args = null ) {
		$post_id = $args[0];
		$post    = get_post( absint( $post_id ) );

		if ( empty( $post ) ) {
			WP_CLI::error( sprintf( __( 'Post with ID %d does not exist.', 'tribe-cli' ), $post_id ) );
		}

		$tickets      = Tribe__Tickets__RSVP::get_instance();
		$post_tickets = $tickets->get_tickets_ids( $post_id );

		if ( empty( $post_tickets ) ) {
			WP_CLI::error( __( 'The specified post should have at least one ticket assigned.', 'tribe-cli' ) );
		}

		if ( isset( $assoc_args['ticket_id'] ) && ( ! filter_var( $assoc_args['ticket_id'],
					FILTER_VALIDATE_INT ) || ! get_post( $assoc_args['ticket_id'] ) ) ) {
			WP_CLI::error( __( 'The specified ticket ID does not exist.', 'tribe-cli' ) );
		}

		if ( isset( $assoc_args['ticket_id'] ) && ! in_array( $assoc_args['ticket_id'], $post_tickets ) ) {
			WP_CLI::error( __( 'The specified ticket ID is not assigned to the specified post.', 'tribe-cli' ) );
		}

		$post_tickets = isset( $assoc_args['ticket_id'] ) ? array( (int) $assoc_args['ticket_id'] ) : $post_tickets;

		$attendees = $tickets->get_attendees_by_id( $post_id );

		$progress_bar = \WP_CLI\Utils\make_progress_bar( sprintf( __( 'Deleting RSVP attendees', 'tribe-cli' ) ),
			count( $attendees ) );

		foreach ( $post_tickets as $ticket ) {
			$this_ticket_attendees = array_filter( $attendees, function ( array $attendee ) use ( $ticket ) {
				return isset( $attendee['product_id'] ) && $attendee['product_id'] == $ticket;
			} );
			foreach ( $this_ticket_attendees as $attendee ) {
				wp_delete_post( $attendee['order_id'], true );
				$progress_bar->tick();
			}
			update_post_meta( $ticket, 'total_sales', 0 );
		}
	}
}