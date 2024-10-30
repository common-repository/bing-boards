<?php
/*-------------------------------------------------------------------------------------*
 * Class container for dependency injection
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards_Container_Default implements Bing_Boards_Container {

	/**
	 * Integration with the WordPress admin.
	 * @var Bing_Boards_Admin
	 */
	private $_admin = null;


	/**
	 * Handler of the Bing Boards API
	 * @var Bing_Boards_API
	 */
	private $_api = null;

	/**
	 * Handler of the Bing Boards API integration
	 * @var Bing_Boards_API_Integration
	 */
	private $_api_integration = null;

	/**
	 * Handler of data types
	 * @var Bing_Boards_Data_Architecture
	 */
	private $_data_architecture = null;

	/**
	 * Validation and process over data
	 * @var Bing_Boards_Data_Wrangler
	 */
	private $_data_wrangler = null;


	/**
	 * Helper for including template files
	 * @var Bing_Boards_Template_Loader
	 */
	private $_template_loader = null;

	/**
	 * AJAX Handlers
	 * @var Bing_Boards_AJAX
	 */
	private $_ajax = null;


	/**
	 * Returns an instance of the Bing_Boards_Admin class
	 * used to integrate this plugin with the WordPress admin.
	 *
	 * @return Bing_Boards_Admin
	 */
	public function admin() {
		// Lazy instantiation
		if ( empty( $this->admin ) )
			$this->_admin = new Bing_Boards_Admin( $this );

		return $this->_admin;
	}


	/**
	 * Returns an instance of the Bing_Boards_API class
	 * used to handle Bing Boards API integration.
	 *
	 * @param $user_key
	 *
	 * @return Bing_Boards_API
	 */
	public function api(  $user_key = null ) {
		return new Bing_Boards_API( $this, $user_key );
	}

	/**
	 * @return Bing_Boards_API_Integration
	 */
	public function api_integration() {
		if ( empty( $this->_api_integration ) )
			$this->_api_integration = new Bing_Boards_API_Integration( $this );

		return $this->_api_integration;
	}

	/**
	 * Returns an instance of the Bing_Boards_Data_Architecture
	 * class used to handle data types.
	 *
	 * @return Bing_Boards_Data_Architecture
	 */
	public function data_architecture() {
		if ( empty( $this->_data_architecture ) )
			$this->_data_architecture = new Bing_Boards_Data_Architecture( $this );

		return $this->_data_architecture;
	}

	/**
	 * Returns an instance of the Bing_Boards_Data_Wrangler
	 * class used to handle data.
	 *
	 * @return Bing_Boards_Data_Wrangler
	 */
	public function data_wrangler() {
		if ( empty( $this->_data_wrangler ) )
			$this->_data_wrangler = new Bing_Boards_Data_Wrangler( $this );

		return $this->_data_wrangler;
	}

	/**
	 * Returns an instance of Bing_Boards_Template_Loader
	 * used to include template files
	 *
	 * @return Bing_Boards_Template_Loader
	 */

	public function template_loader() {

		if ( empty( $this->_template_loader ) )
			$this->_template_loader = new Bing_Boards_Template_Loader( $this );

		return $this->_template_loader;

	}

	/**
	 * Returns an instance of Bing_Boards_AJAX
	 * used to handle AJAX requests
	 *
	 * @return Bing_Boards_AJAX
	 */

	public function ajax() {

		if ( empty( $this->_ajax ) )
			$this->_ajax = new Bing_Boards_AJAX( $this );

		return $this->_ajax;

	}


}
