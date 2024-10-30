<?php
/*-------------------------------------------------------------------------------------*
 * Helps loading templates from the /views/ directory
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards_Template_Loader {

	/**
	 * Container of classes for dependency injection
	 * @var Bing_Boards_Container
	 */
	private $_container;

	/**
	 *  Class constructor
	 */
	public function __construct( Bing_Boards_Container $container ) {
		$this->_container    = $container;
	}

	/**
	 * Includes a template file if exitsts
	 *
	 * @param string      $filename      Name of the file to include
	 * @param array       $vars          Array with variables that will be accessible
	 *                                   from the template file.
	 * @param string|bool $inject_nonce  Whether it should inject a nonce field.
	 *                                   If true, the nonce action will be $filename
	 *                                   and the nonce field name will be the result of
	 *                                   self::filename_to_nonce_name( $filename )
	 *
	 * @action bing_boards_before_load_template called before including the filename
	 * @action bing_boards_after_load_template called after including the filename
	 *
	 * @return bool
	 */
	public function load_template( $filename, $vars = array(), $inject_nonce = false ) {

		$path = trailingslashit( self::get_views_path() ) . $filename;

		if ( ! file_exists( $path ) )
			return false;

		if ( $inject_nonce === true )
			wp_nonce_field( $filename, self::filename_to_nonce_name( $filename ) );

		extract( $vars );

		do_action( 'bing_boards_before_load_template', $filename, $vars, $inject_nonce );

		include $path;

		do_action( 'bing_boards_after_load_template', $filename, $vars, $inject_nonce );

		return true;
	}

	/**
	 * Generates a string to be used as the field name for a nonce, given a filename.
	 * It's used by @{load_template} just to not expose filenames in the markup.
	 *
	 * @param string $filename
	 *
	 * @filter bing_boards_filename_to_nonce_name with the generated nonce name for the given filename
	 *
	 * @return string
	 */
	public function filename_to_nonce_name( $filename ) {
		$nonce_name = "nonce_" . substr( md5( $filename ), 0, 10 );
		$nonce_name = apply_filters( 'bing_boards_filename_to_nonce_name', $nonce_name, $filename );

		return $nonce_name;
	}


	/**
	 * Returns the path to the resources folder
	 *
	 * @filter bing_boards_resources_path with the path to the resources folder
	 *
	 * @return string
	 */
	public function get_resources_path() {
		$resources_folder = trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'resources';
		$resources_folder = apply_filters( 'bing_boards_resources_path', $resources_folder );
		$resources_folder = trailingslashit( $resources_folder );

		return $resources_folder;
	}

	/**
	 * Returns the url to the resources folder
	 *
	 * @filter bing_boards_resources_url with the url to the resources folder
	 *
	 * @return string
	 */
	public function get_resources_url() {
		$resources_folder = trailingslashit( dirname( plugin_dir_url( __FILE__ ) ) ) . 'resources';
		$resources_folder = apply_filters( 'bing_boards_resources_url', $resources_folder );
		$resources_folder = trailingslashit( $resources_folder );

		return $resources_folder;
	}


	/**
	 * Returns the path to the views folder
	 *
	 * @filter bing_boards_views_path with the path to the views folder
	 *
	 * @return string
	 */
	public function get_views_path() {
		$views_folder = trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) ) . 'views';
		$views_folder = apply_filters( 'bing_boards_views_path', $views_folder );
		$views_folder = trailingslashit( $views_folder );

		return $views_folder;
	}

	/**
	 * Returns the url to the views folder
	 *
	 * @filter bing_boards_views_url with the url to the views folder
	 *
	 * @return string
	 */
	public function get_views_url() {
		$views_folder = trailingslashit( dirname( plugin_dir_url( __FILE__ ) ) ) . 'views';
		$views_folder = apply_filters( 'bing_boards_views_url', $views_folder );
		$views_folder = trailingslashit( $views_folder );

		return $views_folder;
	}


}