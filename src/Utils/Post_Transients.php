<?php
namespace Tribe\CLI\Utils;

use WP_CLI;

/**
 * Class Post_Transients
 *
 * @since 0.2.4
 */
class Post_Transients extends \WP_CLI_Command {

	/**
	 * Deletes post transients created by Modern Tribe code.
	 *
	 * ## OPTIONS
	 *
	 * [<key>]
	 * : Key for the post transient.
	 *
	 * [--post_id=<post_id>]
	 * : Delete post transients on this post only.
	 *
	 * [--post_type=<post_type>]
	 * : Delete post transients on this post type only.
	 *
	 * [--all]
	 * : Delete all post transients on all posts.
	 *
	 * [--expired]
	 * : Delete all expired post transients on the post(s).
	 *
	 * ## EXAMPLES
	 *
	 *      # Delete all post transients with an `attendee_cache` key on all posts
	 *      wp tribe post-transient delete attendees_cache
	 *
	 *      # Delete all post transients with an `attendee_cache` key on the post with an ID of `23`
	 *      wp tribe post-transient delete attendees_cache --post_id=23
	 *
	 *      # Delete all post transients with an `attendee_cache` key on any post of the `tribe_events` type
	 *      wp tribe post-transient delete attendees_cache --post_type=tribe_events
	 *
	 *      # Delete all post transients with an `attendee_cache` key on the post with an ID of `23` of the `tribe_events` type
	 *      wp tribe post-transient delete attendees_cache --post_id=23 --post_type=tribe_events
	 *
	 *      # Delete all post transients on all posts
	 *      wp tribe post-transient delete --all
	 *
	 *      # Delete all post transients on the post with an ID of `23`
	 *      wp tribe post-transient delete --all --post_id=23
	 *
	 *      # Delete all post transients on the post with an ID of `23` and a `tribe_events` post type
	 *      wp tribe post-transient delete --all --post_id=23 --post_type=tribe_events
	 *
	 *      # Delete all expired post transients on all posts
	 *      wp tribe post-transient delete --expired
	 *
	 *      # Delete all expired `attendees_cache` post transients on all posts
	 *      wp tribe post-transient delete attendees_cache --expired
	 *
	 *
	 * @subcommand delete
	 *
	 * @since      0.2.4
	 */
	public function delete( array $args = [], array $assoc_args = [] ) {
		$key = isset( $args[0] ) ? $args[0] : false;

		$post_id = false;
		if ( isset( $assoc_args['post_id'] ) ) {
			$post_id = $this->check_post_id( $assoc_args );
			$this->check_post( $post_id );
		}

		$post_type = false;
		if ( isset( $assoc_args['post_type'] ) ) {
			$post_type = $this->check_post_type( $assoc_args );
		}

		$all     = isset( $assoc_args['all'] );
		$expired = isset( $assoc_args['expired'] );

		$this->check_argument_combinations( $key, $post_id, $post_type, $all, $expired );

		global $_wp_using_ext_object_cache;

		if ( $_wp_using_ext_object_cache ) {
			if ( empty( $key ) || empty( $post_id ) ) {
				$message = __(
					'Your website is using object caching to store post transients; you will need to specify the post ID and key for each entry using the format "wp tribe post-transient delete <key> --post_id=<post_id>"',
					'tribe-cli'
				);
				WP_CLI::error( $message );
			}
			$entries = $this->delete_from_object_cache( $key, $post_id );
			WP_CLI::success( "{$entries} post transients deleted from object cache." );

			return;
		}

		$rows = $this->delete_from_database( $key, $post_id, $post_type, $all, $expired );
		WP_CLI::success( "{$rows} post transients deleted." );
	}

	/**
	 * Validates the post ID provided by the user, if any.
	 *
	 * @since 0.2.4
	 *
	 * @param array $assoc_args
	 *
	 * @return int
	 */
	protected function check_post_id( array $assoc_args ) {
		$post_id = filter_var( $assoc_args['post_id'], FILTER_VALIDATE_INT );
		if ( ! $post_id ) {
			WP_CLI::error( "{$post_id} is not a valid post ID" );
		}

		return $post_id;
	}

	/**
	 * Validates the post if the user provided a post ID.
	 *
	 * @since 0.2.4
	 *
	 * @param $post_id
	 */
	protected function check_post( $post_id ) {
		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			WP_CLI::success( "No post found with an ID of {$post_id}." );
		}
	}

	/**
	 * Validates the post type if provided by the user.
	 *
	 * @since 0.2.4
	 *
	 * @param array $assoc_args
	 *
	 * @return string
	 */
	protected function check_post_type( array $assoc_args ) {
		$post_type = $assoc_args['post_type'];
		// we only check if it's a string, not if the post_type is defined in WP
		// as it might be from a deactivated plugin
		$post_type = filter_var( $post_type, FILTER_SANITIZE_STRING );

		if ( ! $post_type || is_numeric( $post_type ) ) {
			WP_CLI::error( "{$post_type} is not a valid post type." );
		}

		return trim( $post_type );
	}

	/**
	 * Validates the command argument combination to make sure it makes sense.
	 *
	 * @since 0.2.4
	 *
	 * @param string $key
	 * @param int    $post_id
	 * @param string $post_type
	 * @param bool   $all
	 * @param bool   $expired
	 */
	protected function check_argument_combinations( $key, $post_id, $post_type, $all, $expired ) {
		$bitmask = (int) ( (bool) $key ) * 10000
		           + (int) ( (bool) $post_id ) * 1000
		           + (int) ( (bool) $post_type ) * 100
		           + (int) $all * 10
		           + (int) $expired;

		switch ( $bitmask ) {
			case 0:
				WP_CLI::error( __( 'Provide at least one parameter; use `--help` to know more.', 'tribe-cli' ) );
				break;
			case 11111:
			case 10011:
			case 11011:
			case 10111:
			case 1111:
			case 1011:
			case 111:
			case 11:
				WP_CLI::error( __( 'Either delete all transients (using `--all`) or just the expired ones (using `--expired`).', 'tribe-cli' ) );
				break;
			case 11010:
			case 11110:
				WP_CLI::error( __( "Either delete all transients on post {$post_id } or just the ones with the {$key} key", 'tribe-cli' ) );
				break;
			default:
				// legit combination
				break;
		}
	}

	/**
	 * Deletes a post transient stored in the object cache.
	 *
	 * @since 0.2.4
	 *
	 * @param string $key
	 * @param int    $post_id
	 *
	 * @return bool
	 */
	protected function delete_from_object_cache( $key, $post_id ) {
		return wp_cache_delete( "tribe_{$key}-{$post_id}", "tribe_post_meta_transient-{$post_id}" );
	}

	/**
	 * Deletes a post transient stored in the database.
	 *
	 * @since 0.2.4
	 *
	 * @param string $key
	 * @param int    $post_id
	 * @param string $post_type
	 * @param bool   $all
	 * @param bool   $expired
	 *
	 * @return false|int False if the post transient(s) could not be delted, the number of deleted post transients otherwise.
	 */
	protected function delete_from_database( $key = null, $post_id = null, $post_type = null, $all = null, $expired = null ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		if ( $key ) {
			$where[] = $wpdb->prepare( "meta_key IN (%s,%s)", '_transient_' . $key, '_transient_timeout_' . $key );
		}

		if ( $all ) {
			$where[] = "meta_key LIKE '_transient_%' OR meta_key LIKE '_transient_timeout_%'";
		}

		if ( $post_id ) {
			$where[] = $wpdb->prepare( "post_id = %d", $post_id );
		}

		if ( $post_type ) {
			$post_ids_query = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", $post_type );
			WP_CLI::debug( "Post type is {$post_type}, getting post IDs with query: {$post_ids_query}" );
			$ids = $wpdb->get_col( $post_ids_query );

			if ( empty( $ids ) ) {
				WP_CLI::success( "No posts of type {$post_type} found." );

				exit;
			}

			$ids = implode( ',', $ids );
			WP_CLI::debug( "Post type is {$post_type}, found these IDs: {$ids}" );
			$where[] = "post_id IN ({$ids})";
		}

		if ( $expired ) {
			$expired_ids_query = $wpdb->prepare(
				"SELECT meta_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE '_transient_timeout_%' AND meta_value <= %d",
				time()
			);
			WP_CLI::debug( "Expired is set, getting expired meta IDs with query: {$expired_ids_query}" );
			$expired_meta_ids = $wpdb->get_results( $expired_ids_query );

			if ( empty( $expired_meta_ids ) ) {
				WP_CLI::success( "No expired post transients found." );

				exit;
			}

			$meta_ids = implode( ',', $expired_meta_ids );
			WP_CLI::debug( "Expired is set, found these meta_ids: {$meta_ids}" );
			$where[] = "meta_id IN {$meta_ids}";
		}

		if ( empty( $where ) ) {
			return 0;
		}

		$where_predicates = count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '';
		$query            = "DELETE FROM {$wpdb->postmeta} {$where_predicates}";

		WP_CLI::debug( 'Running query: ' . $query );

		return $wpdb->query( $query );
	}
}
