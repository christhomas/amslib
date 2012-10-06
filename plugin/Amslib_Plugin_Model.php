<?php
//	NOTE:	this object is getting smaller and smaller whilst it's
//			functionality is being absorbed into the parent object

//	NOTE:	perhaps this means I should work to delete this object
//			and redistribute it's code elsewhere.
class Amslib_Plugin_Model extends Amslib_Database_MySQL
{
	protected $api;

	public function __construct()
	{
		parent::__construct(false);

		//	TODO: default to an empty object??
		$this->api = false;
	}

	public function initialiseObject($api)
	{
		$this->api = $api;
		$this->copyConnection($this->api->getModel());
	}
}