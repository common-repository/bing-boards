<?php
/*-------------------------------------------------------------------------------------*
 * Bing Boards master class
 *
 * @author Modern Tribe Inc. (http://tri.be/)
 *-------------------------------------------------------------------------------------*/

class Bing_Boards {

	/**
	 *  Current version of the plugin
	 */
	const VERSION = '1.0';

	/**
	 * Singleton instance of this class.
	 * @var Bing_Boards
	 */
	private static $_instance;

	/**
	 * Container of classes for dependency injection
	 * See container() for the getter/setter
	 * @var Bing_Boards_Container
	 */
	private $_container;


	/**
	 *  Class constructor
	 */
	public function __construct( Bing_Boards_Container $container ) {

		$this->_container = $container;

		// Initialize our modules
		$this->container()->data_architecture();
		$this->container()->data_wrangler();
		$this->container()->api_integration();

		if ( is_admin() ) {
			$this->container()->admin();
			$this->container()->ajax();
		}

	}

	/**
	 * Setter and getter for the $_container private property
	 * If $set is null, it works as a getter. If you pass a
	 * Bing_Boards_Container child, it works as a setter.
	 *
	 * @param Bing_Boards_Container|null $set
	 *
	 * @return Bing_Boards_Container
	 */
	public function container( Bing_Boards_Container $set = null ) {
		if ( ! empty( $set ) )
			$this->_container = $set;

		return $this->_container;
	}

	/**
	 * Returns the singleton instance for this class.
	 * If $container is null when you first create the instance
	 * it will use Bing_Boards_Container_Default.
	 *
	 * In consecutive calls after the instance is created, if $container
	 * is not null, it'll replace the container. If $container is null, it
	 * will use the one created on the first call.
	 *
	 * @param Bing_Boards_Container $container
	 *
	 * @filter bing_boards_default_container for the default dependency injector
	 *
	 * @return Bing_Boards
	 */
	public static function instance( Bing_Boards_Container $container = null ) {
		if ( ! isset( self::$_instance ) ) {

			// Default container. Only to be executed when first instantiating the object.
			if ( empty( $container ) ) {
				$default_container = apply_filters( 'bing_boards_default_container', 'Bing_Boards_Container_Default' );
				$container         = new $default_container();
			}

			$className       = __CLASS__;
			self::$_instance = new $className( $container );
		} else if ( ! empty( $container ) && is_a( $container, 'Bing_Boards_Container' ) ) { //Set a new container, if passed by the user.
			self::$_instance->container( $container );
		}

		return self::$_instance;
	}

}
