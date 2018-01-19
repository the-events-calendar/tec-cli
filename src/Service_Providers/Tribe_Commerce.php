<?php

/**
 * Class Tribe__Cli__Service_Providers__Tribe_Commerce
 *
 * @since 0.2.0
 */
class Tribe__Cli__Service_Providers__Tribe_Commerce extends Tribe__Cli__Service_Providers__Base  {

	const REQUIRED_TICKETS_VERSION = '4.7dev';

	/**
	 * Returns each plugin required by this one to run
	 *
	 * @since 0.2.0
	 *
	 * @return array {
	 *      List of required plugins.
	 *
	 * @param string $short_name   Shortened title of the plugin
	 * @param string $class        Main PHP class
	 * @param string $thickbox_url URL to download plugin
	 * @param string $min_version  Optional. Minimum version of plugin needed.
	 * @param string $ver_compare  Optional. Constant that stored the currently active version.
	 *                             }
	 */
	protected function get_requisite_plugins() {
		return array(
			array(
				'short_name'   => 'Event Tickets (with Tribe Commerce)',
				'class'        => 'Tribe__Tickets__Main',
				'thickbox_url' => 'plugin-install.php?tab=plugin-information&plugin=event-tickets&TB_iframe=true',
				'min_version'  => self::REQUIRED_TICKETS_VERSION,
				'ver_compare'  => 'Tribe__Tickets__Main::VERSION',
			),
		);
	}

	/**
	 * Returns the display name of this functionality.
	 *
	 * @since 0.2.0
	 *
	 * @return string
	 */
	protected function get_display_name() {
		return 'Tribe Commerce WP-CLI Tools';
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 0.2.0
	 */
	public function register() {
		if ( ! $this->should_run() ) {
			// Display notice indicating which plugins are required
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'commerce', $this->container->make( 'Tribe__Cli__Commerce__Command' ), array('shortdesc' => $this->get_display_name()) );
		}
	}
}