<?php
/*-------------------------------------------------------------------------------------*
 * API of the Container.
 * See implementation on Bing_Boards_Container_Default
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

Interface Bing_Boards_Container {

	/**
	 * @return Bing_Boards_Admin
	 */
	public function admin();


	/**
	 * @param $user_key
	 *
	 * Bing_Boards_API
	 */
	public function api( $user_key = null );

	/**
	 * @return Bing_Boards_API_Integration
	 */
	public function api_integration();

	/**
	 * @return Bing_Boards_Data_Architecture
	 */
	public function data_architecture();

	/**
	 * @return Bing_Boards_Data_Wrangler
	 */
	public function data_wrangler();

	/**
	 * @return Bing_Boards_Template_Loader
	 */
	public function template_loader();

	/**
	 * @return Bing_Boards_AJAX
	 */
	public function ajax();

}
