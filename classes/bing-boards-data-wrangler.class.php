<?php
/*-------------------------------------------------------------------------------------*
 * Validation and process over data
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards_Data_Wrangler {

	/**
	 * Container of classes for dependency injection
	 * @var Bing_Boards_Container
	 */
	private $_container;

	/**
	 *  Class constructor
	 */
	public function __construct( Bing_Boards_Container $container ) {
		$this->_container = $container;
		add_action( 'init', array( $this, 'hooks' ), 5 );
		add_filter( 'oembed_dataparse', array( $this, 'grab_data_from_oembed' ), 10, 3 );
	}

	/**
	 * Registers the filters and action
	 */
	public function hooks() {
		add_action( 'before_delete_post', array( $this, 'maybe_delete_board' ), 10, 1 );
	}

	/**
	 * Sanitizes and saves the search terms for a Board
	 *
	 * @param int    $board_id
	 * @param array $terms
	 */
	public function save_search_terms( $board_id, $terms ) {

		$data_architecture = $this->_container->data_architecture();

		// Make them unique
		$terms = array_unique( $terms );

		// Remove extra spaces
		$terms = array_map( 'trim', $terms );

		// Remove empty elements
		$terms = array_filter( $terms );

		//sanitize and trim each term
		$terms = array_map( 'esc_html', $terms );

		if ( empty( $terms ) )
			delete_post_meta( $board_id, $data_architecture->SEARCH_TERMS_META_KEY );
		else
			update_post_meta( $board_id, $data_architecture->SEARCH_TERMS_META_KEY, $terms );

	}

	/**
	 * Returns an array of search terms for a board or an empty array.
	 *
	 * @param $board_id
	 *
	 * @return array
	 */
	public function get_search_terms( $board_id ) {

		$data_architecture = $this->_container->data_architecture();

		$terms = get_post_meta( $board_id, $data_architecture->SEARCH_TERMS_META_KEY, true );

		if ( empty( $terms ) )
			$terms = array();

		$terms = array_values( $terms );
		$terms = array_map( 'html_entity_decode', $terms );

		return $terms;

	}

	/**
	 * Scraps the given $post_id and creates a panel using it's info.
	 * If a $board_id is given, the panel will be attached to it. If not,
	 * a clean Board will be created.
	 *
	 * @param int      $post_id
	 * @param int|null $board_id
	 * @param int      $menu_order (optional)
	 *
	 * @return int|null
	 */
	public function create_panel_from_local_post( $post_id, $board_id = null, $menu_order = 0 ) {

		if ( empty( $board_id ) ) {
			$board_id = $this->create_board();
		}

		$data_architecture = $this->_container->data_architecture();

		$scrapper = new Bing_Boards_Content_Scrapper_Local( $post_id );

		$args = array(
			'post_title'   => $scrapper->get_title(),
			'post_excerpt' => $scrapper->get_excerpt(),
			'menu_order'   => $menu_order,
			'link_anchor'  => $scrapper->get_title(),
			'link_url'     => $scrapper->get_url()
		);

		$panel_id = $this->create_panel( $board_id, $args );

		if ( empty( $post_id ) )
			return null;

		if ( $scrapper->get_thumbnail_id() )
			set_post_thumbnail( $panel_id, $scrapper->get_thumbnail_id() );

		if ( $scrapper->get_main_image() )
			$this->update_panel_external_media( $panel_id, $scrapper->get_main_image() );

		return $panel_id;

	}

	/**
	 * Updates or deletes the external media reference for a panel.
	 * It'll also try store a cache of the embed.
	 * If a embed is given, it'll use it instead of fetching it.
	 *
	 * @param int    $panel_id
	 * @param string $url
	 * @param string $embed
	 */
	public function update_panel_external_media( $panel_id, $url, $embed = '' ) {
		$data_architecture = $this->_container->data_architecture();

		$url = esc_url( $url );

		if ( empty( $url ) ) {
			delete_post_meta( $panel_id, $data_architecture->MEDIA_META );
			delete_post_meta( $panel_id, $data_architecture->MEDIA_EMBED_META );
			return;
		}

		//Remove thumbnail in case it has.
		delete_post_meta( $panel_id, '_thumbnail_id' );
		delete_post_meta( $panel_id, $data_architecture->MEDIA_EMBED_META );


		update_post_meta( $panel_id, $data_architecture->MEDIA_META, $url );

		$l_url = strtolower($url);

		if (
			( empty( $embed ) || $embed == $url ) &&
			( strstr( $l_url, 'youtube' ) !== false || strstr( $l_url, 'vimeo' ) !== false )
		) {
			$embed = wp_oembed_get( $url );
		}

		if ( ! empty( $embed ) && $embed != $url ) {
			update_post_meta( $panel_id, $data_architecture->MEDIA_EMBED_META, $embed );
		}

	}

	/**
	 * Hooks into the Board's delete action and deletes its Panels
	 *
	 * @param $post_id
	 */
	public function maybe_delete_board( $post_id ) {

		$data_architecture = $this->_container->data_architecture();

		/* Bail unless this is an actual valid Board */
		if (
			$data_architecture->BOARD_TYPE !== get_post_type( $post_id )
			|| ! current_user_can( 'delete_post', $post_id )
		)
			return;

		$panels = $this->get_all_panels_for_board( $post_id );

		foreach ( $panels as $panel ) {
			wp_delete_post( $panel->ID, true );
		}

	}

	/**
	 * Returns an array with the url and embed of external meta,
	 * or false if there's no external media.
	 *
	 * If the external is an image, embed will be false.
	 *
	 * @param $panel_id
	 *
	 * @return array|bool
	 */
	public function get_panel_external_media( $panel_id ) {
		$data_architecture = $this->_container->data_architecture();

		$url = get_post_meta( $panel_id, $data_architecture->MEDIA_META, true );

		if ( empty( $url ) )
			return false;

		$embed = get_post_meta( $panel_id, $data_architecture->MEDIA_EMBED_META, true );

		return array( 'url' => $url, 'embed' => $embed );
	}

	/**
	 * Returns an array with the panel media, wether is internal or external.
	 *
	 * @param        $panel_id
	 * @param string $size
	 *
	 * @return array|bool
	 */
	public function get_panel_media( $panel_id, $size = 'thumbnail' ) {

		$response = array(
			'thumbnail'       => false,
			'thumbnail_id'    => false,
			'thumbnail_embed' => false
		);

		if ( has_post_thumbnail( $panel_id ) ) {
			$thumb_id = get_post_thumbnail_id( $panel_id );
			$thumb    = wp_get_attachment_image_src( $thumb_id, $size );
			if ( ! empty( $thumb[0] ) )
				$response['thumbnail'] = $thumb[0];

			$response['thumbnail_id'] = $thumb_id;
		} else if ( $external = $this->get_panel_external_media( $panel_id ) ) {
			$response['thumbnail']       = $external['url'];
			$response['thumbnail_embed'] = $external['embed'];
		}else{
			return false;
		}

		return $response;

	}


	/**
	 * Inserts a new board.
	 *
	 * @param array $args same as wp_insert_post
	 *
	 * @filter bing_boards_create_board_args
	 *
	 * @return int|\WP_Error
	 */
	public function create_board( $args = array() ) {
		$data_architecure = $this->_container->data_architecture();

		$defaults = array(
			'post_title'  => '',
			'post_status' => 'draft',
			'post_type'   => $data_architecure->BOARD_TYPE
		);

		$args = wp_parse_args( $args, $defaults );

		$args = apply_filters( 'bing_boards_create_board_args', $args );

		return wp_insert_post( $args );
	}

	/**
	 * Inserts a new panel.
	 *
	 * @param int   $board_id
	 * @param array $args same as wp_insert_post
	 *
	 * @filter bing_boards_create_panel_args
	 *
	 * @return int|\WP_Error
	 */
	public function create_panel( $board_id, $args = array() ) {
		$data_architecure = $this->_container->data_architecture();

		$args['post_parent'] = $board_id;

		$defaults = array(
			'post_title'  => '',
			'post_status' => 'publish'
		);

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = $data_architecure->PANEL_TYPE;

		$args = apply_filters( 'bing_boards_create_panel_args', $args );

		$id = wp_insert_post( $args );

		if ( ! empty( $args['link_url'] ) )
			update_post_meta( $id, $data_architecure->LINK_URL_META, esc_url( $args['link_url'] ) );
		else
			delete_post_meta( $id, $data_architecure->LINK_URL_META );

		if ( ! empty( $args['link_anchor'] ) )
			update_post_meta( $id, $data_architecure->LINK_ANCHOR_META, $args['link_anchor'] );
		else
			delete_post_meta( $id, $data_architecure->LINK_ANCHOR_META );

		return $id;
	}

	/**
	 * Returns a string representing the last modified date
	 *
	 * @param WP_Post|int $panel
	 *
	 * @return string
	 */
	public function get_panel_last_modified_string( $panel ) {

		if ( is_numeric( $panel ) )
			$panel = get_post( $panel );

		if ( empty( $panel->post_modified ) )
			return '';

		return sprintf( __( "Saved on %s", "bing-boards" ), mysql2date( get_option( 'date_format' ), $panel->post_modified ) . ' ' . mysql2date( get_option( 'time_format' ), $panel->post_modified ) );

	}


	/**
	 * generate a featured image from the featured video thumbnail
	 *
	 * @param      $return
	 * @param      $data
	 * @param null $url
	 *
	 * @return mixed
	 */
	public function grab_data_from_oembed( $return, $data, $url = null ) {

		global $bing_last_embed_data;

		$bing_last_embed_data = array(
			'title'       => '',
			'thumbnail'   => '',
			'description' => ''
		);

		if ( !empty( $data->title ) )
			$bing_last_embed_data[ 'title' ] = $data->title;

		if ( !empty( $data->thumbnail_url ) )
			$bing_last_embed_data[ 'thumbnail' ] = $data->thumbnail_url;

		if ( !empty( $data->description ) )
			$bing_last_embed_data[ 'description' ] = $data->description;

		return $return;

	}


	/**
	 * Returns an array of WP_Post objects with the child panels for a given board id
	 *
	 * @param int $board_id
	 *
	 * @filter bing_boards_get_all_panels_for_board
	 *
	 * @return array
	 */
	public function get_all_panels_for_board( $board_id ) {

		$data_architecture = $this->_container->data_architecture();

		$panels_query = new WP_Query(
			array(
				'post_type'      => $data_architecture->PANEL_TYPE,
				'posts_per_page' => - 1,
				'post_parent'    => $board_id,
				'orderby'        => 'menu_order ID',
				'order'          => 'ASC'
			)
		);

		$panels = apply_filters( 'bing_boards_get_all_panels_for_board', $panels_query->posts, $board_id );

		return $panels;
	}


	/**
	 * Gets an array with the bing user id and key
	 *
	 * @param mixed $user_id
	 * @access public
	 * @return array
	 */
	public function get_user_key ( $user_id ){

		$da = $this->_container->data_architecture();

		$user = get_userdata( $user_id );
		$return = array(
			'key'  => get_user_meta( $user_id, $da->USER_KEY, true ),
			'name' => $user->user_nicename
		);

		return $return;
	}

	/**
	 * Saves meta with the user bing key
	 * @param $user_id
	 * @param $key
	 */
	public function save_user_key( $user_id, $key ){

		$da = $this->_container->data_architecture();

		if ( empty( $key ) )
			delete_user_meta( $user_id, $da->USER_KEY );
		else
			update_user_meta( $user_id, $da->USER_KEY, $key );

		do_action( 'bing_boards_user_key_updated', $user_id, $key );

	}

	/**
	 * Saves meta with the user bing info (photo, title, name)
	 * @param $user_id
	 * @param $args
	 */
	public function save_user_data( $user_id, $args ){

		$da = $this->_container->data_architecture();

		$name = !empty( $args['Name'] ) ? $args['Name']	: null;
		if ( empty( $name ) )
			delete_user_meta( $user_id, $da->USER_NAME );
		else
			update_user_meta( $user_id, $da->USER_NAME, $name );

		$title = !empty( $args['Title'] ) ? $args['Title']	: null;
		if ( empty( $title ) )
			delete_user_meta( $user_id, $da->USER_TITLE );
		else
			update_user_meta( $user_id, $da->USER_TITLE, $title );

		$PhotoURL = !empty( $args['PhotoUrl'] ) ? $args['PhotoUrl']	: null;
		if ( empty( $PhotoURL ) )
			delete_user_meta( $user_id, $da->USER_PHOTOURL );
		else
			update_user_meta( $user_id, $da->USER_PHOTOURL, $PhotoURL );

		$HomepageUrl = !empty( $args['HomepageUrl'] ) ? $args['HomepageUrl']	: null;
		if ( empty( $HomepageUrl ) )
			delete_user_meta( $user_id, $da->USER_HOMEPAGEURL );
		else
			update_user_meta( $user_id, $da->USER_HOMEPAGEURL, $HomepageUrl );


	}

	/**
	 * Returns the bing info for a given user
	 * @param $user_id
	 *
	 * @return array
	 */
	public function get_user_data( $user_id ){

		$da = $this->_container->data_architecture();
		$return = array();

		$return['name']        = get_user_meta( $user_id, $da->USER_NAME, true );
		$return['title']       = get_user_meta( $user_id, $da->USER_TITLE, true );
		$return['photourl']    = get_user_meta( $user_id, $da->USER_PHOTOURL, true );
		$return['homepageurl'] = get_user_meta( $user_id, $da->USER_HOMEPAGEURL, true );

		return array_filter( $return );
}

	/**
	 * Returns all the users with a valid bing key associated
	 * @return array
	 */
	public function get_all_valid_users(){
		$da = $this->_container->data_architecture();

		$user_query = new WP_User_Query( array( 'meta_key' => $da->USER_KEY, 'meta_compare' => 'EXISTS', 'fields' => 'ID' ) );

		return $user_query->results;
	}


}
