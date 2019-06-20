<?php
namespace Tribe\CLI\Documentation;

use WP_CLI;

class Scan_Docs extends Abstract_Doc_Command {
	/**
	 * Scans plugin and generates WP PHPDoc json file
	 *
	 * @param $args
	 * @param $assoc_args
	 * @throws WP_CLI\ExitException
	 */
	public function scan( array $args = null, array $assoc_args = null ) {
		if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
			WP_CLI::error( __( 'Please install and activate WP Parser from https://github.com/WordPress/phpdoc-parser before building documentation.', 'tribe-cli' ) );
		}

		$plugin      = $this->parse_plugin( $args );
		$output_file = $this->parse_file( $plugin, $assoc_args );
		$data        = $this->get_data();
		$json        = json_encode( $data, JSON_PRETTY_PRINT );
		$result      = file_put_contents( $output_file, $json );
		WP_CLI::line();

		if ( false === $result ) {
			WP_CLI::error( sprintf( __( 'Problem writing %1$s bytes of data to %2$s', 'tribe-cli' ), strlen( $json ), $output_file ) );
		}

		WP_CLI::success( sprintf( __( 'Data exported to %1$s', 'tribe-cli' ), $output_file ) );
		WP_CLI::line();
	}

	private function get_data() {

		WP_CLI::line( sprintf( __( 'Extracting PHPDoc from %1$s. This may take a few minutes...', 'tribe-cli' ), $this->plugin_dir ) );
		$files = $this->collect_files();

		if ( $files instanceof \WP_Error ) {
			WP_CLI::error( sprintf( __( 'Problem with %1$s: %2$s', 'tribe-cli' ), $this->plugin_dir, $files->get_error_message() ) );
		}

		$output = \WP_Parser\parse_files( $files, $this->plugin_dir );

		return $output;
	}

	private function collect_files() {
		$directory = new \RecursiveDirectoryIterator( $this->plugin_dir, \FilesystemIterator::FOLLOW_SYMLINKS );
		$filter    = new \RecursiveCallbackFilterIterator( $directory, function ( $current, $key, $iterator ) {
			// Skip hidden files and directories
			if ( $current->getFilename()[0] === '.' ) {
				return false;
			}

			if ( $current->isDir() ) {
				return ! in_array( $current->getFilename(), [
					'.git',
					'__mocks__',
					'common',
					'deprecated',
					'dev',
					'lang',
					'resources',
					'styles',
					'node_modules',
					'tests',
					'vendor',
				] );
			}

			if ( false !== strstr( $current->getFilename(), 'deprecated' ) ) {
				return false;
			}

			return $current->getExtension() === 'php';
		} );

		$iterator  = new \RecursiveIteratorIterator( $filter );
		$files     = [];

		try {
			foreach ( $iterator as $file ) {

				// exclude the PUE helper file
				if ( preg_match( '!PUE/Helper\.php$!', $file->getPathname() ) ) {
					continue;
				}

				$files[] = $file->getPathname();
			}
		} catch ( \UnexpectedValueException $exc ) {
			return new \WP_Error(
				'unexpected_value_exception',
				sprintf( __( 'Directory [%s] contained a directory we can not recurse into', 'tribe-cli' ), $directory )
			);
		}

		return $files;
	}

	/**
	 * Parses out the passed file path
	 *
	 * @param string $plugin
	 * @param array $assoc_args
	 *
	 * @return mixed
	 */
	private function parse_file( string $plugin, array $assoc_args ) {
		if ( ! isset( $assoc_args['output'] ) ) {
			return sys_get_temp_dir() . "/{$plugin}.json";
		}

		return $assoc_args['output'];
	}
}
