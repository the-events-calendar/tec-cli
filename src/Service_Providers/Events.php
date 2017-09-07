<?php

/**
 * Class Tribe__Cli__Service_Providers__Events
 *
 * @since 0.1.0
 */
class Tribe__Cli__Service_Providers__Events extends Tribe__Cli__Service_Providers__Base {
	/**
	 * Required Events Calendar Version.
	 *
	 * @var string
	 */
	const REQUIRED_TEC_VERSION = '4.5.4';

	/**
	 * Returns the display name of this functionality.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function get_display_name() {
		return 'The Events Calendar: Event Generator';
	}

	/**
	 * Returns each plugin required by this one to run
	 *
	 * @since 0.1.0
	 *
	 * @return array {
	 *      List of required plugins.
	 *
	 *      @param string $short_name   Shortened title of the plugin
	 *      @param string $class        Main PHP class
	 *      @param string $thickbox_url URL to download plugin
	 *      @param string $min_version  Optional. Minimum version of plugin needed.
	 *      @param string $ver_compare  Optional. Constant that stored the currently active version.
	 * }
	 */
	protected function get_requisite_plugins() {
		return array(
			array(
				'short_name'   => 'The Events Calendar',
				'class'        => 'Tribe__Events__Main',
				'thickbox_url' => 'plugin-install.php?tab=plugin-information&plugin=the-events-calendar&TB_iframe=true',
				'min_version'  => self::REQUIRED_TEC_VERSION,
				'ver_compare'  => 'Tribe__Events__Main::VERSION',
			),
		);
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 0.1.0
	 */
	public function register() {
		if ( ! $this->should_run() ) {
			// Display notice indicating which plugins are required
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'tribe-events-generator', $this->container->make( 'Tribe__Cli__Events__Generator__CLI' ) );
		}
	}
}