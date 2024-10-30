<?PHP
/*-------------------------------------------------------------------------------------*
 * Bing Boards API integration.
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

/**
 * Class Bing_Boards_API
 */
class Bing_Boards_API {

	public $PANELS_LIMIT = 25;

	public $SEARCH_TERMS_MAX_ITEMS = 5;
	public $SEARCH_TERM_MAX_LENGTH = 64;
	public $SEARCH_TERM_MIN_LENGTH = 3;

	public $BODY_MAX_LENGTH         = 300;
	public $PANEL_TITLE_MAX_LENGTH  = 35;
	public $BOARD_TITLE_MAX_LENGTH  = 35;
	public $LINK_TITLE_MAX_LENGTH   = 35;

	public $STATUS_PENDING_REVIEW  = 0;
	public $STATUS_ACTION_REQUIRED = 1;
	public $STATUS_PUBLISHED       = 2;
	public $STATUS_DELETED         = 4;

	public $API_URL = "https://ssl.bing.com/boards/api/v1/";

	protected $user_key = null;

	/**
	 * Container of classes for dependency injection
	 * @var Bing_Boards_Container
	 */
	private $_container;


	/**
	 * Class constructor
	 * @param Bing_Boards_Container $container
	 * @param string                $user_key
	 */
	public function __construct( Bing_Boards_Container $container, $user_key ) {
		$this->_container = $container;
		$this->user_key = $user_key;
	}


	/**
	 * Creates or updates a  board.
	 *
	 * @param Bing_Board $board
	 *
	 * @return bool|WP_Error
	 */
	public function save_board( Bing_Board $board ) {

		if ( empty( $board ) )
			return new WP_Error( 1, 'Board is empty' );

		$panels = $board->getPanelList();

		if ( empty( $panels ) )
			return new WP_Error( 3, "Can't create a Board without Panels" );

		if ( $board->getBoardId() )
			$response = $this->call( 'board/post', $board, 'POST' );
		else
			$response = $this->call( 'board/put', $board, 'POST' );

		return $response;

	}


	/**
	 * Returns $count boards for the current user starting at 0-based $index
	 * @param int $index
	 * @param int $count
	 *
	 * @return array|WP_Error
	 */
	public function get_my_boards( $index = 0, $count = 10 ) {

		$response = $this->call(
			'boards/get',
			array(
				'StartIndex' => $index,
				'Count'      => $count
			),
			'POST'
		);

		if ( is_wp_error( $response ) || empty( $response->Boards ) || count( $response->Boards ) < $count )
			return $response;

		$api_count = count( $response->Boards );

		// Recursive call when we get more than 10 reults, given Bing API restrictions
		while ( $api_count == $count ) {
			$response2 = $this->get_my_boards( $index + $count, $count );
			if ( is_wp_error( $response2 ) || empty( $response2->Boards ) )
				return $response;

			$response->Boards = array_merge( $response->Boards, $response2->Boards );
			$api_count        = count( $response2->Boards );
		}

		return $response;
	}

	/**
	 * Delets a board
	 * @param string $BoardId
	 *
	 * @return array|WP_Error
	 */
	public function delete_board( $BoardId ) {

		if ( empty( $BoardId ) || ! is_string( $BoardId ) )
			return new WP_Error( 8, __( 'Need to pass a Bing BoardId', 'bing-boards' ) );

		$response = $this->call(
			'board/delete',
			array(
				'BoardId' => $BoardId,
			),
			'POST'
		);

		return $response;

	}

	/**
	 * Gets the author data (Name, slogan, picture)
	 * @return array|WP_Error
	 */
	public function get_author_data() {
		$response = $this->call(
			'author/get',
			null,
			'POST'
		);

		return $response;
	}


	/**
	 * Generic function to make all the different API calls
	 *
	 * @param string                                 $endpoint
	 * @param array|Bing_Board|Bing_Panel|Bing_Media $object
	 * @param "POST" or "GET"                        $method
	 *
	 * @return array|WP_Error
	 */
	protected function call( $endpoint, $object, $method ) {

		if ( empty( $this->user_key ) )
			return new WP_Error( 99, 'Need a User Key' );

		$url = $this->API_URL . $endpoint;

		$func = null;
		switch ( $method ) {
			case 'POST':
				$func = 'wp_remote_post';
				break;
			case 'GET':
				$func = 'wp_remote_get';
				break;
			default:
				return new WP_Error( 4, 'Unsupported Method' );
		}

		$object = $this->envelope( $object );

		$args = array(
            'body' => json_encode( $object ),
            'timeout' => 10000
		);

		$result = call_user_func_array( $func, array( $url, $args ) );

		if ( is_wp_error( $result ) || empty( $result ) )
			return $result;

		if ( !empty( $result['response']['code'] ) && $result['response']['code'] != 200 )
			return new WP_Error( $result['response']['code'], $result['response']['message'] );

		$result = json_decode( wp_remote_retrieve_body( $result ) );

		if ( property_exists( $result, 'ErrorMessage' ) )
			return new WP_Error( 13, $result->ErrorMessage );

		return $result;
	}

	/**
	 * Wraps the API messages in an evelope with the UsedID
	 * @param Bing_Board|Bing_Media|Bing_Panel $data
	 *
	 * @return array
	 */
	protected function envelope( $data = null ) {

		$envelope = array(
			'UserId'           => $this->user_key,
			'ClientTrackingId' => time(),
		);

		if ( ! empty( $data ) && is_object( $data ) ) {
			$type            = str_replace( 'Bing_', '', get_class( $data ) );
			$envelope[$type] = $data->get_object_vars();
		}

		if ( ! empty( $data ) && is_array( $data ) ) {
			$envelope = array_merge( $envelope, $data );
		}

		return $envelope;

	}

}
