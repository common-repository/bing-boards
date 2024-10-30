<?php
/*-------------------------------------------------------------------------------------*
 * AJAX Handlers
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards_AJAX {
	/**
	 * Container of classes for dependency injection
	 * @var Bing_Boards_Container
	 */
	private $_container;

	/**
	 * Class constructor
	 */
	public function __construct( Bing_Boards_Container $container ) {
		$this->_container = $container;
		$this->register_handlers();
	}

	/**
	 * Registers all the AJAX handlers
	 */
	public function register_handlers() {
		add_action( 'wp_ajax_bing-add-panel',           array( $this, 'add_panel'               ) );
		add_action( 'wp_ajax_bing-delete-panel',        array( $this, 'delete_panel'            ) );
		add_action( 'wp_ajax_bing-get-panel',           array( $this, 'get_panel'               ) );
		add_action( 'wp_ajax_bing-posts-for-scrapping', array( $this, 'get_posts_for_scrapping' ) );
		add_action( 'wp_ajax_bing-posts-for-linking',   array( $this, 'get_posts_for_linking'   ) );
		add_action( 'wp_ajax_bing-scrap-local-post',    array( $this, 'scrap_local_post'        ) );
		add_action( 'wp_ajax_bing-panels-sort',         array( $this, 'panels_sort'             ) );
		add_action( 'wp_ajax_bing-oembed',              array( $this, 'oembed'                  ) );
		add_action( 'wp_ajax_bing-user-has-key',        array( $this, 'user_has_key'            ) );
	}

	/**
	 * AJAX Wrapper for wp_oembed_get
	 * Will also intercept the response and get the meta info for the embed resouce.
	 * See Bing_Boards_Data_Wrangler->grab_data_from_oembed
	 */
	public function oembed() {

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'embed' ) || empty( $_POST['url'] ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		global $bing_last_embed_data;

		$url         = esc_url( $_POST['url'] );
		$html        = wp_oembed_get( $url );
		$title       = isset( $bing_last_embed_data['title'] ) ? $bing_last_embed_data['title'] : null;
		$thumbnail   = isset( $bing_last_embed_data['thumbnail'] ) ? $bing_last_embed_data['thumbnail'] : null;
		$description = isset( $bing_last_embed_data['description'] ) ? $bing_last_embed_data['description'] : null;

		$this->response(
			true, '',
			array(
				'html'        => $html,
				'title'       => $title,
				'thumbnail'   => $thumbnail,
				'description' => $description,
				'url'         => $url
			)
		);
	}

	/**
	 * AJAX handler for the save panel action (new/update)
	 */
	public function add_panel() {

		$data_architecture = $this->_container->data_architecture();
		$data_wrangler     = $this->_container->data_wrangler();
		$type              = get_post_type_object( $data_architecture->PANEL_TYPE );

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'add_panel' ) || empty( $_POST['board_id'] ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		if ( ! empty( $_POST['panel_id'] ) && ! current_user_can( $type->cap->edit_posts, $_POST['panel_id'] ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$status = $this->_container->data_wrangler()->create_panel( $_POST['board_id'],
			array(
				'ID'           => ! empty( $_POST['panel_id'] ) ? $_POST['panel_id'] : null,
				'post_title'   => $_POST['panel_title'],
				'post_excerpt' => $_POST['panel_content'],
				'menu_order'   => ! empty( $_POST['menu_order'] ) ? $_POST['menu_order'] : 0,
				'link_url'     => ! empty( $_POST['panel_link_url'] ) ? $_POST['panel_link_url'] : null,
				'link_anchor'  => ! empty( $_POST['panel_link_anchor'] ) ? $_POST['panel_link_anchor'] : null,
			)
		);

		if ( empty( $status ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		// Handle thumbnail
		if ( ! empty( $_POST['thumb_id'] ) ) {

			// We got a attachment ID
			if ( is_numeric( $_POST['thumb_id'] ) ) {
				set_post_thumbnail( $status, $_POST['thumb_id'] );

				// We got an url
			} else if ( is_string( $_POST['thumb_id'] ) ) {
				$embed = ! empty( $_POST['thumb_embed'] ) ? $_POST['thumb_embed'] : '';
				$data_wrangler->update_panel_external_media( $status, $_POST['thumb_id'], $embed );
			}

		}

		$response = array(
			'panels'        => $this->get_panels_list_markup( $_POST['board_id'] ),
			'id'            => $status,
			'last_modified' => $this->_container->data_wrangler()->get_panel_last_modified_string( $status )
		);

		$this->response( true, '', $response );

	}

	/**
	 * AJAX handler for the delete panel action
	 */
	public function delete_panel() {

		$data_architecture = $this->_container->data_architecture();
		$type              = get_post_type_object( $data_architecture->PANEL_TYPE );

		if ( empty( $_POST['panel_id'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'delete_panel' ) || ! current_user_can( $type->cap->delete_post, $_POST['panel_id'] ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$status = wp_delete_post( $_POST['panel_id'], true );

		if ( empty( $status ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$this->response( true );

	}


	/**
	 * AJAX handler to retrieve a single panel
	 */
	public function get_panel() {

		$data_architecture = $this->_container->data_architecture();

		if ( empty( $_POST['panel_id'] ) || ! current_user_can( 'edit_posts' ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$panel = get_post( $_POST['panel_id'] );

		if ( empty( $panel ) || $panel->post_type != $data_architecture->PANEL_TYPE )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$response = array(
			'ID'              => $panel->ID,
			'post_title'      => $panel->post_title,
			'post_excerpt'    => $panel->post_excerpt,
			'link_url'        => get_post_meta( $panel->ID, $data_architecture->LINK_URL_META, true ),
			'link_anchor'     => get_post_meta( $panel->ID, $data_architecture->LINK_ANCHOR_META, true ),
			'thumbnail'       => false,
			'thumbnail_id'    => false,
			'thumbnail_embed' => false,
			'last_modified'   => $this->_container->data_wrangler()->get_panel_last_modified_string( $panel )
		);

		$media = $this->_container->data_wrangler()->get_panel_media( $panel->ID, 'large' );

		if ( $media !== false )
			$response = array_merge( $response, $media );

		$this->response( true, '', $response );

	}

	/**
	 * AJAX handler to retrieve a list of posts to show in the "Import from existing post" list
	 */
	public function get_posts_for_scrapping() {
		$data_architecture = $this->_container->data_architecture();

		if ( empty( $_POST['board_id'] ) || ! current_user_can( 'edit_posts' ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$s = ! empty( $_POST['s'] ) ? $_POST['s'] : "";

		$args = array(
			'post_type'      => $data_architecture->get_post_types_for_scrapper_metabox(),
			's'              => $s,
			'posts_per_page' => 5
		);

		$posts_to_scrap = get_posts( $args );

		$posts = array();
		foreach ( $posts_to_scrap as $post_to_scrap ) {
			$post = array(
				'ID'    => $post_to_scrap->ID,
				'title' => $post_to_scrap->post_title
			);

			$att_data = wp_get_attachment_image_src( get_post_thumbnail_id( $post_to_scrap->ID ), 'thumbnail' );

			if ( ! empty( $att_data ) )
				$post['img'] = $att_data[0];

			$posts[] = $post;

		}

		$this->response( true, '', (array) $posts );

	}

	/**
	 * AJAX handler to retrieve a list of posts to show in the internal links list
	 */
	public function get_posts_for_linking() {


		$s = ! empty( $_POST['s'] ) ? $_POST['s'] : "";

		$args = array(
			'post_type'      => 'any',
			's'              => $s,
			'posts_per_page' => 5
		);

		$posts_to_scrap = get_posts( $args );

		$posts = array();
		foreach ( $posts_to_scrap as $post_to_scrap ) {
			$post = array(
				'ID'    => $post_to_scrap->ID,
				'title' => $post_to_scrap->post_title,
				'link'  => get_permalink( $post_to_scrap->ID )
			);

			$att_data = wp_get_attachment_image_src( get_post_thumbnail_id( $post_to_scrap->ID ), 'thumbnail' );

			if ( ! empty( $att_data ) )
				$post['img'] = $att_data[0];

			$posts[] = $post;

		}

		$this->response( true, '', (array) $posts );

	}

	/**
	 * AJAX handler to scrap a local post and create a new panel with its information
	 */
	public function scrap_local_post() {

		$data_architecture = $this->_container->data_architecture();
		$type              = get_post_type_object( $data_architecture->PANEL_TYPE );

		if ( empty( $_POST['board_id'] ) || empty( $_POST['post_id'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'scrap_post' ) || ! current_user_can( $type->cap->edit_posts, $_POST['board_id'] ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$menu_order = ! ( empty( $_POST['menu_order'] ) ) ? $_POST['menu_order'] : 0;

		$this->_container->data_wrangler()->create_panel_from_local_post( $_POST['post_id'], $_POST['board_id'], $menu_order );

		$this->response( true, '', $this->get_panels_list_markup( $_POST['board_id'] ) );

	}

	/**
	 *  AJAX handler for the sort panels action
	 */
	public function panels_sort() {

		$data_architecture = $this->_container->data_architecture();
		$type              = get_post_type_object( $data_architecture->PANEL_TYPE );

		if ( empty( $_POST['panels'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sort' ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		global $wpdb;

		$order = 0;
		foreach ( (array) $_POST['panels'] as $panel ) {
			$order ++;

			$panel_id = absint( str_replace( "bing-panel-", "", $panel ) );
			$sql      = $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = %d where ID = %d", $order, $panel_id );

			$wpdb->query( $sql );

		}

		$this->response( true );
	}

	/**
	 *  AJAX handler to verify if a given user_id has an associated bing key
	 */
	public function user_has_key() {

		if ( empty( $_POST['user_id'] ) || empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'can_api' ) )
			$this->response( false, __( 'Something went wrong', 'bing-boards' ) );

		$user_id = absint( $_POST['user_id'] );

		$user_data = $this->_container->data_wrangler()->get_user_key( $user_id );
		$can_api   = ( empty ( $user_data['key'] ) ) ? false : true;

		$this->response( true, '', array( 'user_has_bing_key' => $can_api, 'message' => sprintf( __( "%s does not have a Bing key. Please <a href='%s'>add a key to %s's account.</a> For more help acquiring a key, please <a href='%s'>read our post about authoring bing boards.</a>", 'bing-boards' ), $user_data["name"], get_edit_user_link( $user_data['id'] ), $user_data["name"], '#' ) ) );

	}

	/************************* HELPERS *************************/

	/**
	 * Nonce generator
	 * @return string
	 */
	public function add_panel_nonce() {
		return wp_create_nonce( 'add_panel' );
	}

	/**
	 * Nonce generator
	 * @return string
	 */
	public function delete_panel_nonce() {
		return wp_create_nonce( 'delete_panel' );
	}

	/**
	 * Nonce generator
	 * @return string
	 */
	public function scrap_post_nonce() {
		return wp_create_nonce( 'scrap_post' );
	}

	/**
	 * Nonce generator
	 * @return string
	 */
	public function sort_nonce() {
		return wp_create_nonce( 'sort' );
	}

	/**
	 * Nonce generator
	 * @return string
	 */
	public function embed_nonce() {
		return wp_create_nonce( 'embed' );
	}

	/**
	 * Nonce generator
	 * @return string
	 */
	public function user_has_bing_key_nonce() {
		return wp_create_nonce( 'can_api' );
	}

	/**
	 * Generates the markup for the panel list.
	 * It's used for most AJAX handlers to return the new list after a change
	 *
	 * @param $board_id
	 *
	 * @return string
	 */
	protected function get_panels_list_markup( $board_id ) {
		ob_start();

		$panels      = $this->_container->data_wrangler()->get_all_panels_for_board( $board_id );
		$board       = get_post( $board_id );
		$is_editable = $this->_container->api_integration()->is_editable( $board_id );

		foreach ( $panels as $panel ) {
			$this->_container->template_loader()->load_template( 'panels-metabox-single-panel.template.php',
				array(
					'panel'       => $panel,
					'post'        => $board,
					'media'       => $this->_container->data_wrangler()->get_panel_media( $panel->ID ),
					'is_editable' => $is_editable
				) );
		}

		return ob_get_clean();
	}

	/**
	 * Wrapper for the AJAX responses
	 *
	 * @param bool   $success
	 * @param string $message
	 * @param array  $data
	 */
	protected function response( $success = false, $message = '', $data = array() ) {

		// Setup the response array
		$response = array(
			'success' => $success,
			'message' => $message,
			'data'    => $data

		);

		// Send back the JSON
		header( 'Content-type: application/json' );
		echo json_encode( $response );
		die();
	}


}
