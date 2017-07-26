<?php

class Tribe__Events__Generator__Main {

	/**
	 * The current version of Event Generator.
	 *
	 * @var string
	 */
	const VERSION = '0.1';

	/**
	 * Required Events Calendar Version.
	 *
	 * @var string
	 */
	const REQUIRED_TEC_VERSION = '4.5.4';

	/**
	 * Prefix to use for hooks.
	 *
	 * @var string
	 */
	private $hook_prefix = 'tribe-events-generator-';

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
	 * Singleton to instantiate the class.
	 *
	 * @return Tribe__Events__Generator__Main
	 */
	public static function instance() {

		/**
		 * @var $instance null|Tribe__Events__Generator__Main
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

		$this->plugin_path = trailingslashit( EVENTS_GENERATOR_DIR );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
		$this->plugin_url  = trailingslashit( plugins_url( $this->plugin_dir ) );
		$this->plugin_slug = 'events-generator';

		// Hook to 11 to make sure this gets initialized after tec
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );

	}

	/**
	 * Bootstrap the plugin with the plugins_loaded action.
	 */
	public function plugins_loaded() {

		$this->autoloading();
		$this->register_active_plugin();

		$mopath = $this->plugin_dir . 'lang/';
		$domain = 'tribe-events-generator';

		// If we don't have Common classes load the old fashioned way
		if ( ! class_exists( 'Tribe__Main' ) ) {
			load_plugin_textdomain( $domain, false, $mopath );
		} else {
			// This will load `wp-content/languages/plugins` files first
			Tribe__Main::instance()->load_text_domain( $domain, $mopath );
		}

		if ( ! $this->should_run() ) {
			// Display notice indicating which plugins are required
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			return;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'tribe-events-generator', 'Tribe__Events__Generator__CLI__Events' );
		}

	}

	/**
	 * Registers this plugin as being active for other tribe plugins and extensions.
	 *
	 * @return bool Indicates if Tribe Common wants the plugin to run
	 */
	public function register_active_plugin() {

		if ( ! function_exists( 'tribe_register_plugin' ) ) {
			return true;
		}

		return tribe_register_plugin( EVENTS_GENERATOR_FILE, __CLASS__, self::VERSION );

	}

	/**
	 * Sets up class autoloading for this plugin
	 */
	public function autoloading() {

		if ( ! class_exists( 'Tribe__Autoloader' ) ) {
			return;
		}

		$autoloader = Tribe__Autoloader::instance();
		$autoloader->register_prefix( 'Tribe__Events__Generator__', dirname( __FILE__ ), 'events-generator' );
		$autoloader->register_autoloader();

	}

	/**
	 * Each plugin required by this one to run
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

		$requisite_plugins = array(
			array(
				'short_name'   => 'The Events Calendar',
				'class'        => 'Tribe__Events__Main',
				'thickbox_url' => 'plugin-install.php?tab=plugin-information&plugin=the-events-calendar&TB_iframe=true',
				'min_version'  => self::REQUIRED_TEC_VERSION,
				'ver_compare'  => 'Tribe__Events__Main::VERSION',
			),
		);

		return $requisite_plugins;

	}

	/**
	 * Should the plugin run? Are all of the appropriate items in place?
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
	 * Hooked to the admin_notices action
	 */
	public function admin_notices() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$links = array();

		foreach ( $this->get_requisite_plugins() as $plugin ) {
			$links[] = sprintf( '<a href="%1$s" class="thickbox" title="%2$s">%3$s</a>', esc_attr( $plugin['thickbox_url'] ), esc_attr( $plugin['short_name'] ), esc_html( $plugin['short_name'] ) );
		}

		$message = sprintf( esc_html__( 'To begin using The Events Calendar: Event Generator, please install and activate the latest versions of %1$s, %2$s, %3$s, %4$s, and %5$s.', 'tribe-events-community-events' ), $links[0], $links[1], $links[2], $links[3], $links[4] );

		printf( '<div class="error"><p>%s</p></div>', $message );

	}

}
