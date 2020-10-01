<?php
namespace Tribe\CLI\Events\Control;

use Tribe\CLI\Meta_Keys;
use Tribe__Events__Main;
use WP_CLI_Command;

/**
 * Class CLI.
 *
 * @since 0.2.10
 */
class CLI extends WP_CLI_Command {

	/**
	 * Generate events 100 at a time (default generates one).
	 *
	 * @synopsis   [--count=<count>]
	 * @subcommand rotate
	 *
	 * @since 0.1.0
	 */
	public function rotate() {

	}

	public function find_events() {

	}

	public function move_event( $event ) {

	}
}