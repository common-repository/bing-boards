<?php

class Bing_Boards_API_Integration {

	/* These variables were converted from const to improve PHP 5.2 compat */
	public static $CRON_NAME = 'bing_sync';

	/* Sync data */
	public  $META_SYNC_ID       = '_bing_sync_id';
	public  $META_SYNC_ERROR    = '_bing_sync_result';
	public  $META_SYNC_STATUS   = '_bing_sync_status';
	public  $META_SYNC_CREATED  = '_bing_sync_created';
	public  $META_SYNC_MODIFIED = '_bing_sync_modified';


	/**
	 * Container of classes for dependency injection
	 * @var Bing_Boards_Container
	 */
	private $_container;

	/**
	 * @var WP_Error|null
	 */
	private $last_error = null;

	/**
	 * @param Bing_Boards_Container $container
	 */
	function __construct( Bing_Boards_Container $container ) {
		$this->_container = $container;
		$this->hooks();
	}

	public function hooks() {

		$da = $this->_container->data_architecture();

		//Workflow
		add_action( 'publish_' . $da->BOARD_TYPE,   array( $this, 'maybe_send_board'         ), 10, 1 );
		add_action( 'wp_trash_post',                array( $this, 'maybe_delete_board'       ), 5,  1 );
		add_action( 'before_delete_post',           array( $this, 'maybe_delete_board'       ), 5,  1 );
		add_filter( 'get_post_metadata',            array( $this, 'make_draft_after_untrash' ), 10, 4 );

		// Error handling
		add_filter( 'current_screen',        array( $this, 'check_for_errors'       ), 10, 1 );
		add_action( 'admin_notices',         array( $this, 'admin_notices'          ),  5    );
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages'  ), 10, 1 );

		// Author Data
		add_action( 'bing_boards_user_key_updated', array( $this, 'get_author_data' ), 10, 2 );

		add_action( self::$CRON_NAME, array( $this, 'update_boards_from_bing' ) );

		add_action( 'admin_init', array( $this, 'maybe_force_update_boards_from_bing' ) );

	}

	/**
	 * Force the API sync outside of the cron
	 *
	 * @todo Once we have UI for this then we should use nonce.
	 */
	public function maybe_force_update_boards_from_bing() {

		if ( isset( $_GET['update_bing_boards'] ) ) {
			$this->update_boards_from_bing();
		}

	}


	/**
	 * Gets the latest info for each board and syncs the info back.
	 */
	public function update_boards_from_bing() {
		$processed_keys = array();

		$users = $this->_container->data_wrangler()->get_all_valid_users();
		foreach ( $users as $user ) {
			$key = $this->_container->data_wrangler()->get_user_key( $user );
			if ( empty( $key['key'] ) )
				continue;

			// Short lived cache in case more than an user is using the same bing key
			if ( in_array( $key['key'], $processed_keys ) )
				continue;

			$boards = $this->_container->api( $key['key'] )->get_my_boards();

			if ( empty( $boards->Boards ) )
				continue;

			foreach ( $boards->Boards as $board ) {

				if ( empty( $board->BoardId ) )
					continue;

				$local_board = $this->get_board_from_bing_id( $board->BoardId );

				if ( empty( $local_board ) )
					continue;

				$this->update_sync_meta( $local_board->ID, $board );

			}

			$processed_keys[] = $key['key'];
		}

	}


	/**
	 * Hooks into the Board's post_save action and send the
	 * board to Bing using the API. It's smart enough to detect
	 * new board vs. update.
	 *
	 * @param $post_id
	 */
	public function maybe_send_board( $post_id ) {

		$data_architecture = $this->_container->data_architecture();

		/* Bail unless this is an actual valid Board save */
		if (
			$data_architecture->BOARD_TYPE !== get_post_type( $post_id )
			|| wp_is_post_revision( $post_id )
			|| wp_is_post_autosave( $post_id )
			|| ! current_user_can( 'edit_post', $post_id )
		)
			return;

		$last_result = $this->save_board( $post_id );

		if ( is_wp_error( $last_result ) )
			update_post_meta( $post_id, $this->META_SYNC_ERROR, $last_result );
		else {
			delete_post_meta( $post_id, $this->META_SYNC_ERROR );
			$this->update_sync_meta( $post_id, $last_result );
		}
	}

	/**
	 * Hooks into the Board's delete action and delete the board from Bing
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

		$this->delete_board( $post_id );

	}

	/**
	 * Ensures the board goes to Draft after the user untrash it
	 * @param $meta
	 * @param $object_id
	 * @param $meta_key
	 * @param $single
	 *
	 * @return null|string
	 */
	public function make_draft_after_untrash( $meta, $object_id, $meta_key, $single ) {
		$data_architecture = $this->_container->data_architecture();
		if ( $data_architecture->BOARD_TYPE !== get_post_type( $object_id ) )
			return null;

		if ( $meta_key != '_wp_trash_meta_status' )
			return null;

		$this->clear_meta( $object_id );

		return 'draft';

	}

	/**
	 * Sends the board to the Bing api on publish
	 * @param $post_id
	 *
	 * @return bool|WP_Error
	 */
	public function save_board( $post_id ) {
		$board = $this->build_board_object( $post_id );
		$post  = get_post( $post_id );
		$key   = $this->_container->data_wrangler()->get_user_key( $post->post_author );

		if ( empty( $key['key'] ) )
			return false;

		return $this->_container->api( $key['key'] )->save_board( $board );
	}

	/**
	 * Deletes the board from bing when sent to the trash locally
	 * @param $post_id
	 *
	 * @return array|bool|WP_Error
	 */
	public function delete_board( $post_id ) {

		$BoardId = get_post_meta( $post_id, $this->META_SYNC_ID, true );

		if ( empty( $BoardId ) )
			return false;

		$post = get_post( $post_id );
		$key  = $this->_container->data_wrangler()->get_user_key( $post->post_author );

		if ( empty( $key['key'] ) )
			return false;

		$status = $this->_container->api( $key['key'] )->delete_board( $BoardId );

		if ( ! is_wp_error( $status ) )
			$this->clear_meta( $post_id );

		return $status;

	}

	/**
	 * Removes all the sync meta from a board
	 * @param $post_id
	 */
	protected function clear_meta( $post_id ) {
		delete_post_meta( $post_id, $this->META_SYNC_STATUS );
		delete_post_meta( $post_id, $this->META_SYNC_MODIFIED );
		delete_post_meta( $post_id, $this->META_SYNC_CREATED );
		delete_post_meta( $post_id, $this->META_SYNC_ERROR );
		delete_post_meta( $post_id, $this->META_SYNC_ID );
	}

	/**
	 * Updates all the local sync meta from the information we receive from Bing
	 * @param $board_id
	 * @param $api_result
	 *
	 * @return bool
	 */
	protected function update_sync_meta( $board_id, $api_result ) {

		if ( property_exists( $api_result, 'BoardSummary' ) )
			$summary = $api_result->BoardSummary;
		else
			$summary = $api_result;

		if ( ! property_exists( $summary, 'BoardId' ) )
			return false;

		update_post_meta( $board_id, $this->META_SYNC_ID, $summary->BoardId );
		update_post_meta( $board_id, $this->META_SYNC_STATUS, $summary->BoardState );

		if ( property_exists( $summary, 'CreatedDateTimeUTC' ) )
			$date = $summary->CreatedDateTimeUTC;
		else
			$date = $summary->CreatedDateTimeAsString;

		update_post_meta( $board_id, $this->META_SYNC_CREATED, $date );

		if ( property_exists( $summary, 'ReviewComments' ) )
			update_post_meta( $board_id, $this->META_SYNC_ERROR, new WP_Error( 999, $summary->ReviewComments ) );

		if ( property_exists( $summary, 'LastUpdatedDateTimeUTC' ) )
			$date = $summary->LastUpdatedDateTimeUTC;
		else
			$date = $summary->LastUpdatedDateTimeAsString;

		// Check that local last updated date precedes bing last updated before making any additional data updates.
		$board = get_post( $board_id );
		if ( $board->post_modified_gmt > $date )
			return false;

		update_post_meta( $board_id, $this->META_SYNC_MODIFIED, $date );

		if ( property_exists( $summary, 'Title' ) ) {

			// Update title without resubmitting to bing
			$da = $this->_container->data_architecture();
			remove_action( 'publish_' . $da->BOARD_TYPE,   array( $this, 'maybe_send_board' ), 10 );
			wp_update_post( array(
				'ID' => $board_id,
				'post_title' => $summary->Title
			) );

		}

		if ( property_exists( $summary, 'SearchTerms' ) )
			$this->_container->data_wrangler()->save_search_terms( $board_id, $summary->SearchTerms );

		$panels = $this->_container->data_wrangler()->get_all_panels_for_board( $board_id );

		foreach ( $panels as $key => $panel ) {

			if ( property_exists( $summary, 'PanelSummaries' ) )
				$panel_summary = $summary->PanelSummaries;
			else
				$panel_summary = $summary->PanelList;

			if ( ! isset( $panel_summary[$key] ) )
				continue;

			$panel_summary = $panel_summary[$key];

			if ( property_exists( $panel_summary, 'Id' ) )
				update_post_meta( $panel->ID, $this->META_SYNC_ID, $panel_summary->Id );

			if ( property_exists( $panel_summary, 'Title' ) )
				$panel->post_title = $panel_summary->Title;

			if ( property_exists( $panel_summary, 'Description' ) )
				$panel->post_excerpt = $panel_summary->Description;

			wp_update_post( array(
				'ID' => $panel->ID,
				'post_title' => $panel->post_title,
				'post_excerpt' => $panel->post_excerpt
			) );

			if ( property_exists( $panel_summary, 'Link' ) )
				update_post_meta( $panel->ID, '_bing_panel_link_url', $panel_summary->Link );

			if ( property_exists( $panel_summary, 'LinkText' ) )
				update_post_meta( $panel->ID, '_bing_panel_link_anchor', $panel_summary->LinkText );

			update_post_meta( $panel->ID, $this->META_SYNC_CREATED, $panel_summary->CreatedDateTimeUTC );
			update_post_meta( $panel->ID, $this->META_SYNC_MODIFIED, $panel_summary->LastUpdatedDateTimeUTC );
		}

		return true;

	}

	/**
	 * Grabs a local board for a give bing board id
	 * @param $bing_id
	 *
	 * @return mixed|null
	 */
	public function get_board_from_bing_id( $bing_id ) {
		$da = $this->_container->data_architecture();

		$args = array(
			'post_type'  => $da->BOARD_TYPE,
			'meta_key'   => $this->META_SYNC_ID,
			'meta_value' => $bing_id
		);

		$boards = get_posts( $args );

		if ( empty( $boards ) )
			return null;

		return array_shift( $boards );
	}

	/**
	 * Checks if the last sync had any error and sets a static variable so we can show it later
	 * @param $screen
	 *
	 * @return mixed
	 */
	public function check_for_errors( $screen ) {

		$data_architecture = $this->_container->data_architecture();

		/* Bail unless this is an actual valid Board */
		if ( $screen->id != $data_architecture->BOARD_TYPE )
			return $screen;

		if ( empty( $_GET['post'] ) )
			return $screen;

		$last_result = get_post_meta( absint( $_GET['post'] ), $this->META_SYNC_ERROR, true );

		if ( is_wp_error( $last_result ) ){
			if ( $last_result->get_error_code() === 999 )
				$this->last_error = $last_result->get_error_message();
			else
				$this->last_error = __( "You broke it. Just kidding. We’re not sure why things aren’t working right now, but check back in a little while.", "bing-boards" );
		}

		return $screen;
	}

	/**
	 * Shows any error we had in the last sync
	 */
	public function admin_notices() {

		if ( empty( $this->last_error ) )
			return;

		printf( '<div id="notice" class="error"><p>%s</p></div>', esc_html( $this->last_error ) );

	}

	/**
	 * Changes the wording of WordPress' post updated messages
	 * @param $messages
	 *
	 * @return mixed
	 */
	public function post_updated_messages( $messages ) {

		$da = $this->_container->data_architecture();

		if ( empty( $this->last_error ) ) {
			$messages[$da->BOARD_TYPE][6]  = __( 'Board successfully sent to Bing.', 'bing-boards' );
			$messages[$da->BOARD_TYPE][10] = __( 'Board draft updated.', 'bing-boards' );
		} else {
			$messages[$da->BOARD_TYPE][6] = null;
		}

		return $messages;
	}


	/**
	 * Checks if a user key is valid on the Bing side
	 * and if so grabs the author information.
	 *
	 */
	function get_author_data( $user_id, $key ) {
		$data     = array();
		$api_data = null;

		if ( ! empty( $key ) )
			$api_data = $this->_container->api( $key )->get_author_data();

		if ( ! empty( $api_data ) )
			$data = get_object_vars( $api_data );

		$this->_container->data_wrangler()->save_user_data( $user_id, $data );

		if ( ! empty( $data ) && ! wp_next_scheduled( self::$CRON_NAME ) )
			wp_schedule_event( time(), 'hourly', self::$CRON_NAME );

	}


	/**
	 * Builds a Bing_Board object for a give Board $post_id
	 *
	 * @param $post_id
	 *
	 * @return Bing_Board|null
	 */
	protected function build_board_object( $post_id ) {
		$board_post = get_post( $post_id );

		if ( empty( $board_post ) )
			return null;

		$dw = $this->_container->data_wrangler();

		$board = new Bing_Board();

		$board->setBoardId( get_post_meta( $post_id, $this->META_SYNC_ID, true ) );
		$board->setSearchTerms( $dw->get_search_terms( $post_id ) );
		$board->setTitle( html_entity_decode($board_post->post_title) );
		$board->setPanelList( $this->build_panel_list_for_board( $post_id ) );

		return $board;
	}

	/**
	 * Builds an array of Bing_Panel for a given board
	 *
	 * @param $post_id
	 *
	 * @return null|array of Bing_Panel
	 */
	protected function build_panel_list_for_board( $post_id ) {
		$da = $this->_container->data_architecture();
		$dw = $this->_container->data_wrangler();

		$panels = $dw->get_all_panels_for_board( $post_id );

		if ( empty( $panels ) )
			return null;

		$panel_objects = array();

		foreach ( $panels as $panel ) {
			$panel_object = new Bing_Panel();

			$panel_object->setTitle( html_entity_decode( $panel->post_title ) );
			$panel_object->setDescription( html_entity_decode( $panel->post_excerpt ) );
			$panel_object->setLink( html_entity_decode( get_post_meta( $panel->ID, $da->LINK_URL_META, true ) ) );
			$panel_object->setLinkText( html_entity_decode( get_post_meta( $panel->ID, $da->LINK_ANCHOR_META, true ) ) );
			$panel_object->setPanelId( get_post_meta( $panel->ID, $this->META_SYNC_ID, true ) );
			$panel_object->setMedia( $this->build_media_for_panel( $panel->ID ) );

			$panel_objects[] = $panel_object->get_object_vars();
		}

		return $panel_objects;

	}

	/**
	 * Builds an array of Bing_Media for a given panel
	 * @param $panel_id
	 *
	 * @return array of Bing_Media|null
	 */
	protected function build_media_for_panel( $panel_id ) {

		$media = $this->_container->data_wrangler()->get_panel_media( $panel_id, 'large' );
		if ( empty( $media['thumbnail'] ) )
			return null;

		$media_object = new Bing_Media();

		$media_object->setUrl( $media['thumbnail'] );

		return $media_object->get_object_vars();
	}


	/**
	 * Verifies if the board is in a statii that could generate sync issues if edited
	 * @param $board_id
	 *
	 * @return bool
	 */
	public function is_editable( $board_id ) {
		$api    = $this->_container->api();
		$status = get_post_meta( $board_id, $this->META_SYNC_STATUS, true );

		if ( $status === "" )
			return true;

		$status = absint( $status );

		if (
			$status === $api->STATUS_PENDING_REVIEW ||
			$status === $api->STATUS_PUBLISHED
		)
			return false;

		return true;

	}

	/**
	 * Returns the name of the status of a given board
	 * @param $board_id
	 *
	 * @return string|void
	 */
	public function get_status_name( $board_id ) {
		$status = get_post_meta( $board_id, $this->META_SYNC_STATUS, true );
		$api    = $this->_container->api();

		if ( $status === false || $status === "" )
			return __( "Draft" );

		$status = absint( $status );

		switch ( $status ) {
			case $api->STATUS_PENDING_REVIEW:
				return __( "Pending Review", "bing-boards" );
				break;
			case $api->STATUS_ACTION_REQUIRED:
				return __( "Action Required", "bing-boards" );
				break;
			case $api->STATUS_PUBLISHED:
				return __( "Published", "bing-boards" );
				break;
			case $api->STATUS_DELETED:
				return __( "Draft" );
				break;

		}

		return '';
	}
}
