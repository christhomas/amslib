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
	 * 	TODO:	write documentation
	 *
	 *	FIXME:	this is overly simplistic, but quite realiable.
	 *
	 *	NOTE:	still, what constitutes "initialised", just having a handle to
	 *			the API object? thats it? or there are other factors which are not demonstrated here?
	 */
	public function isInitialised()
	{
		return $this->api ? true : false;
	}

	public function initialiseObject($api)
	{
		$this->api = $api;
	}

	//	I am really not very happy about all this "method-shadowing" that I need to do here
	//	It feels like I am covering up some design/layout problem
	public function getAPI($name)
	{
		return $this->api->getAPI($name);
	}

	public function getValue($name=NULL,$default=NULL)
	{
		return $this->api->getValue($name,$default);
	}

	public function getObject($id,$singleton=true)
	{
		return $this->api->getObject($id,$singleton);
	}

	public function renderView($id,$params=array())
	{
		return $this->api->renderView($id,$params);
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
			$object = $this->getObject($name,true);

			if(!$object) $object = $this->getAPI($name);
		}elseif(is_object($name)){
			$object = $name;
		}else{
			$object = false;
		}

		return parent::addMixin($object,$reject,$accept);
	}
}