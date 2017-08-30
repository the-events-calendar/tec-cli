<?php

class Tribe__CLI__Tickets__Command extends WP_CLI_Command {

	/**
	 * @var \Tribe__CLI__Tickets__Generator__RSVP__CLI
	 */
	protected $rsvp;

	/**
	 * Tribe__CLI__Tickets__Command constructor.
	 *
	 * @param \Tribe__CLI__Tickets__Generator__RSVP__CLI $rsvp
	 */
	public function __construct( Tribe__CLI__Tickets__Generator__RSVP__CLI $rsvp ) {
		parent::__construct();
		$this->rsvp = $rsvp;
	}

	/**
	 * Generates RSVP attendees for a ticketed post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : attendees will be attached to this post
	 *
	 * [--count=<count>]
	 * : the number of attendees to generate
	 * ---
	 * default: 10
	 * ---
	 *
	 * [--tickets_min=<tickets_min>]
	 * : the minimum number of tickets per attendee
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--tickets_max=<tickets_max>]
	 * : the maximum number of tickets per attendee
	 * ---
	 * default: 3
	 * ---
	 *
	 * [--ticket_status=<ticket_status>]
	 * : the RSVP status of the tickets
	 * ---
	 * default: random
	 * options:
	 *      - yes
	 *      - no
	 * ---
	 *
	 * [--ticket_id=<ticket_id>]
	 * : the ID of the ticket attendees should be assigned to
	 *
	 * ## EXAMPLES
	 *
	 *      wp event-ticket generate-attendees 23
	 *      wp event-ticket generate-attendees 23 --count=89
	 *      wp event-ticket generate-attendees 23 --tickets_min=3
	 *      wp event-ticket generate-attendees 23 --tickets_min=3 --tickets_max=10
	 *      wp event-ticket generate-attendees 23 --tickets_min=3 --tickets_max=10 --ticket_status=no
	 *      wp event-ticket generate-attendees 23 --ticket_id=89
	 *
	 * @subcommand generate-rsvp-attendees
	 */
	public function generate_rsvp_attendees( array $args = null, array $assoc_args = null ) {
		$this->rsvp->generate_attendees( $args, $assoc_args );
	}

	/**
	 * Removes all the RSVP attendees from a ticketed post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : attendees will be removed from this ticketed post
	 *
	 * [--ticket_id=<ticket_id>]
	 * : only remove attendees for this RSVP ticket attached to the post
	 *
	 * ## EXAMPLES
	 *
	 *      wp event-ticket reset-rsvp-attendees 23
	 *      wp event-ticket reset-rsvp-attendees 23 --ticket_id=89
	 *
	 * @subcommand reset-rsvp-attendees
	 */
	public function reset_rsvp_attendees( array $args = null, array $assoc_args = null ) {
		$this->rsvp->reset_attendees( $args, $assoc_args );
	}
}