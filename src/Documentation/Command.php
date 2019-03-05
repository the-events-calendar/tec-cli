<?php
namespace Tribe\CLI\Documentation;

use WP_CLI;

/**
 * Class Command
 *
 * @since 0.2.6
 */
class Command extends \WP_CLI_Command {

	/**
	 * Builds WP PHPDoc json file
	 *
	 * ## OPTIONS
	 *
	 * <plugin>
	 * : The plugin to generate documentation for
	 *
	 * [--output=<output>]
	 * : Path to the JSON file to export
	 *
	 * ## EXAMPLES
	 *
	 *      wp tribe doc build the-events-calendar
	 *      wp tribe doc build event-tickets --file=/tmp/whatever.json
	 *
	 * @subcommand build
	 *
	 * @param $args
	 * @param $assoc_args
	 * @throws WP_CLI\ExitException
	 */
	public function build( array $args = null, array $assoc_args = null ) {
		$build_docs = new Build_Docs();
		$build_docs->build( $args, $assoc_args );
	}

	/**
	 * Imports WP PHPDoc json file
	 *
	 * ## OPTIONS
	 *
	 * <plugin>
	 * : The plugin to generate documentation for
	 *
	 * <file>
	 * : Path to the JSON file to export
	 *
	 * ## EXAMPLES
	 *
	 *      wp tribe doc import the-events-calendar
	 *      wp tribe doc import event-tickets /tmp/whatever.json
	 *
	 * @subcommand import
	 *
	 * @param $args
	 * @param $assoc_args
	 * @throws WP_CLI\ExitException
	 */
	public function import( array $args = null, array $assoc_args = null ) {
		$import_docs = new Import_Docs();
		$import_docs->import( $args, $assoc_args );
	}
}