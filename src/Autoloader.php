<?php

class Tribe__Cli__Autoloader {

	/**
	 * @var array A prefix to path map for autoloading.
	 */
	protected $class_prefixes = array();

	/**
	 * @var array
	 */
	protected $classes = array();

	/**
	 * Registers a prefix and path couple in the autoloader.
	 *
	 * Classes are loaded from the specified path removing the prefix.
	 * Example:
	 *
	 *      $autoloader->register_prefix( 'Tribe__Events__', TRIBE_EVENTS_FILE . '/src/Tribe' );
	 *
	 * To load the "Tribe__Events__Admin__Timezone_Settings" class the autoloader will look for the
	 * TRIBE_EVENTS_FILE . '/src/Tribe/Admin/Timezone_Settings.php' file.
	 *
	 * @param string $prefix The class prefix, must include the trailing '__'
	 * @param string $path   The absolute path to the source folder, use '/' as directory separator
	 */
	public function register_prefix( $prefix, $path ) {
		$this->class_prefixes[ $prefix ] = $path;
	}

	/**
	 * Loads a class managed by this autoloader if possible.
	 *
	 * @param string $class The class to autoload
	 *
	 * @return bool Whether the class was found and included or not.
	 */
	public function load_class( $class ) {
		if ( 0 !== strpos( $class, 'Tribe__' ) ) {
			return false;
		}

		if ( array_key_exists( $class, $this->classes ) ) {
			include_once( $this->classes[ $class ] );

			return true;
		}

		foreach ( $this->class_prefixes as $class_prefix => $src_path ) {
			if ( 0 === strpos( $class, $class_prefix ) ) {
				$src_path   = rtrim( $src_path, '/' );
				$class_path = $src_path . '/' . str_replace( array( $class_prefix, '__' ), array( '', '/' ), $class ) . '.php';
				if ( file_exists( $class_path ) ) {
					include_once $class_path;

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Registers a single class to path mapping.
	 *
	 * @param string $class
	 * @param string $path The absolute path to the class file.
	 */
	public function register_class( $class, $path ) {
		$this->classes[ $class ] = $path;
	}
}