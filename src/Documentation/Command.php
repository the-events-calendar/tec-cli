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
	 * Scan and import for all plugins
	 *
	 * ## OPTIONS
	 *
	 * [--plugin=<plugin>]
	 * : Optional plugin to scan/import
	 *
	 * ## EXAMPLES
	 *
	 *      wp tribe doc build
	 *      wp tribe doc build --plugin=the-events-calendar
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
	 * Scan a plugin and generate WP PHPDoc json file
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
	 *      wp tribe doc scan the-events-calendar
	 *      wp tribe doc scan event-tickets --file=/tmp/whatever.json
	 *
	 * @subcommand scan
	 *
	 * @param $args
	 * @param $assoc_args
	 * @throws WP_CLI\ExitException
	 */
	public function scan( array $args = null, array $assoc_args = null ) {
		$scan_docs = new Scan_Docs();
		$scan_docs->scan( $args, $assoc_args );
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
