<?php
/*-------------------------------------------------------------------------------------*
 * Custom post types and taxonomies
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards_Data_Architecture {

	/* These variables were converted from const to improve PHP 5.2 compat */
	/* Our custom types names */
	public $BOARD_TYPE            = 'bing_board';
	public $PANEL_TYPE            = 'bing_panel';
	public $SEARCH_TERMS_META_KEY = '_bing_search_terms';

	/* Stores the url and embed of the main image or video */
	public $MEDIA_META       = '_bing_media_url';
	public $MEDIA_EMBED_META = '_bing_media_embed';

	/* Meta for the panel link */
	public $LINK_URL_META    = '_bing_panel_link_url';
	public $LINK_ANCHOR_META = '_bing_panel_link_anchor';

	public $USER_KEY         = '_bing_user_key';
	public $USER_NAME        = '_bing_user_name';
	public $USER_TITLE       = '_bing_user_title';
	public $USER_PHOTOURL    = '_bing_user_photo';
	public $USER_HOMEPAGEURL = '_bing_user_url';

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
	}

	/**
	 * Registers the filters and action
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_board_post_type' ), 10 );
		add_action( 'init', array( $this, 'register_panel_post_type' ), 10 );
		add_action( 'init', array( $this, 'register_boards_statii'   ), 20 );

		add_filter( 'image_size_names_choose', array( $this, 'image_size_to_media_manager' ) );

	}

	/**
	 * Forces the "large" image in the media manager for the board edit screen
	 * @param $sizes
	 *
	 * @return array
	 */
	function image_size_to_media_manager( $sizes ) {
		global $post;

		if ( empty( $post ) )
			return $sizes;

		if ( $post->post_type != $this->BOARD_TYPE )
			return $sizes;

		$custom_sizes = array(
			'large' => 'Large',
		);

		return $custom_sizes;
	}

	/**
	 * Registers the Boards post type
	 *
	 * @filter bing_boards_register_board_type_args
	 */
	public function register_board_post_type() {

		$labels = array(
			'name'               => __( 'Bing Boards',                    'bing-boards' ),
			'singular_name'      => __( 'Bing Board',                     'bing-boards' ),
			'add_new'            => __( 'Add New',                        'bing-boards' ),
			'add_new_item'       => __( 'Add New Bing Board',             'bing-boards' ),
			'edit_item'          => __( 'Edit Bing Board',                'bing-boards' ),
			'new_item'           => __( 'New Bing Board',                 'bing-boards' ),
			'all_items'          => __( 'All Bing Boards',                'bing-boards' ),
			'view_item'          => __( 'View Bing Board',                'bing-boards' ),
			'search_items'       => __( 'Search Bing Boards',             'bing-boards' ),
			'not_found'          => __( 'No Bing Boards found',           'bing-boards' ),
			'not_found_in_trash' => __( 'No Bing Boards found in Trash',  'bing-boards' ),
			'menu_name'          => __( 'Bing Boards',                    'bing-boards' )
		);

		$supports = array( 'title', 'author' );

		$args = array(
			'labels'              => $labels,
			'menu_position'       => 5,
			'hierarchical'        => false,
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'query_var'           => false,
			'rewrite'             => false,
			'has_archive'         => false,
			'show_ui'             => true,
			'show_in_nav_menus'   => false,
			'show_in_menu'        => true,
			'capability_type'     => 'post',
			'supports'            => $supports
		);

		$args = apply_filters( 'bing_boards_register_board_type_args', $args );

		register_post_type( $this->BOARD_TYPE, $args );

	}

	/**
	 * Registers the Panel post type
	 *
	 * @filter bing_boards_register_panel_type_args
	 */
	public function register_panel_post_type() {

		$labels = array(
			'name'               => __( 'Bing Panels',              'bing-boards' ),
			'singular_name'      => __( 'Bing Panel',               'bing-boards' ),
			'add_new'            => __( 'Add New',                  'bing-boards' ),
			'add_new_item'       => __( 'Add New Panel',            'bing-boards' ),
			'edit_item'          => __( 'Edit Panel',               'bing-boards' ),
			'new_item'           => __( 'New Panel',                'bing-boards' ),
			'all_items'          => __( 'All Panels',               'bing-boards' ),
			'view_item'          => __( 'View Panel',               'bing-boards' ),
			'search_items'       => __( 'Search Panels',            'bing-boards' ),
			'not_found'          => __( 'No Panels found',          'bing-boards' ),
			'not_found_in_trash' => __( 'No Panels found in Trash', 'bing-boards' ),
		);

		$supports = array( 'title', 'content', 'thumbnail' );

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => true,
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'query_var'           => false,
			'rewrite'             => false,
			'has_archive'         => false,
			'show_ui'             => false,
			'show_in_nav_menus'   => false,
			'show_in_menu'        => false,
			'capability_type'     => 'post',
			'supports'            => $supports
		);

		$args = apply_filters( 'bing_boards_register_panel_type_args', $args );

		register_post_type( $this->PANEL_TYPE, $args );
	}

	/**
	 *  Registers the custom Boards statii
	 */
	public function register_boards_statii() {
		$statii = self::get_boards_statii();

		foreach ( $statii as $key => $name ) {

			//Escape the name in case someone tries to inject HTML in a lang file
			$escaped_name = esc_html( $name );

			register_post_status( $key,
				array(
				     'label'                     => $escaped_name,
				     'public'                    => true,
				     'exclude_from_search'       => false,
				     'show_in_admin_all_list'    => true,
				     'show_in_admin_status_list' => true,
				     'label_count'               => _n_noop( "$escaped_name <span class='count'>(%s)</span>", "$escaped_name <span class='count'>(%s)</span>" ),
				)
			);
		}

	}

	/**
	 * Returns the different statii boards can have
	 * @static
	 *
	 * @filter bing_boards_boards_statii with the array of custom statii
	 *
	 * @return array
	 */
	public static function get_boards_statii() {

		$statii = array(
			'bing-submitted'       => __( 'Submitted',         'bing-boards' ),
			'bing-published'       => __( 'Published',         'bing-boards' ),
			'bing-locked'          => __( 'Locked for Review', 'bing-boards' ),
			'bing-action-required' => __( 'Action Required',   'bing-boards' ),
		);

		$statii = apply_filters( 'bing_boards_boards_statii', $statii );

		return $statii;
	}


	/**
	 * Defines what post types should get the "Create Panel from Post" metabox.
	 * @static
	 *
	 * @filter bing_boards_post_types_for_scrapper_metabox
	 *
	 * @return mixed|void
	 */
	public function get_post_types_for_scrapper_metabox() {

		$types = array( 'post' );

		$types = apply_filters( 'bing_boards_post_types_for_scrapper_metabox', $types );

		return $types;
	}


}
