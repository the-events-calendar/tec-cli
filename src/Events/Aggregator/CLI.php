<?php
namespace Tribe\CLI\Events\Aggregator;

use Tribe\CLI\Meta_Keys;
use Tribe__Events__Aggregator__Event as EA_Event;
use Tribe__Events__Aggregator__Records as EA_Records;
use Tribe__Events__Main;
use WP_CLI;

/**
 * Class RescheduleImports
 *
 * @since 0.1.0
 */
class CLI extends \WP_CLI_Command {
		/**
		 * Reschedules all aggregator scheduled imports.
		 *
		 * @subcommand schedules
		 *
		 * @since 0.1.0
		 */
		public function schedules( $args, $assoc_args ) {
			$records = tribe( 'events-aggregator.records' );

			$args = [
				'post_type'      => EA_Records::$post_type,
				'post_status'    => $records->get_status( 'schedule' )->name,
				'posts_per_page' => -1,
			];

			$schedules = $records->query( $args );

			if ( empty( $schedules ) ) {
				return;
			}

			WP_CLI::line( '****************************************' );
			WP_CLI::line( get_option( 'blogname' ) . ' -> ' . get_option( 'siteurl' ) );
			WP_CLI::line( '----------------------------------------' );
			WP_CLI::line( '' );

			foreach ( $schedules->posts as $post ) {
				$origin = get_post_meta( $post->ID, EA_Event::$origin_key, true );
				$schedule = $records->get_by_origin( $origin, $post );
				WP_CLI::line( $schedule->meta['import_name'] . ' -> ' . $schedule->meta['source'] );
				WP_CLI::line( 'Origin: ' . $schedule->meta['origin'] );
				WP_CLI::line( 'Frequency: ' . $schedule->meta['frequency'] );
				WP_CLI::line( '' );
			}
			WP_CLI::line( '****************************************' );
		}
}
