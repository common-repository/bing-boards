<?php
/*-------------------------------------------------------------------------------------*
 * Interface for scraping content from remote URLs
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards_Content_Scrapper_Local implements Bing_Boards_Content_Scrapper {

	/**
	 * Cache of the post being screpped
	 * @var null|WP_Post
	 */
	private $_post;

	/**
	 * Size of the attachments we want
	 */
	public static $IMAGE_SIZE = 'large';

	/**
	 * Class constructor.
	 *
	 * @param $content_identifier int Post_id
	 */
	public function __construct( $content_identifier ) {
		$this->_post = get_post( (int) $content_identifier );
	}

	/**
	 * @return string URL of the main image
	 */
	public function get_main_image() {
		if ( empty( $this->_post ) )
			return "";

		$thumbnail_id = get_post_thumbnail_id( $this->_post->ID );
		$image        = null;

		if ( ! empty( $thumbnail_id ) )
			$image = wp_get_attachment_image_src( $thumbnail_id, self::$IMAGE_SIZE );

		if ( ! empty( $image ) ) {
			$image = $image[0];
		} else {
			$image = $this->first_image();
		}

		return $image;
	}

	/**
	 * @return array (of strings)
	 */
	public function get_all_images() {
		if ( empty( $this->_post ) )
			return array();

		$attachments = get_posts( array(
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_parent'    => $this->_post->ID
		) );

		if ( empty( $attachments ) )
			return array();

		$return = array();

		foreach ( $attachments as $attachment ) {
			$image = wp_get_attachment_image_src( $attachment->ID, self::$IMAGE_SIZE );
			if ( ! empty( $image ) )
				$return[] = $image[0];
		}

		return $return;
	}

	/**
	 * @return string Content of the exceprt
	 */
	public function get_excerpt() {
		if ( empty( $this->_post ) )
			return "";

		if ( ! empty( $this->_post->post_excerpt ) )
			return strip_tags( html_entity_decode( $this->_post->post_excerpt ) );

		return wp_trim_words( $this->get_body(), 55, '' );

	}

	/**
	 * @return string Title of the content
	 */
	public function get_title() {
		if ( empty( $this->_post ) )
			return "";

		return html_entity_decode ( get_the_title( $this->_post->ID ) );
	}

	/**
	 * @return string Main content
	 */
	public function get_body() {
		if ( empty( $this->_post ) )
			return "";

		$content = $this->_post->post_content;
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );

		return html_entity_decode ( wp_kses( $content, array() ) );
	}

	/**
	 * Returns the permalink to this post
	 * @return null|string
	 */
	public function get_url() {
		if ( empty( $this->_post ) )
			return null;

		return get_permalink( $this->_post->ID );
	}

	/**
	 * Get's the post thumbnail id
	 * @return int|null
	 */
	public function get_thumbnail_id() {
		if ( empty( $this->_post ) )
			return null;

		return get_post_thumbnail_id( $this->_post->ID );
	}

	private function first_image() {

		$output    = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $this->_post->post_content, $matches );
		$first_img = '';

		if ( ! empty( $matches[1][0] ) )
			$first_img = $matches[1][0];

		return $first_img;
	}

}
