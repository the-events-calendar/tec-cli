<?php
namespace Tribe\CLI;

use Tribe__Main;
use lucatume\DI52\ServiceProvider;

/**
 * Class Main
 *
 * @since 0.1.0
 */
class Main extends ServiceProvider {

	/**
	 * The current version of Event Generator.
	 *
	 * @var string
	 */
	const VERSION = '0.3.0';

	/**
	 * Plugin directory name.
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * Plugin local path.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Plugin url.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Bootstraps the plugin with the plugins_loaded action.
	 *
	 * @since 0.1.0
	 */
	public function plugins_loaded() {
		$this->register_active_plugin();

		$mopath = $this->plugin_dir . 'lang/';
		$domain = 'tribe-cli';

		// If we don't have Common classes load the old fashioned way
		if ( ! class_exists( 'Tribe__Main' ) ) {
			load_plugin_textdomain( $domain, false, $mopath );
		} else {
			// This will load `wp-content/languages/plugins` files first
			Tribe__Main::instance()->load_text_domain( $domain, $mopath );
		}

	}

	/**
	 * Registers this plugin as being active for other tribe plugins and extensions.
	 *
	 * @since 0.1.0
	 *
	 * @return bool Indicates if Tribe Common wants the plugin to run
	 */
	public function register_active_plugin() {

		if ( ! function_exists( 'tribe_register_plugin' ) ) {
			return true;
		}

		return tribe_register_plugin( TRIBE_CLI_FILE, __CLASS__, self::VERSION );

	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 0.1.0
	 */
	public function register() {
		$this->container->singleton( 'tribe-cli.main', $this );

		$this->plugin_path = trailingslashit( TRIBE_CLI_DIR );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = trailingslashit( plugins_url( $this->plugin_dir ) );
		$this->plugin_slug = 'tribe-cli';

		// Hook to 11 to make sure this gets initialized after tec
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );
	}

	/**
	 * Binds and sets up implementations at boot time.
	 *
	 * @since 0.1.0
	 */
	public function boot() {
		// no-op
	}
}
