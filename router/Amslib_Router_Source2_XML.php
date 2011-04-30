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
 * File: Amslib_Router_Source2_XML.php
 * Title: The XML router source reader, version 2.0 of the api/object
 * Version: 2.0
 * Project: Amslib/Router/Source
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/
class Amslib_Router_Source2_XML
{
	protected $xpath;
	protected $routes;
	protected $url;

	protected function findNodes($name,$parent)
	{
		if(!$parent || !$parent->hasChildNodes()) return array();

		$results = array();

		foreach($parent->childNodes as $p){
			if($p->nodeName == $name) $results[] = $p;
		}

		return $results;
	}

	protected function decodeOptions($url,$route)
	{
		$params = trim(Amslib::lchop($url,$route),"/");

		return (!empty($params)) ? explode("/",$params) : array();
	}

	protected function decodeParameters($path)
	{
		$nodes = $this->findNodes("parameter",$path);

		$list = array();

		foreach($nodes as $p){
			$id = $p->getAttribute("id");
			if(!$id) continue;

			$list[$id] = $p->nodeValue;
		}

		return $list;
	}

	protected function decodeSources($path)
	{
		$nodes = $this->findNodes("src",$path);

		$list = array();

		foreach($nodes as $s){
			$version = $s->getAttribute("version");
			if(!$version) $version = "default";

			$lang = $s->getAttribute("lang");
			if(!$lang) $lang = "all";

			$list[$version][$lang] = $s->nodeValue;
		}

		return $list;
	}

	protected function decodeResource($path)
	{
		$resource = $this->findNodes("resource",$path);

		return ($resource && count($resource) > 0) ? $resource[0]->nodeValue : false;
	}

	protected function addInversePath($name)
	{
		$route = $this->routes[$name];

		foreach($route["src"] as $version=>$src){
			foreach($src as $lang=>$url){
				$this->url[$url] = array(
					"version"		=>	$version,
					"name"			=>	$name,
					"resource"		=>	$route["resource"],
					"route"			=>	$url,
					"lang"			=>	$lang,
					"parameters"	=>	$route["parameters"]
				);
			}
		}
	}

	public function __construct()
	{
		$this->routes	=	array();
		$this->url		=	array();
	}

	public function load($source)
	{
		//	NOTE:	Added a call to absolute to fix finding the file, because in some cases,
		//			the file cannot be found. But I am not sure of the side-effects (if any) of doing this
		$path = Amslib_Filesystem::find(Amslib_Filesystem::absolute($source),true);

		if(!file_exists($path)){
			//	TODO: Should move to using Amslib_Keystore("error") instead
			print("Amslib_Router_Source2_XML::load(), source = ".Amslib::var_dump($source,true));
			die("Amslib_Router_Source2_XML::load(), source file does not exist");
		}

		$routes = array();

		$document = new DOMDocument('1.0', 'UTF-8');
		if($document->load($path)){
			$this->xpath = new DOMXPath($document);

			$paths = $this->xpath->query("//router/path | router/path");

			foreach($paths as $p) $this->addPath($p,$routes);
		}else{
			//	TODO: Should move to using Amslib_Keystore("error") instead
			print("Amslib_Router_Source2_XML::load(), source[$source], path[$path] FAILED TO OPEN<br/>");
		}

		return $routes;
	}

	/**
	 * method: addPath
	 *
	 * A method to add a path to the routes configured by the system, it finds and decodes
	 * all the appropriate nodes in the xml to find all the configuration information
	 *
	 * parameters:
	 * 	path	-	The XML Node in the amslib_router.xml called "path"
	 *
	 * notes:
	 * 	This method is exposed as public because it is useful sometimes to store the xml configuration
	 * 	in another document, but "graft" the route onto the main router configuration as if there
	 * 	was no difference between them.  The administration panel project is a good example of this,
	 * 	The admin_panel.xml file stores each plugin, plus the router configuration, in this case, we need
	 * 	to decode that config, but we dont want to duplicate the decoding mechanism.
	 *
	 *  IMPORTANT:	Not entirely sure whether this is a good idea, or just exposing a security hole
	 *
	 *  TODO:		I actually need this for dynamically allowing widgets to insert their own routes by being installed
	 *  			you can't expect the installer to know how to update the router xml, so they have to supply their own routes
	 *  			and be loaded here, so instead, we need to SUPER VALIDATE everything that passes through this method.
	 *  			Because it's ALL user defined and therefore bullshit, broken and mischevious :)
	 *  NOTE:		actually, the router xml is defined by the user too, so it should be protected in any situation
	 */
	public function addPath($path,&$routes=NULL)
	{
		$path = array(
			"name"			=>	$path->getAttribute("name"),
			"src"			=>	$this->decodeSources($path),
			"resource"		=>	$this->decodeResource($path),
			"parameters"	=>	$this->decodeParameters($path)
		);

		return $this->createPath($path,$routes);
	}
	
	public function createPath($path,&$routes=NULL)
	{
		$name = $path["name"];
		
		$this->routes[$name] = $path;
		
		$this->addInversePath($name);
		
				//	NOTE:	This is so the route can be captured and returned
		//			We need to do this so plugins can know whether a route belongs to them
		//			or someone else, this is needed because sometimes we need to identify
		//			which route is active, based on the url open
		if(is_array($routes)) $routes[$name] = $this->routes[$name];

		return $this->routes[$name];
	}

	//	NOTE: What does versions do again?
	public function getURL($name,$version="default",$lang="all")
	{
		//	Protect against NULL values
		if($version == NULL)	$version	=	"default";
		if($lang == NULL)		$lang		=	"all";

		if(	isset($this->routes[$name]) &&
			isset($this->routes[$name]["src"][$version]))
		{
			$v = $this->routes[$name]["src"][$version];

			if(!empty($v)){
				return (isset($v[$lang])) ? $v[$lang] : current($v);
			}
		}

		return false;
	}

	public function getRoute($name,$version="default",$lang="all")
	{
		//	Protect against NULL values
		if($version == NULL)	$version	=	"default";
		if($lang == NULL)		$lang		=	"all";

		$route = false;

		if(isset($this->routes[$name]["src"][$version])){
			$v = $this->routes[$name]["src"][$version];

			if(!empty($v)){
				$url	=	(isset($v[$lang])) ? $v[$lang] : current($v);
				$route	=	$this->url[$url];
			}
		}

		return $route;
	}

	public function getRouteByURL($url)
	{
		$route = false;

		if(isset($this->url[$url])){
			//	The url exists exactly as it was requested, then do this shortcut
			$route				=	$this->url[$url];
			$route["options"]	=	array();
		}else{
			$key = array_keys($this->url);

			//	Find the longest route that matches against the requested path
			$match = "";
			foreach($key as $k){
				if(strpos($url,$k) !== false && strlen($k) > strlen($match)){
					$match = $k;
				}
			}

			//	Match is found, is a not zero length string and exists as a key in the url array
			if($match && is_string($match) && strlen($match) && isset($this->url[$match])){
				$route				=	$this->url[$match];
				$route["options"]	=	$this->decodeOptions($url,$route["route"]);
			}
		}

		return $route;
	}
	
	public function dump()
	{
		return array("routes"=>$this->routes,"url"=>$this->url);
	}

	static public function &getInstance($source=NULL)
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		if($instance && $source) $instance->load($source);

		return $instance;
	}
}
