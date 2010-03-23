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
 * Title: Core router object for the router system
 * Version: 1.1
 * Project: Amslib/Router
 * 
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router
{
	protected $routes;
	protected $routeCache;
	protected $pages;
	protected $baseDir;
	protected $option;
	
	protected $resource;
	protected $routerPath;
	
	protected $activeRoute;
	
	protected $language;

	protected function initialise()
	{
		Amslib_Router_URL::setRouter($this);
		
		$this->setupCache();
		$this->setupRouter();
	}
	
	protected function pathToOption()
	{
		$parts	=	explode("/",$this->routerPath);
		$last	=	array_pop($parts);
		
		$this->option = false;
		
		if(strpos($last,".php"))
		{
			$this->routerPath	=	implode("/",$parts);
			$this->option		=	$last;
			//	FIXME: Inserting this information directly into the $_GET param might not be safe for some applications
			Amslib::insertGetParam("page",$last);
		}
	}
	
	protected function setupRouter()
	{
		$this->resource = Amslib::getParam("resource","router");
		
		if($this->resource == "router"){
			$this->routerPath = Amslib::getParam("router_path",Amslib_Router_URL::Home());
			
			Amslib_Router_Language::detect($this->routerPath);
			
			if(Cfg_Routes::$phpFileAsOption == true) $this->pathToOption();

			//	Append a / to the string and then replace any // with / (removing any duplicates basically)
			$this->routerPath = str_replace("//","/",$this->routerPath."/");
			
			if(isset($this->routes[$this->routerPath])){
				$this->activeRoute	= $this->routes[$this->routerPath];

				return;
			}
			
			die(var_dump("Cannot find route, dump debug info",$_GET,$this->routerPath));	
		}
	}
	
	/**
	 * method: setupCache
	 * 
	 * Initialises a cache of routes in the session so they can be looked up faster
	 * 
	 * notes:
	 * 	-	it is debatable whether this actually speeds anything up
	 * 	-	what happens when you change language and the entire route cache becomes invalid
	 */
	protected function setupCache()
	{
		if($this->routeCache == false){  
			if( Cfg_Routes::$disableRouterCache == true || !isset($_SESSION["route_cache"]))
			{
				$_SESSION["route_cache"] = array();
	
				foreach(Cfg_Routes::$paths as $p=>$d){
					if(!isset($d["option"])) $d["option"] = "default";
					if(!isset($_SESSION["route_cache"][$d["resource"]][$d["option"]])){
						$_SESSION["route_cache"][$d["resource"]][$d["option"]] = $p;
					}
				}
			}
			
			$this->routeCache = &$_SESSION["route_cache"];
		}
	}
	
	/**
	 * method: __construct
	 * 
	 * The constructor for the Router core object
	 * 
	 * parameters:
	 * 	type	-	the type of router to build, right now, only "builtin" is supported and given as default parameter
	 * 
	 * notes:
	 * 	-	what happens when you dont want to use builtin, but a custom method.
	 * 	-	the whole system is built on Cfg_Routes, which needs to change.
	 */
	public function __construct($type=NULL)
	{
		$this->routes	=	false;
		//	FIXME: we are assigning the instance here and inside the singleton method
		self::$instance	=	$this;
		
		if($type == NULL) $type = "builtin";
		
		switch($type){
			case "builtin":{
				$this->routes = Cfg_Routes::$paths;
			}break;
			
			case "database":{
				//	Setup a database router, a table in the database that contains all the routes and is queried dynamically
			}
		}
		
		if($this->routes){
			$this->initialise();
			$this->setFilePath("");
		}
	}
	
	public function setFilePath($baseDir)
	{
		$this->baseDir = $baseDir;
	}
	
	public function filePath($path="")
	{
		return $this->baseDir.$path;
	}
	
	public function getOption()
	{
		return $this->option;
	}
	
	public function getResource()
	{
		return $this->activeRoute["resource"];
	}
	
	public function getCurrentRouteAttribute($attribute)
	{
		if(isset($this->activeRoute[$attribute])){
			return $this->activeRoute[$attribute];
		}
		
		return false;
	}
	
	public function getCurrentRoute($language=false)
	{
		if(!$language) $language = Amslib_Router_Language::get(true);
		
		return $language.$this->routerPath;
	}
	
	/**
	 * method: getRoute
	 * 
	 * initialise the router cache, or return it, if it was previously initialised
	 * 
	 * operations:
	 * 	-	check router cache is false (need to obtain/initialise)
	 * 	-	check router cache exists in session data
	 * 	-	if not, create it from the config routes data
	 * 	-	for each route, make the controller name and the option the array keys in finding the path
	 * 	-	option=default if then option is not available
	 * 	-	When finished, set the routeCache parameter
	 */
	public function getRoute($controller,$option="default")
	{
		//	if the route exists, return it, or false
		if(isset($this->routeCache[$controller][$option])){
			return Amslib_Router_Language::get(true).$this->routeCache[$controller][$option];	
		}
		
		return false;
	}
	
	public function &getInstance($type=NULL)
	{
		if(self::$instance === NULL){
			self::$instance = new Amslib_Router($type);
		}
	
		return self::$instance;
	}
	
	public static $instance = NULL;
}