<?php

class Tribe__CLI__Events__Generator__CLI extends WP_CLI_Command {

	/**
	 * @var \Tribe__CLI__Events__Generator__API
	 */
	private $api;

	/**
	 * Tribe__CLI__Events__Generator__CLI constructor.
	 *
	 * @param \Tribe__CLI__Events__Generator__API $api
	 */
	public function __construct( Tribe__CLI__Events__Generator__API $api ) {
		parent::__construct();
		$this->api = $api;
	}

	/**
	 * Generate events 100 at a time (default generates one).
	 *
	 * @synopsis   [--count=<count>]
	 * @subcommand generate
	 */
	public function generate( $args, $assoc_args ) {

		$count = 1;

		if ( ! empty( $assoc_args['count'] ) ) {
			$count = absint( $assoc_args['count'] );
		}

		$total_count = $count;

		$memory_used = memory_get_usage();

		while ( 0 < $count ) {
			$max_set = 100;

			if ( $count < $max_set ) {
				$max_set = $count;
			}

			$progress_bar = \WP_CLI\Utils\make_progress_bar(
				sprintf(
					_n( 'Generating event (%d/%d)', 'Generating %s events (%s / %s)', $max_set, 'tribe-cli' ),
					number_format_i18n( $max_set ),
					number_format_i18n( $total_count - $count ),
					number_format_i18n( $total_count )
				),
				$max_set
			);

			for ( $x = 0; $x < $max_set; $x ++ ) {
				$memory_used = memory_get_usage();

				$this->api->generate_event();

				$progress_bar->tick();

				$count--;
			}

			$progress_bar->finish();

			$this->stop_the_insanity();
		}

	}

	/**
	 * Reset all TEC event data.
	 *
	 * @synopsis   [--all]
	 * @subcommand reset
	 */
	public function reset( $args, $assoc_args ) {
		$options = [];

		if ( ! isset( $assoc_args['all'] ) ) {
			$options['meta_key'] = Tribe__CLI__Meta_Keys::$generated_meta_key;
			$options['meta_value'] = 1;
		}

		$this->delete_posts( Tribe__Events__Main::POSTTYPE, $options );
		$this->delete_posts( Tribe__Events__Main::VENUE_POST_TYPE, $options );
		$this->delete_posts( Tribe__Events__Main::ORGANIZER_POST_TYPE, $options );
	}

	/**
	 * Delete posts for a post type.
	 *
	 * @param string $post_type
	 * @param array $options
	 */
	protected function delete_posts( $post_type, $options = [] ) {

		$counter = 0;

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'paged'          => 1,
			'fields'         => 'ids',
		);

		$args = array_merge( $args, $options );

		$post_type_obj = get_post_type_object( $post_type );

		$query = new WP_Query( $args );

		$total_found = $query->found_posts;

		$args['posts_per_page'] = 100;

		$status = 'Deleting ' . $post_type_obj->labels->singular_name;

		if ( 1 !== $total_found ) {
			$status = 'Deleting %s ' . $post_type_obj->labels->name;
		}

		$progress_bar = \WP_CLI\Utils\make_progress_bar( sprintf( $status, number_format_i18n( $total_found ) ), $total_found );

		while ( $posts = $query->query( $args ) ) {
			foreach ( $posts as $post_id ) {
				wp_delete_post( $post_id, true );

				$progress_bar->tick();

				$counter++;

				if ( 0 === ( $counter % 200 ) ) {
					$this->stop_the_insanity();
				}
			}
		}

		$progress_bar->finish();

	}

	/**
	 * Sleep and help avoid hitting memory limit
	 *
	 * @param int $sleep_time Amount of seconds to sleep
	 */
	protected function stop_the_insanity( $sleep_time = 0 ) {

		\WP_CLI::warning( sprintf( '..... Stopped the insanity for %d %s.....', $sleep_time, _n( 'second', 'seconds', $sleep_time ) ) );

		if ( 0 < $sleep_time ) {
			sleep( $sleep_time );
		}

		/**
		 * @var $wpdb            \wpdb
		 * @var $wp_object_cache \WP_Object_Cache
		 */
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array(); // or define( 'WP_IMPORTING', true );

		if ( ! is_object( $wp_object_cache ) ) {
			return;
		}

		$wp_object_cache->group_ops      = array();
		$wp_object_cache->stats          = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache          = array();

		if ( is_callable( array( $wp_object_cache, '__remoteset' ) ) ) {
			call_user_func( array( $wp_object_cache, '__remoteset' ) ); // important
		}

	}

}
