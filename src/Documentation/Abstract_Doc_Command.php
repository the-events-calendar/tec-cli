<?php
namespace Tribe\CLI\Documentation;

use WP_CLI;

abstract class Abstract_Doc_Command extends \WP_CLI_Command {
	protected $plugin_dir;

	/**
	 * Parses out the passed plugin name
	 *
	 * @param array $args
	 *
	 * @return mixed
	 *
	 * @throws WP_CLI\ExitException
	 */
	protected function parse_plugin( array $args ) {
		$plugin = reset( $args );
		if ( ! $plugin ) {
			WP_CLI::error( 'You must specify a plugin' );
			die;
		}

		if ( $plugin !== preg_replace( '![^a-zA-Z0-9\-\_]!', '', $plugin ) ) {
			WP_CLI::error( 'You have supplied an invalid plugin name' );
			die;
		}

		if ( ! file_exists( TRIBE_CLI_DIR . '/../' . $plugin ) ) {
			WP_CLI::error( 'You must specify a plugin directory that actually exists' );
			die;
		}

		$this->plugin_dir = TRIBE_CLI_DIR . '/../' . $plugin;

		return $plugin;
	}
}
