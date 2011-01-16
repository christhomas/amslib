<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * File: Amslib_Router2.php
 * Title: Version 2.0 of the Core router object
 * Version: 2.0
 * Project: Amslib/Router
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router2
{
	protected $activeRoute;
	protected $paramsRoute;
	protected $routerPath;
	protected $source;
	protected $webdir;

	protected function relativePath($path)
	{
		return Amslib_Filesystem::relative($path);
	}

	public function __construct()
	{
		$this->webdir = "";
		
		Amslib_Router_URL::setRouter($this);
	}

	public function load($source,$webdir=NULL)
	{
		if(!$webdir) $webdir = Amslib_Filesystem::documentRoot();
		
		$this->source = $source;
		$this->webdir = $webdir;
	}
	
	/**
	 * method: loadXML
	 * 
	 * Convenience method for loading a XML router configuration
	 * 
	 * parameters:
	 * 	$source	-	The filename of the XML router configuration to load
	 * 
	 * notes:
	 * 	-	This might be a bad idea, but it seems a nice way to simplify the client code
	 */
	public function loadXML($source)
	{
		$source = Amslib_Router_Source_XML::getInstance($source);
		
		$this->load($source);
	}

	public function execute()
	{
		$this->routerPath = Amslib::getParam("router_path");

		if($this->routerPath !== NULL){
			$this->routerPath = $this->relativePath($this->routerPath);

			Amslib_Router_Language2::setRouter($this);
			Amslib_Router_Language2::detect($this->routerPath);

			//	FIXME:	What happens if someone sets up a system
			//			which uses a php file as a router path?
			//			this might not work anymore

			//	Append a / to the string and then replace any // with / (removing any duplicates basically)
			$this->routerPath	=	str_replace("//","/",$this->routerPath."/");
			$this->activeRoute	=	$this->source->getRouteData($this->routerPath);
			$this->paramsRoute	=	($this->activeRoute) ? $this->activeRoute["params"] : array();
		}
	}

	//	TODO: This is a bullshit function I copied from the old router, probably doesnt mean anything now
	//	FIXME: wtf? something? why have I left this parameter named 'something' ???
	public function getResource($something=NULL)
	{
		if($something == NULL) return $this->activeRoute["resource"];
		//	Have to figure out what this something will do
		return false;
	}

	public function getRoute($name=NULL,$version="default")
	{
		//	Return the current active route
		if($name == NULL){
			$name		=	$this->activeRoute["name"];
			$version	=	$this->activeRoute["version"];
		}
		
		//	This is in response to a query for a url
		//	based on the name of the route, version and language requested
		$lang = Amslib_Router_Language2::getCode();
		
		return Amslib_Router_Language2::get(true).$this->source->getRoute($name,$version,$lang);
	}
	
	public function getName()
	{
		return isset($this->activeRoute["name"]) ? $this->activeRoute["name"] : false;
	}

	public function isRouted()
	{
		return ($this->routerPath !== NULL) ? true : false;
	}

	public function getParameters()
	{
		return $this->paramsRoute;
	}
	
	public function hasParameter($name)
	{
		return in_array($name,$this->paramsRoute) ? true : false;
	}

	public function getCurrentRoute()
	{
		//	TODO: Hmmmm, I dont like this, I want to use it for something else
		//	TODO: Is this used for anything?
		return $this->activeRoute;
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Router2();

		return $instance;
	}
}
