<?php
namespace Tribe\CLI\Tickets;

/**
 * Class Command
 *
 * @since 0.1.0
 */
class Command extends \WP_CLI_Command {

	/**
	 * @var Generator\RSVP\CLI
	 */
	protected $rsvp;

	/**
	 * Command constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Generator\RSVP\CLI $paypal
	 */
	public function __construct( Generator\RSVP\CLI $paypal ) {
		parent::__construct();
		$this->rsvp = $paypal;
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
	 * default: yes
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
	 *      wp tribe event-tickets generate-rsvp-attendees 23
	 *      wp tribe event-tickets generate-rsvp-attendees 23 --count=89
	 *      wp tribe event-tickets generate-rsvp-attendees 23 --tickets_min=3
	 *      wp tribe event-tickets generate-rsvp-attendees 23 --tickets_min=3 --tickets_max=10
	 *      wp tribe event-tickets generate-rsvp-attendees 23 --tickets_min=3 --tickets_max=10 --ticket_status=no
	 *      wp tribe event-tickets generate-rsvp-attendees 23 --ticket_id=89
	 *
	 * @subcommand generate-rsvp-attendees
	 *
	 * @since 0.1.0
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
	 *      wp event-tickets reset-rsvp-attendees 23
	 *      wp event-tickets reset-rsvp-attendees 23 --ticket_id=89
	 *
	 * @subcommand reset-rsvp-attendees
	 *
	 * @since 0.1.0
	 */
	public function reset_rsvp_attendees( array $args = null, array $assoc_args = null ) {
		$this->rsvp->reset_attendees( $args, $assoc_args );
	}
}
