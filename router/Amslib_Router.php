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
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *
 *******************************************************************************/

/**
 * 	class:	Amslib_Router
 *
 *	group:	router
 *
 *	file:	Amslib_Router.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Router
{
	/**
	 * variable: $emptyRoute
	 *
	 * The template of an empty route, used when no route is found, we can quickly return this one
	 */
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

	/**
	 * variable: $base
	 *
	 * The base path of the website, where it's installed in the document root
	 */
	static protected $base		=	false;

	/**
	 * variable: $path
	 *
	 * The path of the website, that is after the base path
	 */
	static protected $path		=	false;

	static protected $pathList	=	array();
	static protected $route		=	false;
	static protected $cache		=	array();
	static protected $name		=	array();
	static protected $url		=	array();
	static protected $callback	=	array();

	static protected $import	=	array();
	static protected $export	=	array();

	/**
	 * Variable: $domain
	 *
	 * The default domain string to use with routes that do not provide their own
	 */
	static protected $domain = "__LOCAL_DOMAIN__";

	/**
	 * 	method:	finaliseRoute
	 *
	 * 	todo: write documentation
	 */
	static protected function finaliseRoute($route,$select,$url,$url_params)
	{
		//	Don't replace anything if the string is / because it'll nuke all the separators
		$replace	=	$select == "/" ? "" : $select;
		$params		=	Amslib::lchop($url,$replace);

		//	Filter out NULL or empty elements and return ONLY a valid array of valid components
		$route["url_param"] = Amslib_Array::valid(array_filter(array_merge($url_params,explode("/",trim($params,"/ ")))));

		//	set the language based on the current route and selected url
		//	NOTE: probably going to need to refactor this block of code at some point
		$route["lang"] = array_search($select,$route["src"]);
		if($route["lang"] == "default"){
			$src = $route["src"];
			unset($src["default"]);
			$src = array_keys($src);
			$route["lang"] = array_shift($src);
		}

		//	Set the selected url from the src list
		$route["src_selected"] = $select;

		return $route;
	}

	/**
	 * 	method:	findLongest
	 *
	 * 	todo: write documentation
	 */
	static protected function findLongest($list,$url,$group=NULL,&$url_params=array())
	{
		$select		= "";
		$url_params	= array();

		foreach(self::$url as $u=>$d){
			//	Only allow matches against a requested group
			if($group && $d["group"] != $group) continue;

			//	Easy shortcut, if the u parameter matches EXACTLY the url, stop here, you are going
			//	to use this one, no point in processing more
			if($u === $url) return $u;

			if(preg_match("/^".str_replace("/","\/",$u)."/",$url,$matches) && count($matches)){
				$c = self::$url[$u];
				$u = $matches[0];
				$url_params = array_slice($matches,1);
				self::$url[$u] = $c;
			}

			if($url != "/" && strpos($url,$u) === 0 && strlen($u) >= strlen($select)){
				$select = $u;
			}else if($url == $u){
				$select = $u;
			}
		}

		//	Any matched parameters from the url will be treated like url parameters
		//	and placed at the front of the url parameter list, any "natural" url
		//	parameters that are appending to the recognised url, will appear afterwards
		$refactor = array();
		foreach($url_params as $parts){
			$refactor = array_merge($refactor,explode("/",$parts));
		}
		$url_params = $refactor;

		return $select;
	}

	/**
	 * 	method:	initialise
	 *
	 * 	todo: write documentation
	 */
	static public function initialise()
	{
		//	Find all the AMSLIB_ROUTER type variables and insert them into the pathList
		//	NOTE: I probably don't need to scan through $_SERVER and can just optimise this to use $_GET in the future when I'm sure
		foreach(array_merge($_SERVER,$_GET) as $k=>$v){
			if(strpos($k,"AMSLIB_ROUTER") !== false) self::$pathList[$k] = $v;
		}

		//	TODO:	I should use an easier expression than __AMSLIB_ROUTER_ACTIVE__,
		//			perhaps __WEBSITE__ is more normal, or even __AMSLIB_WEBSITE__

		self::$path	=	NULL;
		self::$base	=	self::getPath("__AMSLIB_ROUTER_ACTIVE__");

		if(self::$base){
			//	Obtain the path within the website, without the website base
			//	we use this to calculate the path inside the website, not relative to the document root
			self::$path	=	Amslib::lchop($_SERVER["REQUEST_URI"],self::$base);
			self::$path =	Amslib::rchop(self::$path,"?");
			self::$path	=	Amslib_File::reduceSlashes("/".self::$path."/");
		}

		//	Now automatically load the amslib routers configuration as the framework system
		//	This allows amslib to add routes the system can use to import/export router configurations
		//	This isn't part of your application, this is part of the system and used to self-configure
		self::load(Amslib::locate()."/router/router.xml","xml","framework");
	}

	/**
	 * 	method:	finalise
	 *
	 * 	todo: write documentation
	 */
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

		//	NOTE: so every route passes through the callback, even though it doesnt ask for it??
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
			//	If there was more than one match, we need to search for the longest one and discard the rest
			$longest = "";

			//	search for the longest match in the array
			foreach(Amslib_Array::valid($matches) as $k=>$r){
				if(strlen($k) > strlen($longest)) $longest = $k;
			}

			self::$route = isset($matches[$longest]) ? $matches[$longest] : self::$emptyRoute;
		}
	}

	/**
	 * 	method:	getPath
	 *
	 * 	todo: write documentation
	 */
	static public function getPath($key=NULL)
	{
		if($key == NULL || !isset(self::$pathList[$key])) return self::$path;

		return self::$pathList[$key];
	}

	/**
	 * 	method:	setPath
	 *
	 * 	todo: write documentation
	 */
	static public function setPath($key,$value)
	{
		self::$pathList[$key] = $value;
	}

	/**
	 * 	method:	listPaths
	 *
	 * 	todo: write documentation
	 */
	static public function listPaths()
	{
		return array_keys(self::$pathList);
	}

	/**
	 * 	method:	getBase
	 *
	 * 	todo: write documentation
	 */
	static public function getBase()
	{
		return self::$base;
	}

	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	static public function load($source,$type,$group,$domain=NULL)
	{
		try{
			switch($type){
				case "xml":{		$s = new Amslib_Router_Source_XML($source);		}break;
				case "database":{	$s = new Amslib_Router_Source_Database($source);}break;
			}

			if($domain == NULL) $domain = self::$domain;

			foreach($s->getRoutes() as $route){
				self::setRoute($route["name"],$group,$domain,$route);
			}

			foreach($s->getImports() as $import){
				self::importRouter($import);
			}
		}catch(Exception $e){
			return false;
		}

		return true;
	}

	/**
	 * 	method:	setCallback
	 *
	 * 	todo: write documentation
	 */
	static public function setCallback($callback)
	{
		if($callback !== NULL && strlen($callback) && !in_array($callback,self::$callback)){
			self::$callback[] = $callback;
		}
	}

	/**
	 * 	method:	getLanguage
	 *
	 * 	todo: write documentation
	 */
	static public function getLanguage()
	{
		return isset(self::$route["lang"]) ? self::$route["lang"] : false;
	}

	/**
	 * 	method:	setLanguage
	 *
	 * 	todo: write documentation
	 */
	static public function setLanguage($lang)
	{
		//	TODO: write how this would work
	}

	/**
	 * 	method:	changeLanguage
	 *
	 * 	todo: write documentation
	 */
	static public function changeLanguage($lang,$fullRoute=NULL,$key=NULL)
	{
		//	NOTE: what does fullRoute mean??
		//	NOTE: what does key mean??
		//	NOTE: what if we are processing languages like /es_ES/route ?
		//	NOTE: (16/04/2014) => still no idea, those parameters are not even used here.

		return self::getURL(NULL,NULL,$lang);
	}

	/**
	 * 	method:	getURL
	 *
	 * 	todo: write documentation
	 */
	static public function getURL($name=NULL,$group=NULL,$lang="default",$domain=NULL)
	{
		if($domain == NULL) $domain = self::$domain;

		//	if the $name parameter is an array, this code will split the $name parameter to
		//	use the first as the route name and all the rest as optional parameters
		if(is_array($name)){
			$params	=	array_slice($name,1);
			$name	=	array_shift($name);
		}else $params = array();

		$route = self::getRoute($name,$group,$domain);

		//	NOTE: I think it's safe to assume sending NULL means you want the default language
		//	NOTE: otherwise it would never ever match a language and the system would fail worse
		//	NOTE: although this is silly, cause it means that passing "default" and NULL are the same, why not just use one?
		if($lang == NULL) $lang = "default";

		$url = isset($route["src"][$lang]) ? $route["src"][$lang] : "";

		//	If the url contains a http, don't attempt to make it relative, it's an absolute url
		//	NOTE: perhaps a better way to solve this is to mark routes as absolute, then I don't have to "best guess"
		$url = strpos($url,"http") !== false ? $url : Amslib_Website::rel($url);

		//	NOTE: perhaps I should move this highly specific code into it's own method so I can keep things small and tidy
		//	Now we can replace all the wildcard targets in the url with parameters passed into the function
		$target	=	array('(\w+)','(.*?)','(.*)');
		$append	=	array();

		foreach(Amslib_Array::valid($params) as $k=>$p){
			//	I copied this replacement code from: http://stackoverflow.com/revisions/1252710/3
			$pos = false;
			foreach($target as $t){
				$pos = strpos($url,$t);
				if($pos !== false){
					$pos = array($pos,strlen($t));
					break;
				}
			}

			if (is_array($pos)){
				$url = substr_replace($url,$p,$pos[0],$pos[1]);
			}else{
				if(!is_array($p)) $p = array($p);

				$append = array_merge($append,$p);
			}
		}

		//	NOTE:	this is pretty dumb, I should try to work out a generic way to deal with all of these
		//			http-like prefixes so I don't have to keep writing this shitty code everywhere.....
		$prefix = "";
		if(strpos($url,"http://") !== false) $prefix = "http://";
		if(strpos($url,"https://") !== false) $prefix = "https://";

		return $prefix.Amslib_File::reduceSlashes(str_replace($prefix,"",$url).implode("/",array_filter($append))."/");
	}

	/**
	 * 	method:	getRouteByURL
	 *
	 * 	todo: write documentation
	 */
	static public function getRouteByURL($url=NULL,$group=NULL)
	{
		if($url == NULL) return self::$route;

		$route	=	false;

		//	Obtain the route which is responsible for the url
		$select = self::findLongest(self::$url,$url,$group,$url_params);

		//	explode the remaining parts of the url into url parameters
		if(strlen($select) && isset(self::$url[$select])){
			$route = self::$url[$select];
			$route = self::finaliseRoute($route,$select,$url,$url_params);
		}

		return $route;
	}

	/**
	 * 	method:	getRoute
	 *
	 * 	todo: write documentation
	 */
	static public function getRoute($name=NULL,$group=NULL,$domain=NULL)
	{
		//	if there was no name, surely you mean return the current route
		if($name == NULL) return self::$route;

		//	If the domain parameter was NULL, use the default local domain
		if($domain == NULL) $domain = self::$domain;

		//	Trim the / from the domain so it can be found correctly when concatenated
		$domain = rtrim($domain,"/");

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

	/**
	 * 	method:	getServiceURL
	 *
	 * 	todo: write documentation
	 *
	 *	NOTE:	If the first parameter is an array, it means there are url parameters to insert into the
	 *			final url, but we need to modify the first section of the array, which holds the name of the
	 *			route that was requested, since the other elements are just parameters, we don't touch those
	 */
	static public function getServiceURL($name,$group=NULL,$lang="default",$domain=NULL)
	{
		if(is_array($name) && count($name)) $name[0] = "service:{$name[0]}";
		else $name = "service:$name";

		return self::getURL($name,$group,$lang,$domain);
	}

	/**
	 * 	method:	getService
	 *
	 * 	todo: write documentation
	 *
	 *	NOTE:	If the first parameter is an array, it means there are url parameters to insert into the
	 *			final url, but we need to modify the first section of the array, which holds the name of the
	 *			route that was requested, since the other elements are just parameters, we don't touch those
	 *
	 *	NOTE:	It was recognised that even if the $name parameter was an array, containing url parameters
	 *			those parameters will not be used as part of the url because the getRoute method has
	 *			no ability to modify the url yet, but it's posted as a future update
	 */
	static public function getService($name,$group=NULL,$domain=NULL)
	{
		$name = "service:".(is_array($name) && count($name) ? $name[0] : $name);

		return self::getRoute($name,$group,$domain);
	}

	/**
	 * 	method:	setRoute
	 *
	 * 	todo: write documentation
	 */
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
		$domain = rtrim($domain,"/");

		//	store the route data underneath the name so you can explicitly search for it
		self::$cache[$domain."/$group/$name"]	=	&$route;
		self::$name[$domain."/$name"]			=	&$route;

		//	store the route data referencing it by url, so you can build a request url cache
		if($updateURLCache) foreach($route["src"] as $s){
			self::$url[$s] = &$route;
		}
	}

	/**
	 * 	method:	getName
	 *
	 * 	todo: write documentation
	 */
	static public function getName()
	{
		return self::$route["name"];
	}

	/**
	 * 	method:	isRouted
	 *
	 * 	todo: write documentation
	 */
	static public function isRouted()
	{
		return self::$path !== NULL ? true : false;
	}

	/**
	 * 	method:	isService
	 *
	 * 	todo: write documentation
	 */
	static public function isService()
	{
		return self::$route && isset(self::$route["type"]) && self::$route["type"] == "service"
			? true
			: false;
	}

	/**
	 *	method:	hasRoute
	 *
	 *	Say whether or not there was a detected route, the system will return an empty route
	 *	if there is nothing matching in the router, we can use this system to know whether
	 *	the route was valid or not
	 *
	 *	returns:
	 *		Boolean true or false, depending on whether the route was valid (not empty) or not
	 */
	static public function hasRoute()
	{
		return self::$emptyRoute != self::getRoute();
	}

	/**
	 * 	method:	getResource
	 *
	 * 	todo: write documentation
	 */
	static public function getResource($name=NULL)
	{
		$r = ($name == NULL) ? self::$route : self::getRoute($name);

		return $r && isset($r["resource"]) ? $r["resource"] : false;
	}

	/**
	 * 	method:	getRouteParam
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	setRouteParam
	 *
	 * 	todo: write documentation
	 */
	static public function setRouteParam($name,$value)
	{
		if(self::$route && isset(self::$route["route_param"])){
			self::$route["route_param"][$name] = $value;
		}
	}

	/**
	 * 	method:	hasRouteParam
	 *
	 * 	todo: write documentation
	 */
	static public function hasRouteParam($name)
	{
		return in_array($name,self::$route["route_param"]) ? true : false;
	}

	/**
	 * 	method:	getURLParam
	 *
	 * 	todo: write documentation
	 */
	static public function getURLParam($index=NULL,$default="")
	{
		//	if there are no url options, return the default
		if(!isset(self::$route["url_param"])) return $default;

		//	if the default was an empty string, set the default to the entire options array
		//	FIXME: I don't think it's reasonable to understand that "" => complete array
		//	NOTE: perhaps we should default to "*" and this can mean "everything" instead?
		//	NOTE: 28/01/2012 => I just ran into this issue again, "" is not a safe assumption at all
		//	NOTE: 01/04/2014 => If you want to obtain a string, or the first param, it'll return an array, breaking strtolower() or whatever method is expecting a string
		//	FUCK IT, LETS TRY TO SEE WHAT HAPPENS :D
		//if($default === "") $default = self::$route["url_param"];
		if($default === NULL) $default = self::$route["url_param"];

		return $index !== NULL && isset(self::$route["url_param"][$index])
				? self::$route["url_param"][$index]
				: $default;
	}

	/**
	 * 	method:	setURLParam
	 *
	 * 	todo: write documentation
	 */
	static public function setURLParam($index,$value)
	{
		if($index && is_int($index)){
			self::$route["url_param"][$index] = $value;
		}
	}

	/**
	 * 	method:	getStylesheet
	 *
	 * 	todo: write documentation
	 */
	static public function getStylesheet()
	{
		return isset(self::$route["stylesheet"]) ? self::$route["stylesheet"] : array();
	}

	/**
	 * 	method:	getJavascript
	 *
	 * 	todo: write documentation
	 */
	static public function getJavascript()
	{
		return isset(self::$route["javascript"]) ? self::$route["javascript"] : array();
	}

	/**
	 * 	method:	decodeURLPairs
	 *
	 * 	todo: write documentation
	 * 	note: probably this doesn't belong here, but in a generic "url, web" object instead
	 */
	static public function decodeURLPairs($offset=0)
	{
		//	here we decode the url into a series of k/v/k/v pairs => [k,v],[k,v]
		//	e.g. list/[name]/page/[number] => [list,name],[page,number]
		$p = array();
		$k = $v = false;
		$u = Amslib_Array::valid(self::getURLParam(NULL,NULL));
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

	/**
	 * 	method:	encodeURLPairs
	 *
	 * 	todo: write documentation
	 * 	note: probably this doesn't belong here, but in a generic "url, web" object instead
	 */
	static public function encodeURLPairs($array,$separator="/",$ignore=array())
	{
		$p = array();

		if(!is_array($ignore)) $ignore = array();

		foreach(Amslib_Array::valid($array) as $k=>$v){
			if(!empty($ignore) && in_array($k,$ignore)) continue;

			$p[] = $k;
			$p[] = $v;
		}

		return implode($separator,$p);
	}

	/**
	 * 	method:	setImportData
	 *
	 * 	Set the import data we received from the data source
	 *
	 * 	params:
	 * 		$name	-	The name of the import
	 * 		$data	-	The import data
	 *
	 * 	notes:
	 * 		-	If the $name given is not a valid or empty string, it
	 * 			will still be added, but the name set will be similar to
	 * 			error_0, error_1, etc.
	 */
	static public function setImportData($name,$data)
	{
		if(!is_string($name) || !strlen($name)) $name = "error_".count(self::$import);

		self::$import[$name] = $data;
	}

	/**
	 * 	method:	getImportData
	 *
	 * 	Obtain the import data by a particular name, returning a default if not found, or the entire import data if set to NULL/false
	 *
	 * 	params:
	 * 		$name		-	The name of the import data to retrieve
	 * 		$default	-	The data to return if nothing was found
	 *
	 * 	notes:
	 * 		-	If the $default parameter is not an array e.g: NULL, it will act to return the entire import data
	 * 		-	If the $name given is not a valid or empty string, it will just immediately return all the import data
	 */
	static public function getImportData($name=NULL,$default=array())
	{
		if(!is_string($name) || !strlen($name)) return self::$import;

		return isset(self::$import[$name])
			? self::$import[$name]
			: (is_array($default) ? $default : self::$import);
	}

	/**
	 * 	method:	setExportRestriction
	 *
	 * 	todo: write documentation
	 */
	static public function setExportRestriction($group,$status)
	{
		self::$export[$group] = $status;
	}

	/**
	 * 	method:	getExportRestriction
	 *
	 * 	todo: write documentation
	 */
	static public function getExportRestriction($group)
	{
		return !isset(self::$export[$group]) || self::$export[$group];
	}

	/**
	 * 	method:	importRouter
	 *
	 * 	todo: write documentation
	 */
	static public function importRouter($import)
	{
		$params = self::encodeURLPairs($import,"/",array("output","url","name"));

		//	acquire the latest route for the export url and construct the url to call the external remote service
		$route				=	self::getRoute("service:framework:router:export:".$import["output"]);
		$import["url"]		=	Amslib_File::resolvePath(Amslib_Website::expandPath($import["url"]));
		$import["url_full"] =	rtrim($import["url"],"/").rtrim($route["src"]["default"],"/")."/$params/";

		if($import["output"] == "json"){
			//	We are going to install a router using json as a data transfer medium
			//	Acquire the json, decode it and obtain the domain
			ob_start();
			$data	= file_get_contents($import["url_full"]);
			$caught = ob_get_clean();

			if(strlen($caught)){
				Amslib::errorLog("FAILED TO IMPORT ROUTER IN JSON FORMAT, OR OTHER PROBLEM DETECTED",$caught,error_get_last());
			}

			$data	= json_decode($data,true);
			$domain	= $data["domain"];

			//	For each route in the cache, create a new route in the local router, giving the name, group, domain and route data
			//	You are not supposed to update the url cache, imported routes are not accessible through url
			//	The reason for this is because it doesnt make sense a url from a remote system will be processed by the local system
			//	All url requests for imported services should goto the remote server directly
			foreach(Amslib_Array::valid($data["cache"]) as $route){
				self::setRoute($route["name"],$route["group"],$domain,$route,false);
			}

			//	Record that we imported something so we can possibly use this information
			self::setImportData($import["name"],$import);

		}else if($import["output"] == "xml"){
			ob_start();
				$data = file_get_contents($import["url"]);
			$caught = ob_get_clean();

			if(strlen($caught)){
				Amslib::errorLog("FAILED TO IMPORT ROUTER IN XML FORMAT, OR OTHER PROBLEM DETECTED",$caught,error_get_last());
			}

			//	TODO: implement the logic to import from XML
		}
	}

	/**
	 * 	method:	exportRouterShared
	 *
	 * 	todo: write documentation
	 */
	static public function exportRouterShared($filter=NULL)
	{
		if(!in_array($filter,array("path","service"))) $filter = NULL;

		//	The raw data source before processing
		$data = array(
			"domain"	=>	Amslib_Router_URL::externalURL(self::$base),
			"cache"		=>	self::$cache
		);

		//	For each cache block, remove the javascript, stylesheet and any framework routes
		//	For each src inside each cache block, prepend the domain to each url making them accessible remotely
		foreach($data["cache"] as $k=>&$r){
			if($filter != NULL && $r["type"] != $filter){
				unset($data["cache"][$k]);
				continue;
			}

			unset($r["stylesheet"]);
			unset($r["javascript"]);
			unset($r["handler"]);

			foreach($r["src"] as &$s){
				//	If the url already contains this, it's an absolute url, you can't possibly add
				//	the domain to it, cause it already has one although I don't know if this will
				//	fit all circumstances, perhaps these urls shouldn't be exported
				if(strpos($s,"http://") !== false) continue;

				$s = rtrim($data["domain"],"/").$s;
			}

			if(strpos($k,"framework") || !self::getExportRestriction($r["group"])){
				unset($data["cache"][$k]);
			}
		}

		return $data;
	}

	/**
	 * 	method:	serviceExportRouterXML
	 *
	 * 	todo: write documentation
	 */
	static public function serviceExportRouterXML($service,$source)
	{
		die("NOT IMPLEMENTED YET");
	}

	/**
	 * 	method:	serviceExportRouterJSON
	 *
	 * 	todo: write documentation
	 */
	static public function serviceExportRouterJSON($service,$source)
	{
		$data = self::exportRouterShared();

		Amslib_Website::outputJSON($data);
	}

	/**
	 * 	method:	serviceExportRouterDEBUG
	 *
	 * 	todo: write documentation
	 */
	static public function serviceExportRouterDEBUG($service,$source)
	{
		die(Amslib::var_dump(self::exportRouterShared(),true));
	}

	/**
	 * 	method:	dump
	 *
	 * 	todo: write documentation
	 */
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
