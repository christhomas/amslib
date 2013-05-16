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

	static protected $base		=	false;
	static protected $path		=	false;
	static protected $pathList	=	array();
	static protected $route		=	false;
	static protected $cache		=	array();
	static protected $name		=	array();
	static protected $url			=	array();
	static protected $callback	=	array();
	static protected $export		=	array();
	
	/**
	 * Variable: $domain
	 * 
	 * The default domain string to use with routes that do not provide their own
	 */
	static protected $domain		=	"__LOCAL_DOMAIN__";
	
	static protected function finaliseRoute($route,$select,$url)
	{
		//	Don't replace anything if the string is / because it'll nuke all the separators
		$replace	=	$select == "/" ? "" : $select;
		$params		=	Amslib::lchop($url,$replace);

		//	Filter out NULL or empty elements and return ONLY a valid array of valid components
		$route["url_param"] = Amslib_Array::valid(array_filter(explode("/",trim($params,"/ "))));

		//	set the language based on the current route and selected url
		$route["lang"] = array_search($select,$route["src"]);
		$route["src_selected"] = $select;

		return $route;
	}

	static protected function findLongest($list,$url)
	{
		$select = "";

		foreach(self::$url as $u=>$d){
			if($url != "/" && strpos($url,$u) === 0 && strlen($u) >= strlen($select)){
				$select = $u;
			}else if($url == $u){
				$select = $u;
			}
		}

		return $select;
	}

	static public function initialise()
	{
		//	NOTE: what does AMSLIB_ROUTER do now? this isn't explained and not understandable
		foreach($_SERVER as $k=>$v){
			if(strpos($k,"AMSLIB_ROUTER") !== false) self::$pathList[$k] = $v;
		}

		self::$path	=	NULL;
		self::$base	=	self::getPath("__AMSLIB_ROUTER_ACTIVE__");

		if(self::$base){
			//	Obtain the path within the website, without the website base
			//	we use this to calculate the path inside the website, not relative to the document root
			self::$path	=	Amslib::lchop($_SERVER["REQUEST_URI"],self::$base);
			self::$path =	Amslib::rchop(self::$path,"?");
			self::$path	=	Amslib_File::reduceSlashes("/".self::$path."/");
		}
		
		self::load(Amslib::locate()."/router/router.xml","xml","framework");
	}

	static public function finalise()
	{
		if(!self::$path){
			trigger_error("There was no __AMSLIB_ROUTER_ACTIVE__ definition found, check your .htaccess file",E_USER_ERROR);
			return false;
		}

		self::$route = false;

		//	TODO: document better what this code does
		$static = self::getRouteByURL(self::$path);
		//	Find all the matches and store all the route names here
		$matches = array($static["src_selected"]=>$static);
		foreach(self::$callback as $c){
			$data = call_user_func($c,self::$path);

			$route = $data ? self::getRoute($data["name"]) : false;

			if($route) $matches[$data["src_selected"]] = array_merge($route,$data);
		}
		$matches = array_filter($matches);

		if(count($matches) == 1){
			//	set the only result
			self::$route = current($matches);
		}else{
			$longest = "";

			//	search for the longest match in the array
			foreach(Amslib_Array::valid($matches) as $k=>$r){
				if(strlen($k) > strlen($longest)) $longest = $k;
			}

			self::$route = isset($matches[$longest]) ? $matches[$longest] : self::$emptyRoute;
		}
	}

	static public function getPath($key=NULL)
	{
		if($key == NULL || !isset(self::$pathList[$key])) return self::$path;

		return self::$pathList[$key];
	}

	static public function setPath($key,$value)
	{
		self::$pathList[$key] = $value;
	}

	static public function listPaths()
	{
		return array_keys(self::$pathList);
	}

	static public function getBase()
	{
		return self::$base;
	}

	static public function load($source,$type,$group,$domain=NULL)
	{
		try{
			switch($type){
				case "xml":{		$s = new Amslib_Router_Source_XML($source);		}break;
				case "database":{	$s = new Amslib_Router_Source_Database($source);}break;
			}

			if($domain == NULL) $domain = self::$domain;
			
			foreach(Amslib_Array::valid($s->getRoutes()) as $route){
				self::setRoute($route["name"],$group,$domain,$route);
			}
			
			foreach(Amslib_Array::valid($s->getImports()) as $import){
				self::importRouter($import);
			}
		}catch(Exception $e){
			return false;
		}

		return true;
	}

	static public function setCallback($callback)
	{
		if($callback !== NULL && strlen($callback) && !in_array($callback,self::$callback)){
			self::$callback[] = $callback;
		}
	}

	static public function getLanguage()
	{
		return isset(self::$route["lang"]) ? self::$route["lang"] : false;
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

	static public function getURL($name=NULL,$group=NULL,$lang="default",$domain=NULL)
	{
		if($domain == NULL) $domain = self::$domain;
		
		$route = self::getRoute($name,$group,$domain);
		
		//	NOTE: I think it's safe to assume sending NULL means you want the default language
		//	NOTE: otherwise it would never ever match a language and the system would fail worse
		if($lang == NULL) $lang = "default";

		$url = isset($route["src"][$lang]) ? $route["src"][$lang] : "";

		//	If the url contains a http, don't attempt to make it relative, it's an absolute url
		//	NOTE: perhaps a better way to solve this is to mark routes as absolute, then I don't have to "best guess"
		return strpos($url,"http") !== false ? $url : Amslib_Website::rel($url);
	}

	static public function getRouteByURL($url=NULL)
	{
		if($url == NULL) return self::$route;

		$route	=	false;

		//	Obtain the route which is responsible for the url
		$select = self::findLongest(self::$url,$url);

		//	explode the remaining parts of the url into url parameters
		if(strlen($select) && isset(self::$url[$select])){
			$route = self::$url[$select];
			$route = self::finaliseRoute($route,$select,$url);
		}

		return $route;
	}

	static public function getRoute($name=NULL,$group=NULL,$domain=NULL)
	{
		//	if there was no name, surely you mean return the current route
		if($name == NULL) return self::$route;
		
		//	If the domain parameter was NULL, use the default local domain
		if($domain == NULL) $domain = self::$domain;
		
		//	if you specify a group, look in the name array specifically for that group
		if($group && is_string($group)){
			$key = "$domain/$group/$name";

			if(isset(self::$cache[$key])) return self::$cache[$key];
		}

		//	if the group wasn't requested or didn't exist, or failed to find the route by name,
		//	look in the mixed/global cache for the last registered route with that name instead
		if($name && is_string($name)){
			$key = "$domain/$name";

			if(isset(self::$name[$key])) return self::$name[$key];
		}

		//	default to an empty route, to make sure the data returned is the right format, just empty
		return self::$emptyRoute;
	}

	static public function getServiceURL($name,$group=NULL,$lang="default",$domain=NULL)
	{
		return self::getURL("service:$name",$group,$lang,$domain);
	}

	static public function getService($name,$group=NULL)
	{
		return self::getRoute("service:$name",$group);
	}

	static public function setRoute($name,$group,$domain,$route,$updateURLCache=true)
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
		if($route["type"] == "service" && strpos($name,"service:") === false){
			$name = "service:".$name;
		}

		//	NOTE: we "reset" the values here because if you are
		//	importing/renaming a service, this will do it for free without complexity
		$route["name"]	=	$name;
		$route["group"]	=	$group;
		
		//	If domain was not specified, use the local default domain
		if($domain == NULL) $domain = self::$domain;
		
		//	store the route data underneath the name so you can explicitly search for it
		self::$cache[$domain."/$group/$name"]	=	&$route;
		self::$name[$domain."/$name"]			=	&$route;

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
		//	FIXME: I don't think it's reasonable to understand that "" => complete array
		//	NOTE: perhaps we should default to "*" and this can mean "everything" instead?
		//	NOTE: 28/01/2012=> I just ran into this issue again, "" is not a safe assumption at all
		if($default === "") $default = self::$route["url_param"];

		return $index !== NULL && isset(self::$route["url_param"][$index])
				? self::$route["url_param"][$index]
				: $default;
	}
	
	static public function setURLParam($index,$value)
	{
		if($index && is_int($index)){
			self::$route["url_param"][$index] = $value;
		}
	}

	static public function getStylesheet()
	{
		return isset(self::$route["stylesheet"]) ? self::$route["stylesheet"] : array();
	}

	static public function getJavascript()
	{
		return isset(self::$route["javascript"]) ? self::$route["javascript"] : array();
	}

	static public function decodeURLPairs($offset=0)
	{
		//	here we decode the url into a series of k/v/k/v pairs => [k,v],[k,v]
		//	e.g. list/[name]/page/[number] => [list,name],[page,number]
		$p = array();
		$k = $v = false;
		$u = Amslib_Array::valid(self::getURLParam());
		if($o=intval($offset)) $u = array_slice($u,$o);

		foreach($u as $value){
			if($k == false) $k = $value;
			else if($v == false) $v = $value;

			if($v !== false && $k !== false){
				$p[$k] = $v;
				$k = $v = false;
			}
		}

		return $p;
	}
	
	static public function setExportRestriction($group,$status)
	{
		self::$export[$group] = $status;
	}
	
	static public function getExportRestriction($group)
	{
		return !isset(self::$export[$group]) || self::$export[$group];
	}
	
	static public function importRouter($import)
	{
		//	acquire the latest route for the export url and construct the url to call the external remote service
		$route	=	self::getRoute("service:framework:router:export:".$import["attr"]["type"]);
		$url	=	$import["attr"]["url"].$route["src"]["default"];
		
		if($import["attr"]["type"] == "json"){
			//	We are going to install a router using json as a data transfer medium
			//	Acquire the json, decode it and obtain the domain
			$data	= file_get_contents($url);
			$data	= json_decode($data,true);
			$domain	= $data["domain"];
			
			//	For each route in the cache, create a new route in the local router, giving the name, group, domain and route data
			//	You are not supposed to update the url cache, imported routes are not accessible through url
			//	The reason for this is because it doesnt make sense a url from a remote system will be processed by the local system
			//	All url requests for imported services should goto the remote server directly
			foreach(Amslib_Array::valid($data["cache"]) as $route){
				self::setRoute($route["name"],$route["group"],$domain,$route,false);
			}
		}else if($import["attr"]["type"] == "xml"){
			$data = file_get_contents($url);
			//	TODO: implement the logic to import from XML 
		}
	}
	
	static public function exportRouterShared()
	{
		//	Construct the protocol.domain to prepend all the urls with
		$protocol	=	"http".(isset($_SERVER["HTTPS"]) || (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]) ? "s" : "")."://";
		$domain		=	$_SERVER["HTTP_HOST"];
		
		//	The raw data source before processing
		$data = array(
				"domain"	=>	$protocol.$domain,
				"cache"		=>	self::$cache
		);
		
		//	For each cache block, remove the javascript, stylesheet and any framework routes
		//	For each src inside each cache block, prepend the domain to each url making them accessible remotely
		foreach($data["cache"] as $k=>&$r){
			unset($r["stylesheet"]);
			unset($r["javascript"]);
			unset($r["handler"]);
				
			foreach($r["src"] as &$s) $s = $data["domain"].$s;
			
			if(strpos($k,"framework") || !self::getExportRestriction($r["group"])){
				unset($data["cache"][$k]);
			}
		}
		
		return $data;
	}
	
	static public function serviceExportRouterXML($service,$source)
	{
		die("NOT IMPLEMENTED YET");
	}
	
	static public function serviceExportRouterJSON($service,$source)
	{
		$data = self::exportRouterShared();
		
		Amslib_Website::outputJSON($data);
	}
	
	static public function serviceExportRouterDEBUG($service,$source)
	{
		die(Amslib::var_dump(self::exportRouterShared(),true));
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
