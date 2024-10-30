<?php

class Bing_Media {
	/**
	 * @var int
	 */
	private $Type = 0;
	/**
	 * @var string
	 */
	private $Url = null;
	/**
	 * @var int
	 */
	private $Height = 0;
	/**
	 * Typo is known, but needs to match the Bing API.
	 * @var int
	 */
	private $Widht = 0;

	public function get_object_vars() {
		return get_object_vars( $this );
	}

	/**
	 * @param int $height
	 */
	public function setHeight( $height ) {
		$this->Height = $height;
	}

	/**
	 * @return int
	 */
	public function getHeight() {
		return $this->Height;
	}

	/**
	 * @param int $type
	 */
	public function setType( $type ) {
		$this->Type = $type;
	}

	/**
	 * @return int
	 */
	public function getType() {
		return $this->Type;
	}

	/**
	 * @param string $url
	 */
	public function setUrl( $url ) {
		$this->Url = $url;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->Url;
	}

	/**
	 * @param int $width
	 */
	public function setWidth( $width ) {
		$this->Widht = $width;
	}

	/**
	 * @return int
	 */
	public function getWidth() {
		return $this->Widht;
	}

}