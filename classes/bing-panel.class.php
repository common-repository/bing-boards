<?php

class Bing_Panel {

	/**
	 * @var int
	 */
	private $PanelId = 0;
	/**
	 * @var string
	 */
	private $Title = null;
	/**
	 * @var string
	 */
	private $Description = null;
	/**
	 * @var string
	 */
	private $Link = null;
	/**
	 * @var string
	 */
	private $LinkText = null;
	/**
	 * @var null
	 */
	private $Media = null;
	/**
	 * @var bool
	 */
	private $Original = true;
	/**
	 * @var string
	 */
	private $SourceUrl = null;
	/**
	 * @var string
	 */
	private $SourceText = null;
	/**
	 * @readonly
	 * @var string
	 */
	private $CreateDateTimeUTC = null;
	/**
	 * @readonly
	 * @var string
	 */
	private $LastUpdatedDateTimeUTC = null;

	public function get_object_vars() {
		return get_object_vars( $this );
	}


	/**
	 * @return string
	 */
	public function getCreateDateTimeUTC() {
		return $this->CreateDateTimeUTC;
	}

	/**
	 * @param string $description
	 */
	public function setDescription( $description ) {
		$this->Description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->Description;
	}

	/**
	 * @return string
	 */
	public function getLastUpdatedDateTimeUTC() {
		return $this->LastUpdatedDateTimeUTC;
	}

	/**
	 * @param string $link
	 */
	public function setLink( $link ) {
		$this->Link = $link;
	}

	/**
	 * @return string
	 */
	public function getLink() {
		return $this->Link;
	}

	/**
	 * @param string $linkText
	 */
	public function setLinkText( $linkText ) {
		$this->LinkText = $linkText;
	}

	/**
	 * @return string
	 */
	public function getLinkText() {
		return $this->LinkText;
	}

	/**
	 * @param null $media
	 */
	public function setMedia( $media ) {
		$this->Media = $media;
	}

	/**
	 * @return null
	 */
	public function getMedia() {
		return $this->Media;
	}

	/**
	 * @param boolean $original
	 */
	public function setOriginal( $original ) {
		$this->Original = $original;
	}

	/**
	 * @return boolean
	 */
	public function getOriginal() {
		return $this->Original;
	}

	/**
	 * @param int $PanelId
	 */
	public function setPanelId( $PanelId ) {
		$this->PanelId = $PanelId;
	}

	/**
	 * @return int
	 */
	public function getPanelId() {
		return $this->PanelId;
	}

	/**
	 * @param string $sourceText
	 */
	public function setSourceText( $sourceText ) {
		$this->SourceText = $sourceText;
	}

	/**
	 * @return string
	 */
	public function getSourceText() {
		return $this->SourceText;
	}

	/**
	 * @param string $sourceUrl
	 */
	public function setSourceUrl( $sourceUrl ) {
		$this->SourceUrl = $sourceUrl;
	}

	/**
	 * @return string
	 */
	public function getSourceUrl() {
		return $this->SourceUrl;
	}

	/**
	 * @param string $Title
	 */
	public function setTitle( $Title ) {
		if ( ! empty( $Title ) )
			$this->Title = $Title;
		else
			$this->Title =  __( 'Untitled', 'bing-boards' );
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->Title;
	}




}