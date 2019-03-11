<?php
namespace Tribe\CLI\Events\Generator;

use Faker;
use Tribe\CLI\Meta_Keys as Meta_Keys;

/**
 * Class API
 *
 * @since 0.1.0
 */
class API {
	/**
	 * Generate multiple events at once.
	 *
	 * @since 0.1.0
	 *
	 * @param int $count Number of events to generate
	 *
	 * @return int[] IDs of the event that was created. False if insert failed.
	 */
	public function generate_events( $count = 100 ) {

		$ids = array();

		for ( $x = 0; $x < $count; $x ++ ) {
			$ids[] = $this->generate_event();
		}

		return $ids;

	}

	/**
	 * Generate event with TEC.
	 *
	 * @since 0.1.0
	 *
	 * @return int ID of the event that was created. False if insert failed.
	 */
	public function generate_event() {

		$faker = Faker\Factory::create();
		$days = wp_rand( - 500, 500 );

		$end_days = wp_rand( 0, 5 );

		if ( 0 < $days ) {
			$start_date = strtotime( '+' . $days . ' days' );
		} elseif ( $days < 0 ) {
			$start_date = strtotime( '-' . abs( $days ) . ' days' );
		} else {
			$start_date = time();
		}

		if ( 0 < $end_days ) {
			$end_date = strtotime( '+' . $end_days . ' days', $start_date );
		} else {
			$end_date = $start_date;
		}

		$all_day = (boolean) wp_rand( 0, 1 );

		$recurrence = (boolean) wp_rand( 0, 1 );

		$content = implode( "\n", $faker->paragraphs() );

		$statuses = array(
			'publish',
			'draft',
		);

		$status = $statuses[ wp_rand( 0, count( $statuses ) - 1 ) ];

		$venue = array(
			'post_status' => 'publish',
			'Venue'       => $faker->company,
			'Country'     => 'US',
			'Address'     => $faker->address,
			'City'        => 'Phoenix',
			'State'       => 'AZ',
			'Zip'         => '85012',
			'Phone'       => $faker->phoneNumber,
		);

		$venue = tribe_create_venue( $venue );

		$organizer = array(
			'post_status' => 'publish',
			'Organizer'   => $faker->name(),
			'Email'       => $faker->email,
			'Phone'       => $faker->phoneNumber,
		);

		$organizer = tribe_create_organizer( $organizer );

		$args = array(
			'post_title'     => ucwords( $faker->words( 3, true ) ),
			'post_content'   => $content,
			'post_status'    => $status,
			'EventStartDate' => date_i18n( 'Y-m-d', $start_date ),
			'EventEndDate'   => date_i18n( 'Y-m-d', $end_date ),
			'EventAllDay'    => $all_day,
			'Venue'          => array(
				'VenueID' => $venue,
			),
			'Organizer'      => array(
				'OrganizerID' => $organizer,
			),
		);

		if ( ! $all_day ) {
			$start_hour = wp_rand( 1, 11 );

			if ( $start_hour < 10 ) {
				$start_hour = '0' . $start_hour;
			}

			$start_minute = wp_rand( 0, 59 );

			if ( $start_minute < 10 ) {
				$start_minute = '0' . $start_minute;
			}

			$end_hour = wp_rand( 1, 11 );

			if ( $end_hour < 10 ) {
				$end_hour = '0' . $end_hour;
			}

			$end_minute = wp_rand( 0, 59 );

			if ( $end_minute < 10 ) {
				$end_minute = '0' . $end_minute;
			}

			$args['EventStartHour']     = $start_hour;
			$args['EventStartMinute']   = $start_minute;
			$args['EventStartMeridian'] = 'am';
			$args['EventEndHour']       = $end_hour;
			$args['EventEndMinute']     = $end_minute;
			$args['EventEndMeridian']   = 'pm';
		}

		//$recurrence = false;

		if ( $recurrence ) {
			$args['recurrence'] = array(
				'rules' => array(
					0 => array(
						'type'      => 'Custom',
						'end-type'  => 'After',
						'end'       => null,
						'end-count' => 5,
						'custom'    => array(
							'interval'  => 1,
							'same-time' => 'yes',
							'type'      => 'Weekly',
							'day'       => array(
								(int) date( 'N', $start_date ),
							),
						),
					),
				),// end rules array
			);
		}

		$event_id = tribe_create_event( $args );

		// mark event, venue, and organizers as generated from tribe-cli
		add_post_meta( $event_id, Meta_Keys::$generated_meta_key, 1 );
		add_post_meta( $venue, Meta_Keys::$generated_meta_key, 1 );
		add_post_meta( $organizer, Meta_Keys::$generated_meta_key, 1 );

		return $event_id;

	}
}
