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
 * title: Antimatter Plugin, Plugin Application object version 3
 * description: An object to handle a plugin, which is actually an application
 * 				which represents a website, the application can have extra configuration
 * 				options which a normal plugin doesn't have.  To setup the "website".
 *
 * version: 3.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Plugin_Application extends Amslib_Plugin
{
	static protected $version;
	static protected $langKey = "amslib/lang/shared";
	static protected $registeredLanguages = array();
	static protected $packageName = array();

	protected $completionCallback;

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

	protected function initialisePlugin(){}

	protected function finalisePlugin()
	{
		//	Set the version of the admin this panel is running
		$this->readVersion();

		//	Load the required router library and execute it to setup everything it needs
		$this->executeRouter();

		//	We need a valid language for the website, make sure it's valid
		$langCode = self::getLanguage("website");
		if(!$langCode) self::setLanguage("website",current(self::getLanguageList("website")));

		//	We need a default/valid language for the content, make sure it's valid
		//	FIXME: we probably need to find a way to automatically do this
		//	NOTE: this is a bit shit tbh, so we definitely need to change this
		$langCode = self::getLanguage("content");
		if(!$langCode) self::setLanguage("content",current(self::getLanguageList("content")));

		$this->autoloadResources();

		return true;
	}

	protected function autoloadResources()
	{
		$plugins = Amslib_Plugin_Manager::listPlugins();
		foreach($plugins as $name) Amslib_Plugin_Manager::getAPI($name)->autoloadResources();

		$default	=	Amslib_Router::getRouteParam("plugin");
		$route		=	Amslib_Router::getRoute();

		//	hack into place the adding or removing of all the stylesheets and javascripts
		foreach(Amslib_Array::valid(Amslib_Router::getJavascript()) as $j){
			$plugin = isset($j["plugin"]) ? str_replace("__CURRENT_PLUGIN__",$route["group"],$j["plugin"]) : $default;
			//	datatables does't load because it's trying with the wrong plugin
			$plugin = Amslib_Plugin_Manager::getAPI($plugin);

			if($plugin){
				if(isset($j["remove"])) Amslib_Resource::removeJavascript($j["value"]);
				else $plugin->addJavascript($j["value"]);
			}
		}

		foreach(Amslib_Array::valid(Amslib_Router::getStylesheet()) as $c){
			$plugin = Amslib_Plugin_Manager::getAPI(isset($c["plugin"])
				? str_replace("__CURRENT_PLUGIN__",$route["group"],$c["plugin"])
				: $default);

			if($plugin){
				if(isset($c["remove"])) Amslib_Resource::removeJavascript($c["value"]);
				else $plugin->addStylesheet($c["value"]);
			}
		}
	}

	//	NOTE:	It might be worth noting that in the future, this functionality might be useless
	//			because we don't load any routes from the amslib_router.xml in the administration panel
	//			any way, so this functionality is practically worthless.
	protected function executeRouter()
	{
		//	Initialise and execute the router
		//	FIXME: allow the use of a database source for routes and not just XML
		//	FIXME: we already load this in the Amslib_Plugin level, why are we doing it twice??
		//	NOTE: also we upgraded the router system, so probably this is dead code as well
		$source = !isset($this->config["router_source"])
			? $this->filename
			: str_replace("__SELF__",$this->filename,$this->config["router_source"]);

		Amslib_Router::load($source,"xml",$this->getName());
		Amslib_Router::finalise();
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

		$this->completionCallback = array();

		//	Preload the plugin manager with the application object
		Amslib_Plugin_Manager::preload($name,$this);

		//	Set the base locations to load plugins from
		Amslib_Plugin_Manager::addLocation($location);
		Amslib_Plugin_Manager::addLocation($location."/plugins");

		//	We can't use Amslib_Plugin_Manager for this, because it's an application plugin
		$this->config($name,$location);
		//	We need to set this before any plugins are touched, because other plugins will depend on it's knowledge
		//	NOTE: It sounds like I'm setting up a system of "priming" certain values which are important, this might need expanding in the future
		//	NOTE: I really hate the language setup, I think it's old and clunky, should think about replacing it
		$this->setLanguageKey();
		//	Now continue loading the plugin like normal
		$this->transfer();
		$this->load();

		$this->runCompletionCallbacks();
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function addCompletionCallback($function,$object=NULL)
	{
		$this->completionCallback[] = $object
			? array($object,$function)
			: $function;
	}

	public function runCompletionCallbacks()
	{
		foreach(Amslib_Array::valid($this->completionCallback) as $cb){
			call_user_func($cb);
		}
	}

	static public function setPackageFilename($domain,$file)
	{
		self::$packageName[$domain] = $file;
	}

	static public function getVersion($element=NULL)
	{
		return (!isset(self::$version[$element])) ? self::$version : self::$version[$element];
	}

	public function setLanguageKey()
	{
		$k = current(Amslib_Array::filter(Amslib_Array::valid($this->config["value"]),"name","lang_key",true));
		if(!empty($k)) self::$langKey = $k["value"];
	}

	static public function setLanguage($name,$langCode)
	{
		Amslib::insertSessionParam(Amslib_File::reduceSlashes(self::$langKey."/$name"),$langCode);
	}

	static public function getLanguage($name)
	{
		return Amslib::sessionParam(Amslib_File::reduceSlashes(self::$langKey."/$name"));
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

	public function runService()
	{
		$route = Amslib_Router::getRoute();

		if(!$route) die(__FILE__.": route is invalid");

		//	check all the route data is valid before using it
		if(!isset($route["group"]) || !strlen($route["group"])) die(__FILE__.": route/group is invalid");
		if(!isset($route["name"]) || !strlen($route["name"])) die(__FILE__.": route/name is invalid");
		if(!isset($route["handler"]) || !is_array($route["handler"]) || empty($route["handler"])) die(__FILE__.": route/handler is invalid or empty");

		$this->api->setupService($route["group"],$route["name"]);

		$service = new Amslib_Plugin_Service();
		foreach(Amslib_Array::valid($route["handler"]) as $h){
			$plugin	=	isset($h["plugin"]) ? $h["plugin"] : $route["group"];
			$api	=	Amslib_Plugin_Manager::getAPI($plugin);
			$object	=	isset($h["object"]) ? $api->getObject($h["object"],true) : $api;
			$method	=	isset($h["method"]) ? $h["method"] : "missingServiceMethod";

			$service->setHandler($plugin,$object,$method);
		}

		$service->execute();
	}

	/**
	 * method: execute
	 *
	 * execute the application, or process a web service, depending on the type of the route
	 *
	 * NOTE:
	 * 	-	if the route has type='service' we are going to process a webservice
	 * 	-	override this default behaviour by overriding this method with a customised version
	 */
	public function execute()
	{
		//	If the url executed belonds to a web service, run the service code
		if(Amslib_Router::isService()) $this->runService();

		//	If the url executed belongs to a page, render the default view of the application
		print($this->api->render("default"));
	}
}
