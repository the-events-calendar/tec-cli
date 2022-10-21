<?php

namespace Tribe\CLI\Events\Control;

use TEC\Events\Custom_Tables\V1\Models\Event;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Events_Pro\Custom_Tables\V1\Events\Recurrence;
use TEC\Events_Pro\Custom_Tables\V1\Traits\With_Blocks_Editor_Recurrence;
use TEC\Events_Pro\Custom_Tables\V1\Updates\Events;
use WP_CLI_Command;
use DateInterval;
use WP_CLI;
use Tribe__Date_Utils as Dates;

/**
 * Class CLI.
 *
 * @since 0.2.10
 */
class CLI extends WP_CLI_Command {
	/**
	 * Rotate Events dates for events found.
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
	 * @since      0.2.10
	 *
	 * @param array $args       Arguments that setup the rotation commands.
	 * @param array $assoc_args Arguments that setup the rotation commands.
	 */
	public function rotate( $args, $assoc_args ) {
		$start = $assoc_args['start'];
		$end = $assoc_args['end'];
		$add = $assoc_args['add'];
		$sub = $assoc_args['sub'];
		$is_subtraction = ! empty( $sub );

		$events = $this->find_events( $start, $end );

		if ( empty( $events ) ) {
			WP_CLI::error( "No events were found between Start Date '{$start}' and End Date '{$end}'" );

			return;
		}

		if ( tribe()->getVar( 'ct1_fully_activated' ) ) {
			$this->move_ct1_events( $events, $is_subtraction, $sub, $add );
		} else {
			$this->move_events( $events, $is_subtraction, $sub, $add );
		}

		// Flush the site object cache after the move: flushing only selected posts' cache would not work.
		wp_cache_flush();
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
		return tribe_events()->where( 'starts_between', $start, $end )->per_page( - 1 )->all();
	}

	private function move_ct1_events( array $events, bool $is_subtraction, $sub, $add ): void {
		// Go from Occurrence Provisional post IDs to the Event post ID, drop duplicates.
		$event_post_ids = array_unique( array_filter( array_map(
			static function ( \WP_Post $event ) {
				// This property MUST be defined, this should not fail gracefully.
				return $event->_tec_occurrence->post_id;
			},
			$events
		) ) );

		$interval = $is_subtraction ? new DateInterval( $sub ) : new DateInterval( $add );
		$utc = new \DateTimeZone( 'UTC' );

		// This will avoid using a possibly not defined trait on the CLI class.
		$blocks_handler = new class {
			use With_Blocks_Editor_Recurrence;

			public function update_blocks_meta( int $post_id, array $recurrence_meta ): void {
				$this->update_blocks_format_recurrence_meta( $post_id, $recurrence_meta );
			}
		};

		foreach ( $event_post_ids as $event_post_id ) {
			// Move the Event date meta.
			$timezone = get_post_meta( $event_post_id, '_EventTimezone', true );
			foreach (
				[
					'_EventStartDate' => '_EventStartDateUTC',
					'_EventEndDate'   => '_EventEndDateUTC',
				] as $meta_key => $meta_key_utc
			) {
				$moved = Dates::immutable( get_post_meta( $event_post_id, $meta_key, true ), $timezone );
				$moved = $is_subtraction ? $moved->sub( $interval ) : $moved->add( $interval );

				// Update the timezone-based meta.
				update_post_meta( $event_post_id, $meta_key, $moved->format( Dates::DBDATETIMEFORMAT ) );

				// From the timezone-based meta work out the UTC time, taking daylight-saving into account.
				$meta_value_utc = $moved->setTimezone( $utc )->format( Dates::DBDATETIMEFORMAT );
				update_post_meta( $event_post_id, $meta_key_utc, $meta_value_utc );
			}

			$event_model = Event::find( $event_post_id, 'post_id' );

			if ( ! $event_model instanceof Event ) {
				WP_CLI::error( "Event model for post ID {$event_post_id} not found: run the command again in debug mode to find out more." );
			}

			try {
				$recurrence = get_post_meta( $event_post_id, '_EventRecurrence', true );

				/*
				 * If the Event is recurring, then move the "On" limit of any recurrence or exclusion rule
				 * and flag possible off-pattern DTSTART.
				 */
				if ( ! empty( $recurrence ) && is_array( $recurrence ) ) {
					$update_dates = static function ( array $rule ) use ( $event_post_id, $interval, $is_subtraction, $timezone ): array {
						if ( isset( $rule['end-type'], $rule['end'] ) && $rule['end-type'] === 'On' ) {
							$date_time_immutable = Dates::immutable( $rule['end'], $timezone );
							$rule['end'] = $is_subtraction ?
								$date_time_immutable->sub( $interval )->format( 'Y-m-d' )
								: $date_time_immutable->add( $interval )->format( 'Y-m-d' );
						}

						// Prepare for the correct off-pattern DTSTART detection later.
						$rule['EventStartDate'] = get_post_meta( $event_post_id, '_EventStartDate', true );
						$rule['EventEndDate'] = get_post_meta( $event_post_id, '_EventEndDate', true );
						unset( $rule['isOffStart'] );

						return $rule;
					};

					$recurrence['rules'] = array_map( $update_dates, ( $recurrence['rules'] ?? [] ) );
					$recurrence['exclusions'] = array_map( $update_dates, ( $recurrence['exclusions'] ?? [] ) );

					// Add the off-pattern DTSTART flag, if required.
					$recurrence = tribe( Events::class )->add_off_pattern_flag_to_meta_value( $recurrence, $event_post_id );

					update_post_meta( $event_post_id, '_EventRecurrence', $recurrence );

					// Update the Blocks Editor format recurrence rules and exclusion meta.
					$blocks_handler->update_blocks_meta( $event_post_id, $recurrence );
				}

				// Refresh the Event model from the new post and meta data.
				if ( Event::upsert( [ 'post_id' ], Event::data_from_post( $event_post_id ) ) === false ) {
					WP_CLI::error( "Event model for ID {$event_post_id} could not be upserted." );
				}

				// Update the Event's occurrences: this will apply to Single and Recurring Events.
				$event_model->occurrences()->save_occurrences();

				// Declare the Event as updated.
				$action = $is_subtraction ? 'decreased' : 'increased';
				$action_value = $is_subtraction ? $sub : $add;
				$new_start = get_post_meta( $event_post_id, '_EventStartDate', true );
				$new_end = get_post_meta( $event_post_id, '_EventEndDate', true );
				WP_CLI::success( "Event (ID: {$event_post_id}) dates were {$action} by {$action_value} to Start Date '{$new_start}' and End Date '{$new_end}'." );
			} catch ( \Exception $e ) {
				WP_CLI::error( "Error saving occurrences for event with post_id {$event_post_id}: {$e->getMessage()}" );
			}
		}
	}

	private function move_events( array $events, bool $is_subtraction, $sub, $add ): void {
		foreach ( $events as $event ) {
			if ( $is_subtraction ) {
				list( $event_id, $new_start, $new_end ) = $this->move_event_backward( $event, $sub );
				WP_CLI::success( "Event (ID: {$event_id}) dates were decreased by {$sub} to Start Date '{$new_start}' and End Date '{$new_end}'." );
			} else {
				list( $event_id, $new_start, $new_end ) = $this->move_event_forward( $event, $add );
				WP_CLI::success( "Event (ID: {$event_id}) dates were increased by {$add} to Start Date '{$new_start}' and End Date '{$new_end}'." );
			}
		}
	}
}
