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
 * file: Amslib_Plugin_Application2.php
 * title: Antimatter Plugin, Plugin Application object version 2
 * description: An object to handle a plugin, which is actually an application
 * 				which represents a website, the application can have extra configuration
 * 				options which a normal plugin doesn't have.  To setup the "website".
 * 
 * version: 2.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Plugin_Application2 extends Amslib_Plugin2
{
	static protected $version;
	static protected $registeredLanguages = array();
	static protected $packageName = array();
		
	protected function getPackageFilename()
	{
		foreach(Amslib_Array::valid(self::$packageName) as $host=>$file){
			if(strpos($_SERVER["HTTP_HOST"],$host) !== false) return "{$this->location}/$file";
		}
		
		return parent::getPackageFilename();
	}

	protected function readVersion()
	{
		if(isset($this->config["version"])){
			self::$version = array(
				"date"		=>	$this->config["version"]["date"],
				"number"	=>	$this->config["version"]["number"],
				"name"		=>	$this->config["version"]["name"]
			);
		}
	}

	protected function initialisePlugin()
	{
		//	Load the router (need to initialise the router first, but execute it after everything is loaded from the plugins)
		//	NOTE: This is done because of webservices right? perhaps there is a better way of doing this
		$this->initRouter();

		return true;
	}

	protected function finalisePlugin()
	{
		//	Set the version of the admin this panel is running
		$this->readVersion();
		
		//	Load the required router library and execute it to setup everything it needs
		$this->executeRouter();
		
		//	We need a valid language for the website, make sure it's valid
		$langCode = self::getLanguage("website");
		if(!$langCode) self::setLanguage("website",reset(self::getLanguageList("website")));

		return true;
	}

	protected function initRouter()
	{
		//	TODO:	need to get rid of the need to do this constructor
		//	FIXME:	initialising the router object like this is fucking ugly....
		//	TODO:	Why are we hardcoding the type of source to XML? what if we chose a DB source instead?
		//	FIXME:	we have to coordinate code with executeRouter in order to make sure it still works
		//			perhaps this should be done in one place only?
		$r = Amslib_Router3::getInstance();
		Amslib_Router3::setSource(Amslib_Router3::getObject("source:xml"));
	}

	//	NOTE:	It might be worth noting that in the future, this functionality might be useless
	//			because we don't load any routes from the amslib_router.xml in the administration panel
	//			any way, so this functionality is practically worthless.
	protected function executeRouter()
	{
		//	Initialise and execute the router
		//	FIXME: allow the use of a database source for routes and not just XML
		//	FIXME: we already load this in the Amslib_Plugin level, why are we doing it twice??
		$source = str_replace("__SELF__",$this->filename,$this->config["router_source"]);
		
		$xml = Amslib_Router3::getObject("source:xml");
		$xml->load($source);

		Amslib_Router3::setSource($xml);
		Amslib_Router3::execute();
	}

	public function __construct($name,$location)
	{
		parent::__construct();
		
		parent::setPath("amslib",	Amslib::locate());
		parent::setPath("website",	"__WEBSITE__");
		parent::setPath("admin",	"__ADMIN__");
		parent::setPath("plugin",	"__PLUGIN__");
		parent::setPath("docroot",	Amslib_File::documentRoot());
		
		$this->search = array_merge(array("path","router_source","version"),$this->search);
		
		//	Preload the plugin manager with the application object
		Amslib_Plugin_Manager2::preload($name,$this);
		
		//	Set the base locations to load plugins from
		Amslib_Plugin_Manager2::addLocation($location);
		Amslib_Plugin_Manager2::addLocation($location."/plugins");
		
		//	We can't use Amslib_Plugin_Manager for this, because it's an application plugin
		$this->config($name,$location);
		$this->transfer();
		$this->load();
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
	
	public function setPackageFilename($domain,$file)
	{
		self::$packageName[$domain] = $file;
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
	//	NOTE:	Does anybody use this anymore?
	//	NOTE:	perhaps the loaded plugin should import into the application
	//			plugin the page title to use, but wouldn't work with our current method of loading all plugins
	//	NOTE:	This would require a change in how the administration panel uses plugins
	//			because right now they are all loaded and then we do a "getActivePlugin"
	//			to obtain the correct plugin, perhaps we should not load all plugins, but only
	//			the current one based on the route, then it would be simpler
	//	NOTE:	This would require us to allow a plugin to be queried, but not loaded, perhaps
	//			we can add a new method alongside load() called config() and it'll only load the config value data
	public function getPageTitle()
	{
		$api = Amslib_Plugin_Manager2::getAPI(self::getActivePlugin());

		$title = false;

		if($api)	$title	=	$api->getValue("page_title");
		if(!$title)	$title	=	"MISSING PAGE TITLE";

		return $title;
	}

	//	NOTE:	I don't like this method, I should find a nicer way to do this
	//	NOTE:	if we didnt load all the plugins, we wouldn't need it, but we need to load all plugins
	//			to get their configuration data for the header section, the url's, etc, etc
	static public function getActivePlugin()
	{
		static $activePlugin = NULL;

		if($activePlugin === NULL){
			$routeName		=	Amslib_Router3::getName();
			$activePlugin	=	Amslib_Plugin_Manager2::getPluginNameByRouteName($routeName);
		}
		
		return $activePlugin;
	}
	
	protected function runService()
	{
		$plugin		=	Amslib_Router3::getParameter("plugin",false);
		$service	=	Amslib_Router3::getParameter("service",false);
		
		if($plugin && $service){
			$api = Amslib_Plugin_Manager2::getAPI($plugin);
			
			if($api){
				//	NOTE:	this could be better if there was an api method to call
				//			because that would mean it'll get the parameters 
				//			directly + specifically for it's needs	
				
				//	Call the service script to setup any static parameters required
				Amslib::requireFile(Amslib_Website::abs("/plugins/service.php"));
				
				//	TODO:	we should upgrade the service code so it calls a method, not includes some arbitary file
				//			this way it would probably be a lot more flexible since we stay inside the api system and not
				//			in some could-be-anything script that runs anywhere and I dont know where...
				$api->callService($service);
			}
			
			//	TODO: we have to implement a way to redirect away from this script after we're done, right?
			//	TODO: we need to redirect away if we posted here, if it's ajax, it doesnt matter
		}else{
			//	TODO: we are being a bit hasty in assuming that "home" route even exists?
			//	NOTE: yes we are, but right now we have no alternative than assume it exists for now
			Amslib_Website::redirect("home");
		}
		
		//	Protect against any code branch which doesn't complete and block (all code paths should block)
		die("FAILURE TO COMPLETE SERVICE CORRECTLY");
	}
	
	protected function runService2()
	{
		$plugin		=	Amslib_Router3::getParameter("plugin",false);
		$handler	=	Amslib_Router3::getParameter("service",false);
		
		if($plugin && $handler){
			$api = Amslib_Plugin_Manager2::getAPI($plugin);
			
			if($api){
				//	This method is called to setup any special data we might need
				$this->api->setupService($plugin,$handler);

				$service = new Amslib_Plugin_Service3();
				$service->execute($api,$handler);
			}
		}
		
		//	NOTE: if you arrive here, it's because the service didn't execute, all services terminate with die()
		//	TODO: we are being a bit hasty in assuming that "home" route even exists?
		//	NOTE: yes we are, but right now we have no alternative than assume it exists for now
		Amslib_Website::redirect("home",true);
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
		$resource = Amslib_Router3::getResource();
		if($resource === "Service")		$this->runService();
		if($resource === "Service2")	$this->runService2();	
		
		//	Request the website render itself now
		print($this->api->render());
	}
}