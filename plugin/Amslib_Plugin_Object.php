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

	//	NOTE: in an ideal world, this method would be handled by the inheritance
	public function initialiseObject($api)
	{
		$this->api = $api;

		$this->model = $this->api->getObject("Mo_Catalog");
	}
}