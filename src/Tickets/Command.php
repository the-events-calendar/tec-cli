<?php
class Tribe__CLI__Tickets__Command extends \WP_CLI_Command{

	/**
	 * Generates attendees for a ticketed post.
	 *
	 * @subcommand generate-attendees
	 */
	public function generate_attendees(  ) {
		WP_CLI::success("hello world!");
	}
}