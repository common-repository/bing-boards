<?php
/*-------------------------------------------------------------------------------------*
 * Interface for scraping content from remote URLs
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

interface Bing_Boards_Content_Scrapper {

	/**
	 * Class constructor.
	 *
	 * @param mixed $content_identifier
	 * Each implementer can choose how it looks like
	 * (URL, post_id, content string, etc)
	 */
	public function __construct( $content_identifier );

	/**
	 * @return string URL of the main image
	 */
	public function get_main_image();

	/**
	 * @return array (of strings)
	 */
	public function get_all_images();

	/**
	 * @return string Content of the exceprt
	 */
	public function get_excerpt();

	/**
	 * @return string Title of the content
	 */
	public function get_title();

	/**
	 * @return string Main content
	 */
	public function get_body();

	/**
	 * @return string URL of the content
	 */
	public function get_url();

}