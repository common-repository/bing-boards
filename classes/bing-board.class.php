<?php

class Bing_Board {

	/**
	 * @var int
	 */
	private $BoardId = 0;
	/**
	 * @readonly
	 * @var string
	 */
	private $CreatedDateTimeAsString = null;
	/**
	 * @readonly
	 * @var string
	 */
	private $LastUpdatedDateTimeAsString = null;
	/**
	 * @var array of Bing_Panel
	 */
	private $PanelList = array();
	/**
	 * @var array of string
	 */
	private $SearchTerms = array();
	/**
	 * @var string
	 */
	private $Title = "";


	/**
	 * Returns an array with the properties of this class.
	 * @return array
	 */
	public function get_object_vars() {
		return get_object_vars( $this );
	}

	/**
	 * Setter for the board id
	 * @param int $boardId
	 */
	public function setBoardId( $boardId ) {
		$this->BoardId = $boardId;
	}

	/**
	 * Getter for the board id
	 * @return int
	 */
	public function getBoardId() {
		return $this->BoardId;
	}

	/**
	 * Getter for the created timestamp
	 * @return string
	 */
	public function getCreatedDateTimeAsString() {
		return $this->CreatedDateTimeAsString;
	}

	/**
	 * Getter for the updated timestamp
	 * @return string
	 */
	public function getLastUpdatedDateTimeAsString() {
		return $this->LastUpdatedDateTimeAsString;
	}

	/**
	 * Setter for the list of panels
	 * @param array $panelList
	 */
	public function setPanelList( $panelList ) {
		$this->PanelList = $panelList;
	}

	/**
	 * Getter for the list of panels
	 * @return array
	 */
	public function getPanelList() {
		return $this->PanelList;
	}

	/**
	 * Setter for the search terms array
	 * @param array $searchTerms
	 */
	public function setSearchTerms( $searchTerms ) {
		$this->SearchTerms = $searchTerms;
	}

	/**
	 * Getter for the search terms array
	 * @return array of strings
	 */
	public function getSearchTerms() {
		return $this->SearchTerms;
	}

	/**
	 * Setter for the title. Will set as Untitled if empty.
	 * @param string $title
	 */
	public function setTitle( $title ) {
		if ( ! empty( $title ) )
			$this->Title = $title;
		else
			$this->Title =  __( 'Untitled', 'bing-boards' );
	}

	/**
	 * Getter for the title
	 * @return string
	 */
	public function getTitle() {
		return $this->Title;
	}



}
