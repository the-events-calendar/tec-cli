<?php
namespace Tribe\CLI\Documentation;

use WP_Parser\WP_CLI_Logger;
use WP_CLI;

class Import_Docs extends Abstract_Doc_Command {
	/**
	 * Imports WP PHPDoc json file
	 *
	 * @param $args
	 * @param $assoc_args
	 * @throws WP_CLI\ExitException
	 */
	public function import( array $args = null, array $assoc_args = null ) {
		if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
			WP_CLI::error( __( 'Please install and activate WP Parser from https://github.com/WordPress/phpdoc-parser before building documentation.', 'tribe-cli' ) );
		}

		WP_CLI::line();

		if ( ! class_exists( '\WP_Parser\Importer' ) ) {
			WP_CLI::error( __( 'Please install and activate WP Parser from https://github.com/WordPress/phpdoc-parser before importing documentation.', 'tribe-cli' ) );
		}

		$plugin = $this->parse_plugin( $args );
		$file   = $this->parse_file( $plugin, $args );

		// Get the data from the <file>, and check it's valid.
		$phpdoc = false;
		if ( is_readable( $file ) ) {
			$phpdoc = file_get_contents( $file );
		}

		if ( ! $phpdoc ) {
			WP_CLI::error( sprintf( __( "Can't read %1\$s. Does the file exist?", 'tribe-cli' ), $file ) );
		}

		$phpdoc = json_decode( $phpdoc, true );

		if ( is_null( $phpdoc ) ) {
			WP_CLI::error( sprintf( __( "JSON in %1\$s can't be decoded", 'tribe-cli' ), $file ) );
		}

		// Import data
		$this->run_import( $phpdoc );
	}

	private function run_import( $data ) {
		if ( ! wp_get_current_user()->exists() ) {
			WP_CLI::error( __( 'Please specify a valid user: --user=<id|login>', 'tribe-cli' ) );
		}

		// Run the importer
		$importer = new Data_Importer();
		$importer->setLogger( new WP_CLI_Logger() );
		$importer->import( $data, true, false );
		WP_CLI::line();
	}

	/**
	 * Parses out the passed file path
	 *
	 * @param string $plugin
	 * @param array $args
	 *
	 * @return mixed
	 */
	private function parse_file( string $plugin, array $args ) {
		if ( ! isset( $args[1] ) ) {
			return "/tmp/{$plugin}.json";
		}

		return $args[1];
	}
}
