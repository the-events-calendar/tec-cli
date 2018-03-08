<?php

/**
 * Class Tribe__Cli__Service_Providers__Utils
 *
 * @since 0.2.4
 */
class Tribe__Cli__Service_Providers__Utils extends Tribe__Cli__Service_Providers__Base {

	/**
	 * Returns each plugin required by this one to run
	 *
	 * @since 0.2.4
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
		// Utils should always be loaded, no Modern Tribe specific plugins required.
		return array();
	}

	/**
	 * Returns the display name of this functionality.
	 *
	 * @since 0.2.4
	 *
	 * @return string
	 */
	protected function get_display_name() {
		return 'Modern Tribe CLI utilities';
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 0.2.4
	 */
	public function register() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'tribe post-transient', $this->container->make( 'Tribe__Cli__Utils__Post_Transients' ) );
		}
	}
}