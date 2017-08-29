<?php

class Tribe__CLI__Events__Generator__API {

	/**
	 * Singleton to instantiate the class.
	 *
	 * @return Tribe__CLI__Events__Generator__API
	 */
	public static function instance() {

		/**
		 * @var $instance null|Tribe__CLI__Events__Generator__API
		 */
		static $instance;

		if ( ! $instance ) {
			$instance = new self;
		}

		return $instance;

	}

	/**
	 * Constructor method.
	 */
	public function __construct() {

		// Nothing to do here

	}

	/**
	 * Generate multiple events at once.
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
	 * @return int ID of the event that was created. False if insert failed.
	 */
	public function generate_event() {

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

		$content = implode( ' ', array( $this->generate_text(), $this->generate_text(), $this->generate_text() ) );

		$statuses = array(
			'publish',
			'draft',
		);

		$status = $statuses[ wp_rand( 0, count( $statuses ) - 1 ) ];

		$venue = array(
			'post_status' => 'publish',
			'Venue'       => $this->generate_text( 20, 50 ),
			'Country'     => 'US',
			'Address'     => wp_rand( 10, 5000 ) . ' ' . $this->generate_text( 5 ) . ' St',
			'City'        => 'Phoenix',
			'State'       => 'AZ',
			'Zip'         => '85012',
			'Phone'       => wp_rand( 101, 999 ) . '-' . wp_rand( 101, 999 ) . '-' . wp_rand( 1001, 9999 ),
		);

		$venue = tribe_create_venue( $venue );

		$organizer = array(
			'post_status' => 'publish',
			'Organizer'   => $this->generate_text( 10, 30 ),
			'Email'       => sanitize_title_with_dashes( $this->generate_text( 10 ) ) . '@' . sanitize_title_with_dashes( $this->generate_text( 10 ) ) . '.com',
			'Phone'       => wp_rand( 101, 999 ) . '-' . wp_rand( 101, 999 ) . '-' . wp_rand( 1001, 9999 ),
		);

		$organizer = tribe_create_organizer( $organizer );

		$args = array(
			'post_title'     => $this->generate_text( 100 ),
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

		return $event_id;

	}

	public function generate_text( $max_length = 0, $offset = 0 ) {

		$ipsum = array(
			"There's so many poorly chosen words in that sentence.",
			"Steve Holt!",
			"Get me a vodka rocks. And a piece of toast.",
			"I don't understand the question, and I won't respond to it.",
			"There's only one man I've ever called a coward, and that's Brian Doyle Murray. No, what I'm calling you is a television actor.",
			"Guy's a pro.",
			"He'll want to use your yacht, and I don't want this thing smelling like fish.",
			"Really? Did nothing cancel?",
			"Well, what do you expect, mother?",
			"That's what it said on 'Ask Jeeves.'",
			"As you may or may not know, Lindsay and I have hit a bit of a rough patch.",
			"Army had half a day.",
			"Say goodbye to these, because it's the last time!",
			"I've opened a door here that I regret.",
			"Marry me.",
			"It's a hug, Michael. I'm hugging you.",
			"I'm half machine. I'm a monster.",
			"Bad news. Andy Griffith turned us down. He didn't like his trailer.",
			"That's why you always leave a note!",
			"I don't criticize you! And if you're worried about criticism, sometimes a diet is the best defense.",
			"Now, when you do this without getting punched in the chest, you'll have more fun.",
			"We just call it a sausage.",
			"What's Spanish for 'I know you speak English?'",
			"First place chick is hot, but has an attitude, doesn't date magicians.",
			"It's called 'taking advantage.' It's what gets you ahead in life.",
			"No! I was ashamed to be SEEN with you. I like being with you.",
			"I care deeply for nature.",
			"Oh, you're gonna be in a coma, all right.",
			"There's so many poorly chosen words in that sentence.",
			"Jayne, your mouth is talking. You might wanna look to that.",
			"First rule of battle, little one ... don't ever let them know where you are... WHOO-HOO! I'M RIGHT HERE! I'M RIGHT HERE! YOU WANT SOME O' ME?! YEAH YOU DO! COME ON! COME ON! AAAAAH! Whoo-hoo! ... 'Course, there're other schools of thought.",
			"Yes sir, Captain Tightpants!",
			"Psychic, though? That sounds like something out of science fiction. We live in a spaceship, dear.",
			"Mercy is the mark of a great man. Guess I'm just a good man. Well, I'm all right.",
			"Well, my time of not taking you seriously is coming to a middle.",
			"Let's go be bad guys!",
			"Curse your sudden but inevitable betrayal!",
			"Am I a lion? I don't think of myself as a lion. You might as well, though, I have a mighty roar.",
			"Ten percent of nothin' is ... let me do the math here ... nothin' into nothin' ... carry the nothin' ...",
			"Course you couldn't buy an invite with a diamond the size of a testicle, but I got my hands on a couple.",
			"I swear by my pretty floral bonnet, I will end you.",
			"Every man there go back inside or we will blow a new crater in this little moon.",
			"You know what the chain of command is? It's the chain I go get and beat you with until you understand who's in ruttin charge here.",
			"Someone ever tries to kill you, you try to kill 'em right back!",
			"Here lies my beloved Zoe, my autumn flower ... somewhat less attractive now that she's all corpsified and gross.",
			"Also? I can kill you with my brain.",
			"Oh, I think you might wanna reconsider that last part. See, I married me a powerful ugly creature.",
			"If anyone gets nosy, just ...you know ... shoot 'em. Shoot 'em? Politely, of course.",
			"I cannot abide useless people.",
			"I've been under fire before. Well ... I've been in a fire. Actually, I was fired. I can handle myself.",
			"How did your brain even learn human speech?",
			"Just get us on the ground! That part will happen pretty definitely!",
			"Next time you want to stab me in the back, have the guts to do it to my face.",
			"Man walks down the street in a hat like that, you know he's not afraid of anything.",
			"You're welcome on my boat. God ain't.",
			"You see, but you do not observe. The distinction is clear.",
			"The world is full of obvious things which nobody by any chance ever observes.",
			"My name is Sherlock Holmes. It is my business to know what other people don't know.",
			"You will not apply my precept. How often have I said to you that when you have eliminated the impossible, whatever remains, however improbable, must be the truth? We know that he did not come through the door, the window, or the chimney. We also know that he could not have been concealed in the room, as there is no concealment possible. When, then, did he come?",
			"Come, Watson, come! The game is afoot. Not a word! Into your clothes and come!",
			"Mediocrity knows nothing higher than itself; but talent instantly recognizes genius.",
			"Elementary, my dear Watson.",
			"Education never ends, Watson. It is a series of lessons, with the greatest for the last.",
			"Is there any point to which you would wish to draw my attention? To the curious incident of the dog in the night-time. The dog did nothing in the night-time. That was the curious incident.",
			"I am the most incurably lazy devil that ever stood in shoe leather.",
			"You know my method. It is founded upon the observation of trifles.",
			"Holmes and Watson are on a camping trip. In the middle of the night Holmes wakes up and gives Dr. Watson a nudge. 'Watson' he says, 'look up in the sky and tell me what you see.' 'I see millions of stars, Holmes,' says Watson. 'And what do you conclude from that, Watson?' Watson thinks for a moment. 'Well,' he says, 'astronomically, it tells me that there are millions of galaxies and potentially billions of planets. Astrologically, I observe that Saturn is in Leo. Horologically, I deduce that the time is approximately a quarter past three. Meterologically, I suspect that we will have a beautiful day tomorrow. Theologically, I see that God is all-powerful, and we are small and insignificant. Uh, what does it tell you, Holmes?' 'Watson, you idiot! Someone has stolen our tent!'",
			"Show Holmes a drop of water and he would deduce the existence of the Atlantic. Show it to me and I would look for a tap. That was the difference between us.",
			"I must apologize for calling so late, and I must further beg you to be so unconventional as to allow me to leave your house presently by scrambling over your back garden wall.",
			"I have always held, too, that pistol practice should be distinctly an open-air pastime; and when Holmes, in one of his queer humours, would sit in an armchair with his hair-trigger and a hundred Boxer cartridges and proceed to adorn the opposite wall with a patriotic V.R. done in bullet pocks, I felt strongly that neither the atmosphere nor the appearance of our room was improved by it.",
			"Man, or at least criminal man, has lost all enterprise and originality. As to my own little practice, it seems to be degenerating into an agency for recovering lost lead pencils and giving advice to young ladies from boarding-schools.",
			"Tis but a scratch. A scratch?! Your arm's off!. No, it isn't.",
			"Always look on the bright side of life.",
			"Strange women lying in ponds distributing swords is no basis for a system of government!",
			"He's not pining, he's passed on. This parrot is no more. He has ceased to be. He's expired and gone to meet his maker. He's a stiff, bereft of life, he rests in peace. He's rung down the curtain and joined the choir invisible. This is an ex-parrot!",
			"And now for something completely different.",
			"You've got two empty halves of coconuts and you're banging them together!",
			"NOBODY expects the Spanish Inquisition!",
			"I wanted to be... A LUMBERJACK!",
			"No it can't! An argument is a connected series of statements intended to establish a proposition.",
			"One, two, five! Three, sir. Three!",
			"Then shalt thou count to three, no more, no less. Three shall be the number thou shalt count, and the number of the counting shall be three. Four shalt thou not count, neither count thou two, excepting that thou then proceed to three. Five is right out.",
			"Are you suggesting that coconuts migrate?",
			"I don't think there's a punch-line scheduled, is there?",
			"Of course, it’s a bit of a jump, isn’t it? I mean, er… chartered accountancy to lion taming in one go… You don’t think it might be better if you worked your way towards lion taming, say via banking?",
			"The Castle Aaahhhgggg - our quest is at an end.",
			"Vikings? There ain’t no vikings here. Just us honest farmers. The town was burning, the villagers were dead. They didn’t need those sheep anyway. That’s our story and we’re sticking to it.",
		);

		// Truffle shuffle
		shuffle( $ipsum );
		shuffle( $ipsum );

		$paragraph = implode( ' ', array( $ipsum[0], $ipsum[1], $ipsum[2], $ipsum[3], $ipsum[4] ) );

		if ( 0 < $max_length ) {
			$paragraph = trim( substr( $paragraph, $offset, $max_length ) );
		}

		return $paragraph;

	}

}
