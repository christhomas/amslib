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
 * file: Amslib_Plugin_Application.php
 * title: Antimatter Plugin, Plugin Application object
 * description: An object to handle a plugin, which is actually an application
 * 				which represents a website, the application can have extra configuration
 * 				options which a normal plugin doesn't have.  To setup the "website".
 * 
 * version: 1.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Plugin_Application extends Amslib_Plugin
{
	static protected $version;
	static protected $registeredLanguages = array();

	protected function readValue($query,$default=NULL)
	{
		$node = $this->xpath->query($query);

		return ($node && $node->length) ? $node->item(0)->nodeValue : $default;
	}
	
	protected function readSingleNode($query,$default=NULL)
	{
		$node = $this->xpath->query($query);
		
		return ($node && $node->length) ? $node->item(0) : $default;
	}

	protected function findResource($plugin,$node)
	{
		$node->nodeValue = Amslib_Plugin::expandPath($node->nodeValue);

		return parent::findResource($plugin,$node);
	}

	protected function readVersion()
	{
		self::$version = array(
			"date"		=>	$this->readValue("//package/version/date"),
			"number"	=>	$this->readValue("//package/version/number"),
			"name"		=>	$this->readValue("//package/version/name"),
		);
	}

	protected function readPaths()
	{
		$path = $this->readSingleNode("//package/path");
		if($path)
		{
			foreach($path->childNodes as $p)
			{
				if($p->nodeType != 1) continue;
				
				$name	=	$p->nodeName;
				$value	=	Amslib_Plugin::expandPath($p->nodeValue);
	
				if($name == "include"){
					Amslib::addIncludePath(Amslib_File::absolute($value));
				}else{
					Amslib_Plugin::setPath($name,$value);
					
					switch($name){
						case "plugin":{
							Amslib_Plugin_Manager::addLocation($value);
						}break;
						
						case "docroot":{
							Amslib_File::documentRoot($value);						
						}break;
					};
				}
			}
	
			//	NOTE: What should I do if a path is missing? this could cause a crash
		}
	}

	protected function initialiseModel()
	{
		parent::initialiseModel();

		if($this->model){
			Amslib_Database::setSharedConnection($this->model);
		}
	}

	protected function initialisePlugin()
	{
		//	Set the version of the admin this panel is running
		$this->readVersion();
		//	Set all the important paths for the admin to run
		$this->readPaths();
		//	Load the router (need to initialise the router first, but execute it after everything is loaded from the plugins)
		$this->initRouter();

		return true;
	}

	protected function finalisePlugin()
	{
		//	Load all the customised configuration into the plugins
		$this->configurePlugins();

		//	Load the required router library and execute it to setup everything it needs
		$this->executeRouter();
		
		//	We need a valid language for the website, make sure it's valid
		$langCode = self::getLanguage("website");
		if(!$langCode) self::setLanguage("website",reset(self::getLanguageList("website")));

		return true;
	}

	protected function initRouter()
	{
		//	TODO: need to get rid of the need to do this constructor
		//	TODO: Why are we hardcoding the type of source to XML? what if we chose a DB source instead?
		$r = Amslib_Router3::getInstance();
		Amslib_Router3::setSource(Amslib_Router3::getObject("source:xml"));
	}

	//	NOTE:	It might be worth noting that in the future, this functionality might be useless
	//			because we don't load any routes from the amslib_router.xml in the administration panel
	//			any way, so this functionality is practically worthless.
	protected function executeRouter()
	{
		$source = $this->readValue("//package/router_source");
		$source = Amslib_Plugin::expandPath($source);

		//	Initialise and execute the router
		//	TODO: As noted in initRouter, why are we hardcoding an XML source?
		$xml = Amslib_Router3::getObject("source:xml");
		$xml->load($source);

		Amslib_Router3::setSource($xml);
		Amslib_Router3::execute();
	}

	public function __construct($name,$location)
	{
		parent::__construct();
		
		Amslib_Plugin::setPath("amslib",	Amslib::locate());
		Amslib_Plugin::setPath("website",	"__WEBSITE__");
		Amslib_Plugin::setPath("admin",		"__ADMIN__");
		Amslib_Plugin::setPath("plugin",	"__PLUGIN__");
		Amslib_Plugin::setPath("docroot",	Amslib_File::documentRoot());
		
		$this->load($name,$location);
		Amslib_Plugin_Manager::import($name,$this);
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	/**
	 * method: runPackage
	 *
	 * Run all the sequence of events that need to happen for the plugin to be opened correctly
	 *
	 * NOTE:	we overload this method with a customised version
	 * 			because the application plugin needs the model initialised
	 * 			before any plugins load
	 *
	 * NOTE:	plugins need that their dependencies are loaded BEFORE they
	 * 			load their stuff, for example, if models are inherited from
	 * 			one another, the plugin will break because it's model is
	 * 			initialised before it's dependency is made available.
	 */
	protected function runPackage()
	{
		$this->initialisePlugin();
		$this->initialiseModel();
		$this->loadDependencies();
		$this->loadRouter();
		$this->loadConfiguration();
		$this->finalisePlugin();
	}
	
	public function setModel($model)
	{
		Amslib_Database::setSharedConnection($model);

		parent::setModel($model);
	}

	static public function getVersion($element=NULL)
	{
		return (!isset(self::$version[$element])) ? self::$version : self::$version[$element];
	}
	
	static public function setLanguage($name,$langCode)
	{
		Amslib::insertSessionParam("language_code_{$name}",$langCode);
	}
	
	static public function getLanguage($name)
	{
		return Amslib::sessionParam("language_code_{$name}");
	}
	
	static public function registerLanguage($name,$langCode)
	{
		self::$registeredLanguages[$name][$langCode] = true;
	}
	
	static public function getLanguageList($name)
	{
		return isset(self::$registeredLanguages[$name]) 
					? array_keys(self::$registeredLanguages[$name]) 
					: array();
	}

	//	NOTE:	This method looks like it's out of date and needs
	//			to be revamped with a new way to obtain this info
	//			because it looks a little bit hacky
	//	NOTE:	Stop using this method, it's going to get deleted soon
	public function getPageTitle()
	{
		$api = Amslib_Plugin_Manager::getAPI(self::getActivePlugin());

		$title = false;

		if($api)	$title	=	$api->getValue("page_title");
		if(!$title)	$title	=	"MISSING PAGE TITLE";

		return $title;
	}

	//	NOTE: I don't like this method, I should find a nicer way to do this
	static public function getActivePlugin()
	{
		static $activePlugin = NULL;

		if($activePlugin === NULL){
			$routeName		=	Amslib_Router3::getName();
			$activePlugin	=	Amslib_Plugin_Manager::getPluginNameByRouteName($routeName);
		}

		return $activePlugin;
	}
	
	protected function runService()
	{
		$parameters = Amslib_Router3::getParameter();

		if(isset($parameters["plugin"]) && isset($parameters["service"])){
			$api = Amslib_Plugin_Manager::getAPI($parameters["plugin"]);
			if($api){
				//	NOTE:	this could be better if there was an api method to call
				//			because that would mean it'll get the parameters 
				//			directly + specifically for it's needs
				
				//	Call the service script to setup any static parameters required
				Amslib::requireFile(Amslib_Website::abs("/plugins/service.php"));
				
				$api->callService($parameters["service"]);
			}
			
			//	TODO: we have to implement a way to redirect away from this script after we're done, right?
			//	TODO: we need to redirect away if we posted here, if it's ajax, it doesnt matter
		}else{
			//	TODO: we are being a bit hasty in assuming that "home" route even exists?
			//	NOTE: yes we are, but right now we have no alternative than assume it exists for now
			Amslib_Website::redirect("home");
		}
		
		die();
	}
	
	/**
	 * method: render
	 * 
	 * Render the application, or process a web service, depending on the resource
	 * 
	 * NOTE:
	 * 	-	by standard the "resource" === "Service" means a webservice
	 * 	-	override this default behaviour by overriding this method with a customised version
	 */
	public function render()
	{
		if(Amslib_Router3::getResource() === "Service") $this->runService();	
		
		//	Request the website render itself now
		print($this->api->render());
	}
}