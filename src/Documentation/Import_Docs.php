<?php
namespace Tribe\CLI\Documentation;

use WP_Parser\WP_CLI_Logger;
use WP_CLI;

class Import_Docs extends Abstract_Doc_Command {
	/**
	 * @var string Plugin
	 */
	private $plugin;

	/**
	 * @var string Products taxonomy
	 */
	private $products_taxonomy = 'tribe_products';

	/**
	 * @var array Inserted terms
	 */
	private $inserted_terms;

	/**
	 * Imports WP PHPDoc json file
	 *
	 * @param $args
	 * @param $assoc_args
	 * @throws WP_CLI\ExitException
	 */
	public function import( array $args = null, array $assoc_args = null ) {
		if ( ! function_exists( '\WP_Parser\parse_files' ) ) {
			WP_CLI::error( __( 'Please install and activate WP Parser from https://github.com/WordPress/phpdoc-parser before building documentation.', 'tribe-cli' ) );
		}

		WP_CLI::line();

		if ( ! class_exists( '\WP_Parser\Importer' ) ) {
			WP_CLI::error( __( 'Please install and activate WP Parser from https://github.com/WordPress/phpdoc-parser before importing documentation.', 'tribe-cli' ) );
		}

		$plugin = $this->parse_plugin( $args );
		$file   = $this->parse_file( $plugin, $args );

		// Get the data from the <file>, and check it's valid.
		$phpdoc = false;
		if ( is_readable( $file ) ) {
			$phpdoc = file_get_contents( $file );
		}

		if ( ! $phpdoc ) {
			WP_CLI::error( sprintf( __( "Can't read %1\$s. Does the file exist?", 'tribe-cli' ), $file ) );
		}

		$phpdoc = json_decode( $phpdoc, true );

		if ( is_null( $phpdoc ) ) {
			WP_CLI::error( sprintf( __( "JSON in %1\$s can't be decoded", 'tribe-cli' ), $file ) );
		}

		add_action( 'wp_parser_import_item', [ $this, 'wp_parser_import_item' ], 10, 2 );
		add_filter( 'wp_parser_pre_import_file', [ $this, 'wp_parser_pre_import_file' ], 10 ,2 );

		// Import data
		$this->run_import( $phpdoc );
	}

	/**
	 * Runs the import
	 *
	 * @param $data
	 *
	 * @throws WP_CLI\ExitException
	 */
	private function run_import( $data ) {
		if ( ! wp_get_current_user()->exists() ) {
			WP_CLI::error( __( 'Please specify a valid user: --user=<id|login>', 'tribe-cli' ) );
		}

		// Run the importer
		$importer = new Data_Importer();
		$importer->setLogger( new WP_CLI_Logger() );
		$importer->import( $data, true, false );
		WP_CLI::line();
	}

	/**
	 * Parses out the passed file path
	 *
	 * @param string $plugin
	 * @param array $args
	 *
	 * @return mixed
	 */
	private function parse_file( string $plugin, array $args ) {
		if ( ! isset( $args[1] ) ) {
			return "/tmp/{$plugin}.json";
		}

		return $args[1];
	}

	/**
	 * Preps a parser item for import
	 *
	 * @param $id
	 * @param $data
	 */
	public function wp_parser_import_item( $id, $data ) {
		if ( $this->plugin ) {
			$term = $this->insert_term( $this->plugin['Name'], $this->products_taxonomy, [ 'description' => $this->plugin['Description'] ] );
			if ( ! is_wp_error( $term ) ) {
				wp_set_object_terms( $id, (int) $term['term_id'], $this->products_taxonomy );
			}
		}

		// use category as a taxonomy
		$categories = $this->get_category( $data );

		// If no doc pages are found then we're done with this item.
		if ( empty( $categories ) ) {
			return;
		}

		// connect the item with all the relevent category terms.
		// This is more or less copied from wp-parser class-importer.php import_item()
		foreach ( $categories as $category ) {
			$term = $this->insert_term( $category['content'], 'wp-parser-category' );
			if ( ! is_wp_error( $term ) ) {
				wp_set_object_terms( $id, (int) $term['term_id'], 'wp-parser-category' );
			}
		}
	}

	/**
	 * Given a folder path to a plugin, locate the main plugin file and get the header info.
	 *
	 * @param $plugin_base_path
	 *
	 * @return array|bool
	 */
	private function get_plugin_info( $plugin_base_path ) {

		// Scan the directory for php files.
		$plugin_files = [];
		$plugin_dir   = @opendir( $plugin_base_path );

		if ( $plugin_dir ) {
			while ( ( $plugin_file = readdir( $plugin_dir ) ) !== false ) {
				if ( substr( $plugin_file, 0, 1 ) == '.' ) {
					continue;
				}

				if ( substr( $plugin_file, -4 ) == '.php' ) {
					$plugin_files[] = "{$plugin_base_path}/{$plugin_file}";
				}
			}

			closedir( $plugin_dir );
		}

		// No php files found. Return false.
		if ( empty( $plugin_files ) ) {
			return false;
		}

		// Find the php file with a plugin header
		foreach ( $plugin_files as $plugin_file ) {
			if ( ! is_readable( $plugin_file ) ) {
				continue;
			}

			$plugin_data = get_plugin_data( $plugin_file, false, false ); //Do not apply markup/translate as it'll be cached.

			if ( empty ( $plugin_data['Name'] ) ) {
				continue;
			}

			$plugin_data['Name'] = str_replace( 'The Events Calendar: ', '', $plugin_data['Name'] );
			$plugin_data['Name'] = str_replace( 'Event Tickets: ', '', $plugin_data['Name'] );

			return $plugin_data;
		}
	}

	/**
	 * Intercept file handling in the parser to learn more about the plugin currently being processed.
	 *
	 * @param $return
	 * @param $file
	 *
	 * @return mixed
	 */
	public function wp_parser_pre_import_file( $return, $file ) {

		// get plugin info. Create term with plugin name. Apply term to all posts created in this batch.

		// File scanner based on include/plugin.php
		if ( ! isset( $this->plugin ) ) {
			$this->plugin = $this->get_plugin_info( $file['root'] );
		}

		if ( ! $this->plugin ) {
			wp_die( "Fatal Error: Cannot get plugin info for {$file['root']}." );
		}

		return $return;
	}

	/**
	 * Insert term function copied from wp-parser class-importer.php
	 *
	 * @param       $term
	 * @param       $taxonomy
	 * @param array $args
	 *
	 * @return array|mixed|WP_Error
	 */
	protected function insert_term( $term, $taxonomy, $args = [] ) {
		if ( isset( $this->inserted_terms[ $taxonomy ][ $term ] ) ) {
			return $this->inserted_terms[ $taxonomy ][ $term ];
		}

		$parent = isset( $args['parent'] ) ? $args['parent'] : 0;
		if ( ! $inserted_term = term_exists( $term, $taxonomy, $parent ) ) {
			$inserted_term = wp_insert_term( $term, $taxonomy, $args );
		}

		if ( ! is_wp_error( $inserted_term ) ) {
			$this->inserted_terms[ $taxonomy ][ $term ] = $inserted_term;
		} else {
			WP_CLI::warning( "\tCannot set {$taxonomy} term: " . $term->get_error_message() );
		}

		return $inserted_term;
	}


	/**
	 * Attempt to identify @category tag in php docbloc data.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function get_category( $data = [] ) {
		return wp_list_filter( $data['doc']['tags'], [ 'name' => 'category' ] );
	}
}
