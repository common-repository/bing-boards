<?php
/*-------------------------------------------------------------------------------------*
 * WordPress admin interface
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards_Admin {

	/**
	 * Container of classes for dependency injection
	 * @var Bing_Boards_Container
	 */
	private $_container;

	/**
	 * Handle of the about page screen
	 * @var string
	 */
	private $_about_page_slug;

	/**
	 * Name of the action executed when we need to scrap a local post.
	 */
	public static $SCRAP_ACTION = 'bing-scrap-local';

	/**
	 * Class constructor
	 */
	public function __construct( Bing_Boards_Container $container ) {
		$this->_container = $container;
		add_action( 'init', array( $this, 'hooks' ) );
	}

	/**
	 * Registers actions and filters
	 */
	public function hooks() {
		add_action( 'add_meta_boxes',         array( $this, 'handle_core_metaboxes'        ), 1     );
		add_action( 'add_meta_boxes',         array( $this, 'register_our_metaboxes'       ), 2     );
		add_action( 'admin_enqueue_scripts',  array( $this, 'scripts_and_styles'           ), 10, 1 );
		add_action( 'transition_post_status', array( $this, 'search_terms_metabox_save'    ),  5, 3 );
		add_action( 'admin_init',             array( $this, 'maybe_scrap_local_post'       ), 10    );
		add_action( 'admin_head',             array( $this, 'maybe_bing_activation_notice' )        );
		add_action( 'admin_notices',          array( $this, 'non_editable_warning'         )        );
		add_action( 'admin_menu',             array( $this, 'add_about_page'               )        );


		/* User profile key and ID */
		add_action( 'show_user_profile',          array( $this, 'add_key_field_to_user_profile'    )  );
		add_action( 'edit_user_profile',          array( $this, 'add_key_field_to_user_profile'    )  );
		add_action( 'personal_options_update',    array( $this, 'save_key_field_from_user_profile' )  );
		add_action( 'edit_user_profile_update',   array( $this, 'save_key_field_from_user_profile' )  );
		add_action( 'user_profile_update_errors', array( $this, 'validate_author_data'             )  );

	    add_filter( 'media_view_strings', array( $this, 'customize_media_manager' ), 10, 2 );
		add_filter( 'post_row_actions',   array( $this, 'remove_quick_edit'       ), 10, 2 );
		add_filter( 'manage_edit-bing_board_columns',        array( $this, 'add_status_column' )        );
		add_action( 'manage_bing_board_posts_custom_column', array( $this, 'do_status_column'  ), 10, 2 );


        add_filter( 'bulk_actions-edit-bing_board', '__return_empty_array' );

		add_filter( 'pre_post_title',       array( $this, 'mask_empty_panel'   ) );
		add_filter( 'pre_post_content',     array( $this, 'mask_empty_panel'   ) );
		add_filter( 'wp_insert_post_data',  array( $this, 'unmask_empty_panel' ) );

	}


	public function mask_empty_panel( $value ) {
		if ( empty( $value ) ) {
			return ' ';
		}

		return $value;
	}

	function unmask_empty_panel( $data ) {
		if ( ' ' == $data['post_title'] ) {
			$data['post_title'] = '';
		}
		if ( ' ' == $data['post_content'] ) {
			$data['post_content'] = '';
		}

		return $data;
	}

	/**
	 * Enqueues the required scipts and styles
	 */
	public function scripts_and_styles( $hook ) {

		global $post;

		// Bail if it's not the post edit screen
		if ( ! in_array( $hook, array( 'post-new.php', 'post.php' ) ) )
			return;

		// Bail if it's not a Board
		$data_architecture = $this->_container->data_architecture();
		if ( $data_architecture->BOARD_TYPE !== $post->post_type )
			return;

		$template_loader = $this->_container->template_loader();
		$api             = $this->_container->api();

		wp_enqueue_media( array( 'post' => $post ) );

		wp_enqueue_style( 'bing-boards', $template_loader->get_resources_url() . 'bing-boards.min.css', array(), Bing_Boards::VERSION );

		wp_enqueue_script( 'tribe-placeholder', $template_loader->get_resources_url() . 'jquery.placeholder.min.js', array( 'jquery' ), Bing_Boards::VERSION );

		wp_enqueue_script( 'jquery-jcarousel', $template_loader->get_resources_url() . 'jquery.jcarousel.min.js', array( 'jquery' ), Bing_Boards::VERSION );

		wp_enqueue_script( 'spinjs', $template_loader->get_resources_url() . 'spin.min.js', array( 'jquery' ), Bing_Boards::VERSION );

		wp_enqueue_script( 'bing-boards', $template_loader->get_resources_url() . 'bing-boards.min.js', array( 'jquery', 'media-upload', 'media-views', 'jquery-ui-sortable', 'tribe-placeholder' ), Bing_Boards::VERSION );

		$user_data         = $this->_container->data_wrangler()->get_user_key( $post->post_author );
		$user_has_bing_key = ( empty ( $user_data['key'] ) ) ? false : true;

        wp_localize_script( 'bing-boards', 'BingBoards',
			array(
				'add_panel_nonce'         => $this->_container->ajax()->add_panel_nonce(),
				'delete_panel_nonce'      => $this->_container->ajax()->delete_panel_nonce(),
				'scrap_post_nonce'        => $this->_container->ajax()->scrap_post_nonce(),
				'sort_nonce'              => $this->_container->ajax()->sort_nonce(),
				'embed_nonce'             => $this->_container->ajax()->embed_nonce(),
				'user_has_bing_key_nonce' => $this->_container->ajax()->user_has_bing_key_nonce(),
				'txt_replace_image'       => __( 'Replace Media', 'bing-boards' ),
				'txt_ays_delete'          => __( 'Are you sure you want to delete this panel?', 'bing-boards' ),
				'txt_ays'                 => __( 'You have unsaved changes. Are you sure you want to discard them?', 'bing-boards' ),
				'txt_create_new_panel'    => __( 'Create a New Panel', 'bing-boards' ),
				'txt_choose_link'         => __( 'Click here to add a link for this panel >', 'bing-boards' ),
				'txt_new_panel_from_post' => __( 'New Panel from Post', 'bing-boards' ),
				'body_char_limit'         => $api->BODY_MAX_LENGTH,
				'panel_title_char_limit'  => $api->PANEL_TITLE_MAX_LENGTH,
				'board_title_char_limit'  => $api->BOARD_TITLE_MAX_LENGTH,
				'link_title_char_limit'   => $api->LINK_TITLE_MAX_LENGTH,
				'search_term_min_chars'   => $api->SEARCH_TERM_MIN_LENGTH,
				'search_term_max_chars'   => $api->SEARCH_TERM_MAX_LENGTH,
				'panels_limit'            => $api->PANELS_LIMIT,
				'user_has_bing_key'       => $user_has_bing_key,
				'submit_errors'           => array(
					0 => __( 'Please enter a Bing Board title.', 'bing-boards' ),
                    1 => sprintf( __( 'Please limit your Bing Board title to %d characters.', 'bing-boards' ), $api->BOARD_TITLE_MAX_LENGTH ),
                    2 => sprintf( __( "You do not have a Bing key associated with your account. Please <a href='%s'>add a bing key to your account</a>.", 'bing-boards' ), get_edit_user_link($post->post_author).'#bing-key' ),
                    3 => __( 'It appears that one or more of the panels have errors in them. Please click on the red panel(s) to fix the errors.', 'bing-boards' ),
                    4 => __( 'You need at least one panel to submit a board.', 'bing-boards' ),
                    5 => __( 'Please enter at least one suggested search term.', 'bing-boards' ),
                    6 => sprintf( __( 'Each search term should have between %d and %d characters.', 'bing-boards' ), $api->SEARCH_TERM_MIN_LENGTH, $api->SEARCH_TERM_MAX_LENGTH  ),
                    7 => sprintf( __( "Sorry, bing boards can't have more than %d panels.", 'bing-boards' ), $api->PANELS_LIMIT ),

                )
			)
		);
	}

	/**
	 * Registers the about page
	 */
	public function add_about_page(){
		$this->_about_page_slug = add_submenu_page( 'edit.php?post_type=bing_board', __( 'About', 'bing-boards' ), __( 'About', 'bing-boards' ), 'edit_posts', 'bing-about', array( $this, 'do_about_page' ) );
	}

	/**
	 * Renders the about page
	 */
	public function do_about_page() {
		$key_error = false;

		if ( isset( $_POST['bing_key'] ) ) {
			$e = new WP_Error();
			$this->validate_author_data( $e );

			if ( empty( $e->errors ) )
				$this->save_key_field_from_user_profile( get_current_user_id() );
			else
				$key_error = $e->get_error_message();

		}

		$key = $this->_container->data_wrangler()->get_user_key( get_current_user_id() );

		$this->_container->template_loader()->load_template( 'about-page.template.php',
			array(
				'current_user_key' => $key['key'],
				'key_error'        => $key_error
			)
		);
	}

	/**
	 * Moves core's 'Author' metabox to the right side (by default)
	 * and removes the 'Slug' metabox.
	 *
	 * @param $post_type
	 */
	public function handle_core_metaboxes( $post_type ) {

		$data_architecture = $this->_container->data_architecture();

		// Only do it if it's our main CPT
		if ( $data_architecture->BOARD_TYPE !== $post_type )
			return;

		// Bail if the user doesn't have permissions
		$post_type_object = get_post_type_object( $post_type );
		if ( ! ( is_super_admin() || current_user_can( $post_type_object->cap->edit_others_posts ) ) )
			return;

		//Let's remove the metaboxes we don't want
		remove_meta_box( 'authordiv', $post_type, 'normal' );
		remove_meta_box( 'slugdiv',   $post_type, 'normal' );
		remove_meta_box( 'submitdiv', $post_type, 'side' );

		// and re-add the authors one, but on the side, if the board is editable

		global $post;

		if ( empty( $post ) || $this->_container->api_integration()->is_editable( $post->ID ) )
			add_meta_box( 'authordivside', __( 'Author' ), 'post_author_meta_box', $post_type, 'side', 'low' );

	}

	/**
	 * Maybe enqueues the banner with the welcome message and the tutorial link
	 * It'll show only one time ever in the global scope.
	 * In the bing boards page it will show always until the user press the skip button.
	 */
	public function maybe_bing_activation_notice() {
		if ( ! empty( $_GET['bing_skip_notice'] ) )
			update_user_meta( get_current_user_id(), 'bing_activation_notice_skip', true );

		global $post;
		$data_architecture = $this->_container->data_architecture();

		$post_type = ! empty( $_GET['post_type'] ) ? $_GET['post_type'] : null;
		$post_type = ! empty( $post ) ? $post->post_type : $post_type;

		if ( $data_architecture->BOARD_TYPE !== $post_type && get_option( 'bing_activation_notice_global_skip' ) !== false )
			return;

		update_option( 'bing_activation_notice_global_skip', true );

		$user_skip = get_user_meta( get_current_user_id(), 'bing_activation_notice_skip', true );
		if ( ! empty( $user_skip ) )
			return;

		$template_loader = $this->_container->template_loader();
		wp_enqueue_style( 'bing-notices', $template_loader->get_resources_url() . 'bing-notices.min.css', array(), Bing_Boards::VERSION );

		add_action( 'admin_notices', array( $this, 'bing_activation_notice' ) );
	}

	/**
	 * Shows the banner for the activation notice
	 */
	public function bing_activation_notice() {

		$s = get_current_screen();

		if ( ! empty( $s ) && $s->id == $this->_about_page_slug )
			return;

		$this->_container->template_loader()->load_template( 'activation-notice.template.php',
			array(
				'skip_url'  => add_query_arg( 'bing_skip_notice', '1' ),
				'about_url' => admin_url( 'edit.php?post_type=bing_board&page=bing-about' )
			)
		);
	}

	/**
	 * Registers our custom metaboxes for the Board edit screen
	 *
	 * @param $post_type
	 */
	public function register_our_metaboxes( $post_type ) {
		$data_architecture = $this->_container->data_architecture();

		// Registers the "Create Panel from Post" metabox for the allowed types
		$types_to_scrap = $data_architecture->get_post_types_for_scrapper_metabox();
		foreach ( (array) $types_to_scrap as $type ) {
			add_meta_box( 'bingscrapdiv', __( 'Bing Boards', 'bing-boards' ), array( $this, 'scrap_metabox_inside' ), $type, 'side', 'default' );
		}

		// Only continue it if it's our main CPT
		if ( $data_architecture->BOARD_TYPE !== $post_type )
			return;

		// Register the metaboxes
		add_meta_box( 'submitdiv',      __( 'Bing Submission',      'bing-boards' ), array( $this, 'submit_metabox_inside'       ), $post_type, 'side',   'high'    );
		add_meta_box( 'searchtermsdiv', __( 'Suggest Search Terms', 'bing-boards' ), array( $this, 'search_terms_metabox_inside' ), $post_type, 'side',   'default' );
		add_meta_box( 'bingpanelsdiv',  __( 'Panels',               'bing-boards' ), array( $this, 'panels_metabox_inside'       ), $post_type, 'normal', 'high'    );

	}

	/**
	 *  Implement our own submit / status metabox
	 */
	public function submit_metabox_inside() {
		global $action, $post;

		$dw  = $this->_container->data_wrangler();
		$api = $this->_container->api_integration();

		$post_type        = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_publish      = current_user_can( $post_type_object->cap->publish_posts );
		$can_delete       = current_user_can( "delete_post", $post->ID );
		$user_data        = $dw->get_user_key( $post->post_author );
		$can_api          = ( empty ( $user_data['key'] ) ) ? false : true;
		$status           = $api->get_status_name( $post->ID );
		$is_editable      = $api->is_editable( $post->ID );
		$submit_text      = $is_editable ? __( 'Submit to Bing', 'bing-boards' ) : __( 'Resubmit to Bing', 'bing-boards' );

		$this->_container->template_loader()->load_template( 'publish-metabox.template.php',
			array(
				'action'           => $action,
				'post'             => $post,
				'post_type'        => $post_type,
				'post_type_object' => $post_type_object,
				'can_publish'      => $can_publish,
				'can_delete'       => $can_delete,
				'can_api'          => $can_api,
				'status'           => $status,
				'is_editable'      => $is_editable,
				'submit_text'      => $submit_text
			) );
	}

	/**
	 *  Implement our Panels metabox
	 */
	public function panels_metabox_inside() {
		global $action, $post;

		$can_publish = current_user_can( 'edit_post', $post->ID );
		$panels      = $this->_container->data_wrangler()->get_all_panels_for_board( $post->ID );
		$api         = $this->_container->api();
		$is_editable = $this->_container->api_integration()->is_editable( $post->ID );

		// Insert the panels loop template
		$this->_container->template_loader()->load_template( 'panels-metabox.template.php',
			array(
				'action'      => $action,
				'post'        => $post,
				'can_publish' => $can_publish,
				'panels'      => $panels,
				'is_editable' => $is_editable
			), true );

		// Insert the live editor template
		$this->_container->template_loader()->load_template( 'live-editor.template.php',
			array(
				'body_max_length' => $api->BODY_MAX_LENGTH,
				'user_data'       => $this->_container->data_wrangler()->get_user_data( $post->post_author )
			), false );

	}

	/**
	 *  Implement our Search Tersm metabox
	 */
	public function search_terms_metabox_inside() {

		global $post;

		$api           = $this->_container->api();
		$data_wrangler = $this->_container->data_wrangler();
		$can_publish   = current_user_can( 'edit_post', $post->ID );
		$search_terms  = $data_wrangler->get_search_terms( $post->ID );

		$search_terms  = array_map( 'esc_html', $search_terms );

		$this->_container->template_loader()->load_template( 'search-terms-metabox.template.php',
			array(
				'post'              => $post,
				'can_publish'       => $can_publish,
				'search_terms'      => $search_terms,
				'search_term_count' => $api->SEARCH_TERMS_MAX_ITEMS
			), true );
	}

	/**
	 * Implements our "Create Panel from Post" metabox
	 */
	public function scrap_metabox_inside() {
		global $post;

		$data_architecture = $this->_container->data_architecture();

		$url = sprintf( 'post-new.php?post_type=%s&scrap_id=%d&action=%s', $data_architecture->BOARD_TYPE, $post->ID, self::$SCRAP_ACTION );

		$this->_container->template_loader()->load_template( 'scrap-metabox.template.php',
			array(
				'post'       => $post,
				'action_url' => wp_nonce_url( admin_url( $url ), self::$SCRAP_ACTION )
			)
		);
	}

	/**
	 * Hooks into the Board's transition_post_status action and save the contents
	 * of the Search Terms texbox as meta for the board.
	 *
	 * It's hooking into transition_post_status and not the common post_save because
	 * the api integration needs this data on publish_post.
	 *
	 */
	public function search_terms_metabox_save( $new_status, $old_status, $post ) {

		$data_architecture = $this->_container->data_architecture();
		$data_wrangler     = $this->_container->data_wrangler();
		$template_loader   = $this->_container->template_loader();

		$nonce_action = 'search-terms-metabox.template.php';
		$nonce_name   = $template_loader->filename_to_nonce_name( $nonce_action );

		/* Bail unless this is an actual valid Board save */
		if (
			$data_architecture->BOARD_TYPE !== get_post_type( $post->ID )
			|| wp_is_post_revision( $post->ID )
			|| wp_is_post_autosave( $post->ID )
			|| ! current_user_can( 'edit_post', $post->ID )
			|| empty( $_POST['bing_search_terms'] )
			|| empty( $_POST[$nonce_name] )
			|| ! wp_verify_nonce( $_POST[$nonce_name], $nonce_action )
		)
			return;

		$data_wrangler->save_search_terms( $post->ID, (array) $_POST['bing_search_terms'] );

	}

	/**
	 * Checks if this is a scrap local post request, and does it.
	 */
	public function maybe_scrap_local_post() {

		// Bail if it's not our action
		if (
			! is_admin()
			|| empty( $_GET['action'] )
			|| $_GET['action'] !== self::$SCRAP_ACTION
			|| empty( $_GET['scrap_id'] )
			|| empty( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( $_GET['_wpnonce'], self::$SCRAP_ACTION )
		)
			return;

		$panel_id = $this->_container->data_wrangler()->create_panel_from_local_post( $_GET['scrap_id'] );

		if ( empty( $panel_id ) )
			return;

		$panel = get_post( $panel_id );

		wp_safe_redirect( admin_url( 'post.php?post=' . $panel->post_parent . '&action=edit' ) );
		exit;

	}

	/**
	 * Tweaks the labels of some elements of the media manager, only if the user
	 * is on a board edit screen.
	 *
	 * @param $strings
	 * @param $post
	 *
	 * @return mixed
	 */
	public function customize_media_manager( $strings, $post ) {
		$data = $this->_container->data_architecture();
		if ( empty($post) || $post->post_type !== $data->BOARD_TYPE )
			return $strings;

		$strings['createGalleryTitle'] = '';
		$strings['insertFromUrlTitle'] = __( 'Insert YouTube or Vimeo URL', 'bing-boards' );
		$strings['insertIntoPost']     = __( 'Select for Board', 'bing-boards' );
		$strings['insertMediaTitle']   = __( 'Insert Image', 'bing-boards' );

		return $strings;
	}

	/**
	 * Add the extra fields on user profile to allow
	 * entering the Bing ID and Key for each user
	 *
	 * @param bool $profileuser
	 * @return void
	 */
	public function add_key_field_to_user_profile( $profileuser = null ){
		if ( empty( $profileuser ) )
			return;

		if ( ! current_user_can( 'edit_user', $profileuser->ID ) )
			return;

		$dw = $this->_container->data_wrangler();

		$data = $dw->get_user_key( $profileuser->ID );

		$bing_key = !empty( $data['key'] ) ? esc_attr( $data['key'] ) : '';
		?>
		<a name="bing-key"></a>
		<table class="form-table">
			<tr>
				<th><label for="bing_key"><?php _e( 'Bing User Key', 'bing-boards'); ?></label></th>
				<td>
					<input type="text" name="bing_key" id="bing_key" value="<?php echo $bing_key; ?>" class="regular-text code" />
                </td>
			</tr>
		</table>
		<?php

	}

	/**
	 * Checks if a user key is valid on the Bing side
	 */
	function validate_author_data( $error ){

		if ( empty( $_POST['bing_key'] ) )
            return;

		$api_data = $this->_container->api( $_POST['bing_key'] )->get_author_data();

		if ( isset( $api_data->errors ) )
            $error->add( 'bing', __( '<strong>ERROR</strong>: The Bing Key is invalid.', 'bing-boards' ), array( 'form-field' => 'bing_key' ) );
	}

	/**
	 * Saves the bing user id and key on profile save
	 *
	 * @param mixed $user_id
	 * @access public
	 * @return void
	 */
	function save_key_field_from_user_profile( $user_id ){
		if ( !current_user_can( 'edit_user', $user_id ) )
			return;

		$dw = $this->_container->data_wrangler();

		$key = !empty( $_POST['bing_key'] ) ? $_POST['bing_key'] : null;

		$dw->save_user_key( $user_id, $key );

	}

	/**
	 * Removs the quick edit link from the boards admin list
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function remove_quick_edit( $actions ) {
		global $post;

		$data_architecture = $this->_container->data_architecture();
		if ( $data_architecture->BOARD_TYPE == $post->post_type )
			unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	/**
	 * Adds a status column to the boards admin list
	 *
	 * @param $posts_columns
	 *
	 * @return mixed
	 */
	public function add_status_column( $posts_columns ) {
		unset( $posts_columns['date'] );
		unset( $posts_columns['cb'] );
		$posts_columns['bing_status'] = __( 'Status' );

		return $posts_columns;
	}

	/**
	 * Renders the content for each row of the status column in the boards admin list
	 * @param $column_name
	 * @param $post_id
	 */
	public function do_status_column( $column_name, $post_id ) {

		if ( $column_name != 'bing_status' )
			return;

		echo $this->_container->api_integration()->get_status_name( $post_id );

	}


	/**
	 * Adds a sync warning at the top of the board editor if the current board
	 * is in a statii where could get out of sync if the user doesn't re-submit it.
	 */
	public function non_editable_warning() {

		$data_architecture = $this->_container->data_architecture();

		$screen = get_current_screen();

		/* Bail unless this is an actual valid Board */
		if ( empty( $screen ) || $screen->id != $data_architecture->BOARD_TYPE )
			return;

		if ( empty( $_GET['post'] ) )
			return;

		if ( $this->_container->api_integration()->is_editable( $_GET['post'] ) )
			return;

		$status = $this->_container->api_integration()->get_status_name( $_GET['post'] );
		$status = '<strong>' . $status . '</strong>';

		printf( '<div id="notice" class="updated"><p>%s</p></div>', sprintf( esc_html__( 'Your Board is %s. Any and all edits will require you to Resubmit the board for review.', 'bing-boards' ), $status ) );

	}


}
