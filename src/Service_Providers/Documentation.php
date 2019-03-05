<?php
namespace Tribe\CLI\Service_Providers;

/**
 * Class Documentation
 *
 * @since 0.2.6
 */
class Documentation extends Base {

	/**
	 * Returns the display name of this functionality.
	 *
	 * @since 0.2.6
	 *
	 * @return string
	 */
	public function get_display_name() {
		return 'Plugin Documentation Generator';
	}

	/**
	 * Returns each plugin required by this one to run
	 *
	 * @since 0.2.6
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
		return [];
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 0.2.6
	 */
	public function register() {
		if ( ! $this->should_run() ) {
			// Display notice indicating which plugins are required
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'tribe doc', $this->container->make( 'Tribe\\CLI\\Documentation\\Command' ), array( 'shortdesc' => $this->get_display_name() ) );
		}
	}
}
