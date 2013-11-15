<?php
class Amslib_Plugin_Object extends Amslib_Mixin
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

	/**
	 * 	method:	addMixin
	 *
	 * 	This method allows you to use the getObject call to acquire an object by name instead of passing
	 * 	it by object, allowing the system to find and return the object for you instead of manually having
	 * 	to create it first.
	 *
	 * 	todo: write documentation
	 *
	 * 	NOTE:	I have this method installed in Amslib_MVC and Amslib_Plugin_Object, I'm not sure whether
	 * 			I want to have it installed in both places, but I'm not allowed to inherit from multiple
	 * 			classes, so I have to find another code organisation instead.
	 */
	public function addMixin($name,$reject=array(),$accept=array())
	{
		if(is_string($name)){
			$object = $this->api->getObject($name,true);

			if(!$object) $object = $this->api->getAPI($name);
		}elseif(is_object($name)){
			$object = $name;
		}else{
			$object = false;
		}

		return parent::addMixin($object,$reject,$accept);
	}
}