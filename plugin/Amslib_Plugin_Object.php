<?php
class Amslib_Plugin_Object
{
	protected $api;

	public function __construct()
	{
		$this->api = false;
	}

	/**
	 * 	method:	isInitialised
	 *
	 * 	todo: write documentation
	 */
	public function isInitialised()
	{
		//	FIXME: this is overly simplistic, but quite realiable.
		return $this->api ? true : false;
	}

	public function initialiseObject($api)
	{
		$this->api = $api;
	}
}