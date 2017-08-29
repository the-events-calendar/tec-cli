<?php

abstract class Tribe__CLI__Service_Providers__Base extends tad_DI52_ServiceProvider {

	/**
	 * Returns each plugin required by this one to run
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
	abstract protected function get_requisite_plugins();

	/**
	 * Should the plugin run? Are all of the appropriate items in place?
	 *
	 * @return bool
	 */
	public function should_run() {

		foreach ( $this->get_requisite_plugins() as $plugin ) {
			if ( ! class_exists( $plugin['class'] ) ) {
				return false;
			}

			if ( ! isset( $plugin['min_version'] ) || ! isset( $plugin['ver_compare'] ) ) {
				continue;
			}

			$active_version = constant( $plugin['ver_compare'] );

			if ( null === $active_version ) {
				return false;
			}

			if ( version_compare( $plugin['min_version'], $active_version, '>=' ) ) {
				return false;
			}
		}

		return true;

	}

	/**
	 * Returns the display name of this functionality.
	 *
	 * @return string
	 */
	abstract protected function get_display_name();

	/**
	 * Hooked to the admin_notices action
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$links = array();

		foreach ( $this->get_requisite_plugins() as $plugin ) {
			$links[] = sprintf( '<a href="%1$s" class="thickbox" title="%2$s">%3$s</a>', esc_attr( $plugin['thickbox_url'] ),
				esc_attr( $plugin['short_name'] ), esc_html( $plugin['short_name'] ) );
		}

		$links    = implode( ', ', $links );
		$template = esc_html__( 'To begin using ' . $this->get_display_name() . ', please install and activate the latest versions of %1$s.',
			'tribe-cli' );
		$message  = sprintf( $template, $links );

		printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message );
	}

	/**
	 * Binds and sets up implementations at boot time.
	 */
	public function boot() {
		// no-op
	}
}
