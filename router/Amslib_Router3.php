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
 * File: Amslib_Router3.php
 * Title: Version 3.0 of the Core router object
 * Version: 3.2
 * Project: Amslib/Router
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router3
{
	static protected $source;
	static protected $location;
	static protected $path;
	static protected $route;

	/**
	 * method: sanitise
	 *
	 * If the route was invalid, set a false "fake" array of data
	 * to stop you from having to do all kinds of crazy test by default
	 * in other methods
	 */
	static protected function sanitise($route)
	{
		if(!$route){
			//	This just makes sure the information returned has the valid indexes, I'm not sure why I do this here
			//	and not inside the source object......maybe this method is a mistake...
			$route["name"]			=	false;
			$route["resource"]		=	false;
			$route["route"]			=	false;
			$route["parameters"]	=	array();
			$route["options"]		=	array();
		}

		return $route;
	}

	public function __construct()
	{
		//	IMPORTANT: This method might be full of information which doesnt need to be here!!!
		//	TODO: Analyse who uses the information set here to determine whether it's safe to "move" this somewhere else.

		self::$location	=	Amslib_Website::set();

		//	Setup the router path in order to obtain the correct path information
		$router_path	=	Amslib::getParam("router_path");
		self::$path		=	NULL;

		if($router_path){
			//	This is so ugly but I can't think of another way to do it.
			//	First strip all the // -> /, then strip self::$location, then strip // -> from /self::$location/
			//	We need to do this because we need to handle silly situations where a forward slash is missing
			//	or a double slash is encountered because of the user configuration being bad, or bad input data, etc
			self::$path = str_replace("//","/",$router_path);
			self::$path = str_replace(self::$location,"",self::$path);
			self::$path = str_replace("//","/","/".self::$path."/");
		}
	}

	static public function setSource($source)
	{
		self::$source = $source;
	}

	/**
	 * method:	getObject
	 *
	 * It's hard to know when a router source object is compatible or not
	 * this method eliminates that doubt by putting the onus on this object
	 * to create the appropriate object for you, depending on the type you
	 * requested.
	 */
	static public function getObject($type,$file=NULL)
	{
		switch($type){
			case "source:xml":{
				return Amslib_Router_Source_XML2::getInstance();
			}break;

			case "source:database":{
				return Amslib_Router_Source_Database::getInstance();
			}break;

			case "language":{
				return Amslib_Router_Language3::getInstance();
			}
		}

		return false;
	}

	static public function execute()
	{
		if(self::$source && self::$path){
			self::$path		=	Amslib_Router_Language3::extract(self::$path);
			self::$route	=	self::getRouteByURL(self::$path);
		}
	}

	/**
	 * method: getURL
	 *
	 * Return a url based on the name,version,lang being requested
	 *
	 * parameters:
	 * 	$name		-	The name of the route, defined in each route declaration
	 * 	$version	-	The version of the route (not 100% sure what this is for anymore, old code?)
	 * 	$lang		-	The language of the route to return
	 */
	static public function getURL($name=NULL,$version=NULL,$lang=NULL)
	{
		$route = self::getRoute($name,$version,$lang);

		//	TODO: Support passing the $lang parameter and have it override the language setup
		$lang = Amslib_Router_Language3::getName();
		if($lang) $lang = "/$lang/";

		return Amslib_Website::rel($lang.$route["route"]);
	}

	static public function getRoute($name=NULL,$version=NULL,$lang=NULL)
	{
		if($name == NULL) return self::$route;

		if($lang == NULL) $lang = Amslib_Router_Language3::getCode();

		return self::sanitise(self::$source->getRoute($name,$version,$lang));
	}

	static public function getRouteByURL($url=NULL)
	{
		if($url == NULL) return self::$route;

		return self::sanitise(self::$source->getRouteByURL($url));
	}

	static public function getName()
	{
		return self::$route["name"];
	}

	static public function isRouted()
	{
		return self::path !== NULL ? true : false;
	}

	static public function getResource($name=NULL)
	{
		$r = ($name == NULL) ? self::$route : self::getRoute($name);

		return $r["resource"];
	}

	//	DEPRECATED USE getParameter instead (so I can return one parameter, or all of them at once)
	static public function getParameters(){ return self::getParameter(); }

	static public function getParameter($name=NULL)
	{
		return $name && isset(self::$route["parameters"][$name])
			? self::$route["parameters"][$name]
			: self::$route["parameters"];
	}

	static public function hasParameter($name)
	{
		return in_array($name,self::$route["parameters"]) ? true : false;
	}

	static public function getURLOptions()
	{
		return self::$route["options"];
	}

	/**
	 * method: changeLang
	 *
	 * Create a url for the current route that will change the language into the other one
	 * this is completely application specific in how this happens, per application, requires perhaps
	 * a different way to change languages, as long as the url scheme for languages remains the same
	 */
	static public function changeLang($langName)
	{
		Amslib_Router_Language3::push();
		Amslib_Router_Language3::set($langName);

		$u = self::getURL();

		Amslib_Router_Language3::pop();

		return $u;
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}
