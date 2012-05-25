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
 * File: Amslib_Router.php
 * Title: Version 4.0 of the Core router object
 * Version: 4.0
 * Project: Amslib/Router
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router
{
	static protected $emptyRoute = array(
		"name"			=>	false,
		"resource"		=>	false,
		"src"			=>	array(),
		"route"			=>	false,
		"route_param"	=>	array(),
		"url_param"		=>	array(),
		"stylesheet"	=>	array(),
		"javascript"	=>	array()
	);

	static protected $base	=	false;
	static protected $path	=	false;
	static protected $route	=	false;
	static protected $cache	=	array();
	static protected $name	=	array();
	static protected $url	=	array();

	static public function initialise()
	{
		//	Setup the router path in order to obtain the correct path information
		self::$base	=	$_SERVER["AMSLIB_WEBSITE"];
		self::$path	=	Amslib::lchop($_SERVER["REQUEST_URI"],self::$base);
		self::$path	=	Amslib_File::reduceSlashes("/".self::$path."/");;
	}

	static public function finalise()
	{
		if(!self::$path) return false;

		self::$route = self::getRouteByURL(self::$path);
	}

	static public function getPath()
	{
		return self::$path;
	}

	static public function getBase()
	{
		return self::$base;
	}

	static public function load($source,$type,$group)
	{
		try{
			switch($type){
				case "xml":{		$s = new Amslib_Router_Source_XML($source);			}break;
				case "database":{	$s = new Amslib_Router_Source_Database($source);	}break;
			}

			foreach(Amslib_Array::valid($s->getRoutes()) as $route){
				self::setRoute($route["name"],$group,$route);
			}
		}catch(Exception $e){
			return false;
		}

		return true;
	}

	static public function setLanguage($lang)
	{
		//	TODO: write how this would work
	}

	static public function changeLanguage($lang,$fullRoute=NULL,$key=NULL)
	{
		//	NOTE: what does fullRoute mean??
		//	NOTE: what does key mean??
		//	NOTE: what if we are processing languages like /es_ES/route ?

		return self::getURL(NULL,NULL,$lang);
	}

	static public function getURL($name=NULL,$group=NULL,$lang="default")
	{
		$route = self::getRoute($name,$group);

		return Amslib_Website::rel(isset($route["src"][$lang]) ? $route["src"][$lang] : "");
	}

	static public function getRouteByURL($url=NULL)
	{
		if($url == NULL) return self::$route;

		$result = "";
		foreach(self::$url as $u=>$d){
			if($url != "/" && strpos($url,$u) !== false && strlen($u) >= strlen($result)){
				$result = $u;
			}else if($url == $u){
				$result = $u;
			}
		}

		if(strlen($result) && isset(self::$url[$result])){
			$r = self::$url[$result];

			//	Don't replace anything if the string is / because it'll nuke all the separators
			$replace	=	$result == "/" ? "" : $result;
			$result		=	str_replace($replace,"",$url);

			$r["url_param"] = Amslib_Array::valid(explode("/",trim($result,"/ ")));

			return $r;
		}

		return self::$emptyRoute;
	}

	static public function getRoute($name=NULL,$group=NULL)
	{
		//	if there was no name, surely you mean return the current route
		if($name == NULL) return self::$route;

		//	if you specify a group, look in the name array specifically for that group
		if($group && is_string($group) && isset(self::$cache[$group]) && isset(self::$cache[$group][$name])){
			return self::$cache[$group][$name];
		}

		//	if the group wasn't requested or didn't exist, or failed to find the route by name,
		//	look in the mixed/global cache for the last registered route with that name instead
		if($name && is_string($name) && isset(self::$name[$name])) return self::$name[$name];

		//	default to an empty route, to make sure the data returned is the right format, just empty
		return self::$emptyRoute;
	}

	static public function getServiceURL($name,$group=NULL,$lang="default")
	{
		return self::getURL("service:$name",$group,$lang);
	}

	static public function getService($name,$group=NULL)
	{
		return self::getRoute("service:$name",$group);
	}

	static public function setRoute($name,$group,$route,$updateURLCache=true)
	{
		//	test if the group is valid or not
		if(!$group || !is_string($group)) return false;
		//	test if the name is valid or not
		if(!$name || !is_string($name)) return false;
		//	Obviously if the data is not valid, you can't process it anyway
		if(!$route || !is_array($route)) return false;
		//	test if the src value is valid
		if(!isset($route["src"]) || !is_array($route["src"]) || count($route["src"]) == 0) return false;

		//	Automatically prepend service route names with "service:" so you can easily distinguish them
		//	This also enabled the getServiceURL,getService methods on the Amslib_Router object
		if($route["type"] == "service") $name = "service:".$name;

		//	NOTE: we "reset" the values here because if you are
		//	importing/renaming a service, this will do it for free without complexity
		$route["name"]	=	$name;
		$route["group"]	=	$group;

		//	store the route data underneath the name so you can explicitly search for it
		self::$cache[$group][$name]	=	&$route;
		self::$name[$name]			=	&$route;

		//	store the route data referencing it by url, so you can build a request url cache
		if($updateURLCache) foreach($route["src"] as $s){
			self::$url[$s] = &$route;
		}
	}

	static public function getName()
	{
		return self::$route["name"];
	}

	static public function isRouted()
	{
		return self::$path !== NULL ? true : false;
	}

	static public function isService()
	{
		return self::$route && isset(self::$route["type"]) && self::$route["type"] == "service"
			? true
			: false;
	}

	static public function getResource($name=NULL)
	{
		$r = ($name == NULL) ? self::$route : self::getRoute($name);

		return $r && isset($r["resource"]) ? $r["resource"] : false;
	}

	static public function getRouteParam($name=NULL,$default="")
	{
		if(isset(self::$route["route_param"])){
			if($default === "") $default = self::$route["route_param"];

			return $name !== NULL && isset(self::$route["route_param"][$name])
				? self::$route["route_param"][$name]
				: $default;
		}

		return $default;
	}

	static public function setRouteParam($name,$value)
	{
		if(self::$route && isset(self::$route["route_param"])){
			self::$route["route_param"][$name] = $value;
		}
	}

	static public function hasRouteParam($name)
	{
		return in_array($name,self::$route["route_param"]) ? true : false;
	}

	static public function getURLParam($index=NULL,$default="")
	{
		//	if there are no url options, return the default
		if(!isset(self::$route["url_param"])) return $default;
		//	if the default was an empty string, set the default to the entire options array
		if($default === "") $default = self::$route["url_param"];

		return $index !== NULL && isset(self::$route["url_param"][$index])
				? self::$route["url_param"][$index]
				: $default;
	}

	static public function getStylesheet()
	{
		return isset(self::$route["stylesheet"]) ? self::$route["stylesheet"] : array();
	}

	static public function getJavascript()
	{
		return isset(self::$route["javascript"]) ? self::$route["javascript"] : array();
	}

	static public function dump()
	{
		return array(
			"path"		=>	self::$path,
			"current"	=>	self::$route,
			"cache"		=>	self::$cache,
			"url"		=>	self::$url,
			"name"		=>	self::$name
		);
	}
}
