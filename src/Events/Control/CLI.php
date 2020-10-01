<?php
namespace Tribe\CLI\Events\Control;

use Tribe\CLI\Meta_Keys;
use Tribe__Events__Main;
use WP_CLI_Command;
use DateInterval;
use WP_CLI;

/**
 * Class CLI.
 *
 * @since 0.2.10
 */
class CLI extends WP_CLI_Command {

	/**
	 * Generate events 100 at a time (default generates one).
	 *
	 * ## OPTIONS
	 *
	 * [--start=<start>]
	 * : Start date to query for the events that will be moved.
	 * ---
	 * default: -30days
	 * ---
	 *
	 * [--end=<end>]
	 * : End date to query for the events that will be moved.
	 * ---
	 * default: +30days
	 * ---
	 *
	 * [--add=<add>]
	 * : Interval that should be added to the events found. Using DateInterval formatting. (https://www.php.net/manual/en/dateinterval.format.php)
	 * ---
	 * default: P7D
	 * ---
	 *
	 * [--sub=<sub>]
	 * : Interval that should be subtracted to the events found. Using DateInterval formatting. (https://www.php.net/manual/en/dateinterval.format.php)
	 * ---
	 * default:
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp tribe events-control rotate --start=-30days --end=+30days --add=P7D
	 *
	 *     wp tribe events-control rotate --start="last year" --end="today" --add=PT12H
	 *
	 *     wp tribe events-control rotate --start=-30days --end=+30days --sub=P7D
	 *
	 * @subcommand rotate
	 *
	 * @since 0.1.0
	 */
	public function rotate( $args, $assoc_args ) {
		$start = $assoc_args['start'];
		$end   = $assoc_args['end'];
		$add   = $assoc_args['add'];
		$sub   = $assoc_args['sub'];
		$is_subtraction = ! empty( $sub );

		$events = $this->find_events( $start, $end );

		if ( empty( $events ) ) {
			WP_CLI::error( "No events were found between Start Date '{$start}' and End Date '{$end}'" );
			return;
		}

		foreach ( $events as $event ) {
			if ( ! $is_subtraction ) {
				list( $event_id, $new_start, $new_end ) = $this->move_event_forward( $event, $add );
				WP_CLI::success( "Event (ID: {$event_id}) dates were increased by {$add} to Start Date '{$new_start}' and End Date '{$new_end}'." );
			} else {
				list( $event_id, $new_start, $new_end ) = $this->move_event_backward( $event, $sub );
				WP_CLI::success( "Event (ID: {$event_id}) dates were decreased by {$sub} to Start Date '{$new_start}' and End Date '{$new_end}'." );
			}
		}

	}

	protected function move_event_forward( $event, $by ) {
		$start_date = $event->dates->start->add( new DateInterval( $by ) )->format( 'Y-m-d H:i:s' );
		$end_date = $event->dates->end->add( new DateInterval( $by ) )->format( 'Y-m-d H:i:s' );
		tribe_events()
			->where( 'post__in', [ $event->ID ] )
			->set( 'start_date', $start_date )
			->set( 'end_date', $end_date )
			->save();

		return [ $event->ID, $start_date, $end_date ];
	}

	protected function move_event_backward( $event, $by ) {
		$start_date = $event->dates->start->sub( new DateInterval( $by ) )->format( 'Y-m-d H:i:s' );
		$end_date = $event->dates->end->sub( new DateInterval( $by ) )->format( 'Y-m-d H:i:s' );
		tribe_events()
			->where( 'post__in', [ $event->ID ] )
			->set( 'start_date', $start_date )
			->set( 'end_date', $end_date )
			->save();

		return [ $event->ID, $start_date, $end_date ];
	}

	protected function find_events( $start, $end ) {
		$events = tribe_events()->where( 'starts_between', $start, $end )->per_page( -1 )->all();

		return $events;
	}
}