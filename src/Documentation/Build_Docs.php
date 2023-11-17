<?php
namespace Tribe\CLI\Documentation;

use WP_CLI;

class Build_Docs extends Abstract_Doc_Command {
	/**
	 * Scan and import for all plugins
	 *
	 * @param $args
	 * @param $assoc_args
	 * @throws WP_CLI\ExitException
	 */
	public function build( array $args = null, array $assoc_args = null ) {
		$plugins = [
			'advanced-post-manager',
			'event-automator',
			'event-tickets',
			'event-tickets-plus',
			'event-tickets-wallet-plus',
			'events-community',
			'events-community-tickets',
			'events-eventbrite',
			'events-filterbar',
			'events-pro',
			'events-virtual',
			'image-widget-plus',
			'the-events-calendar',
			'tribe-common',
			'event-schedule-manager',
		];

		if ( isset( $assoc_args['plugin'] ) ) {
			$plugins = [ $assoc_args['plugin'] ];
		}

		wp_set_current_user( 1 );

		$temp_dir = sys_get_temp_dir();
		$options = [
			'return'     => false, // Return 'STDOUT'; use 'all' for full object.
			'parse'      => false, // Parse captured STDOUT to JSON array.
			'launch'     => false, // Reuse the current process.
			'exit_error' => true,  // Halt script execution on error.
		];

		foreach ( $plugins as $plugin ) {
			$plugin_path = WP_CONTENT_DIR . '/plugins/' . $plugin;
			if ( ! file_exists ( $plugin_path ) ) {
				continue;
			}

			$output_file = "{$temp_dir}/{$plugin}.json";

			WP_CLI::line( "Scanning $plugin" );
			WP_CLI::runcommand( "tribe doc scan {$plugin} --output={$output_file}", $options );

			WP_CLI::line( "Importing $plugin" );
			WP_CLI::runcommand( "tribe doc import {$plugin} {$output_file}", $options );
		}

		WP_CLI::success( 'Work complete.' );
	}
}
