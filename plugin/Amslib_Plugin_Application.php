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
 * 	class:	Amslib_Plugin_Application
 *
 *	group:	plugin
 *
 *	file:	Amslib_Plugin_Application.php
 *
 *	description:
 *		An object to handle a plugin, which is actually an application
 *		which represents a website, the application can have extra configuration
 *		options which a normal plugin doesn't have in order to setup the "website".
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Plugin_Application extends Amslib_Plugin
{
	static protected $version;
	static protected $langKey = "/amslib/lang/shared";
	static protected $registeredLanguages = array();
	static protected $packageName = array();

	protected $completionCallback;

	/**
	 * 	method:	getPackageFilename
	 *
	 * 	todo: write documentation
	 */
	protected function getPackageFilename()
	{
		foreach(Amslib_Array::valid(self::$packageName) as $host=>$file){
			if(strpos($_SERVER["HTTP_HOST"],$host) !== false) return "{$this->location}/$file";
		}

		return parent::getPackageFilename();
	}

	/**
	 * 	method:	readVersion
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	initialisePlugin
	 *
	 * 	todo: write documentation
	 */
	protected function initialisePlugin()
	{

	}

	/**
	 * 	method:	finalisePlugin
	 *
	 * 	todo: write documentation
	 */
	protected function finalisePlugin()
	{
		//	NOTE: admin panel? This is obviously code I included here by accident
		//	Set the version of the admin this panel is running
		$this->readVersion();

		//	Load the required router library and execute it to setup everything it needs
		$this->executeRouter();

		//	Load all the javascripts and stylesheets automatically set to load into the current url
		$this->autoloadResources();

		//	Call the getLanguage method for both default translators, this is just a precautionary step.  What
		//	will happen is, if no language is set, it'll default and store the first language in the approved list
		//	meaning that both will have a correct code, we don't care about the return code here, cause it's not used
		self::getLanguage("website");
		self::getLanguage("content");

		$this->setLanguageKey();

		//	Setup all the translators in other plugins with the correct languages
		foreach(Amslib_Plugin_Manager::listPlugins() as $plugin){
			$api = Amslib_Plugin_Manager::getAPI($plugin);

			if($api){
				$translators = $api->listTranslators(false);

				foreach(Amslib_Array::valid($translators) as $name=>$object){
					//	Obtain the language the system should use when printing text
					$object->setLanguage(Amslib_Plugin_Application::getLanguage($name));
					$object->load();
				}
			}else{
				Amslib::errorLog("plugin not found?",$p);
			}
		}

		return true;
	}

	/**
	 * 	method:	autoloadResources
	 *
	 * 	todo: write documentation
	 */
	protected function autoloadResources()
	{
		//	STEP 1: Autoload all resources from each plugin as they are requested
		foreach(Amslib_Plugin_Manager::listPlugins() as $name){
			$p = Amslib_Plugin_Manager::getAPI($name);
			if($p){
				$p->autoloadResources();
			}else{
				Amslib::errorLog("plugin not found?",$p);
			}
		}

		$default	=	Amslib_Router::getRouteParam("plugin");
		$route		=	Amslib_Router::getRoute();

		//	STEP 2: Autoload all resources which are bound the current route.
		//	hack into place the adding or removing of all the stylesheets and javascripts
		foreach(Amslib_Array::valid(Amslib_Router::getJavascript()) as $j){
			//	datatables does't load because it's trying with the wrong plugin
			$name	=	isset($j["plugin"]) ? str_replace("__CURRENT_PLUGIN__",$route["group"],$j["plugin"]) : $default;
			$plugin	=	Amslib_Plugin_Manager::getAPI($name);

			if($plugin){
				if(isset($j["remove"])){
					Amslib_Resource::removeJavascript($j["value"]);
				}else{
					$plugin->addJavascript($j["value"]);
				}
			}
		}

		foreach(Amslib_Array::valid(Amslib_Router::getStylesheet()) as $c){
			$name	=	isset($c["plugin"]) ? str_replace("__CURRENT_PLUGIN__",$route["group"],$c["plugin"]) : $default;
			$plugin	=	Amslib_Plugin_Manager::getAPI($name);

			if($plugin){
				if(isset($c["remove"])){
					Amslib_Resource::removeStylesheet($c["value"]);
				}else{
					$plugin->addStylesheet($c["value"]);
				}
			}
		}
	}

	//	NOTE:	It might be worth noting that in the future, this functionality might be useless
	//			because we don't load any routes from the amslib_router.xml in the administration panel
	//			any way, so this functionality is practically worthless.
	/**
	 * 	method:	executeRouter
	 *
	 * 	todo: write documentation
	 */
	protected function executeRouter()
	{
		//	Initialise and execute the router
		//	FIXME: allow the use of a database source for routes and not just XML
		//	FIXME: we already load this in the Amslib_Plugin level, why are we doing it twice??
		//	NOTE: also we upgraded the router system, so probably this is dead code as well
		$source = !isset($this->config["router_source"])
			? $this->filename
			: str_replace("__SELF__",$this->filename,$this->config["router_source"]);

		//	FIXME: we are hardcoding the source of this to the xml file, what happens when I change to a db router?
		Amslib_Router::load($source,"xml",$this->getName());
		Amslib_Router::finalise();

		//	NOTE:	we do not do it because webservice urls are typically not enabled with languages
		//			therefore if you run the code which sets the language based on the url here, you might be in spanish
		//			but then this code will default to setting english and all the language strings processed
		//			in the webservice will come out in english instead of spanish like the website url might be dictating
		if(Amslib_Router::isService() == false){
			self::setLanguage("content", Amslib_Router::getLanguage());
			self::setLanguage("website", Amslib_Router::getLanguage());
		}
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct($name,$location)
	{
		parent::__construct();

		parent::setPath("amslib",	Amslib::locate());
		parent::setPath("website",	"__WEBSITE__");
		parent::setPath("admin",	"__ADMIN__");
		parent::setPath("plugin",	"__PLUGIN__");
		parent::setPath("docroot",	Amslib_File::documentRoot());

		//	NOTE: router_source? this is a really old and now deprecated configuration node isnt it?
		$this->search = array_merge(array("path","router_source","version"),$this->search);

		$this->completionCallback = array();

		//	Preload the plugin manager with the application object
		Amslib_Plugin_Manager::preload($name,$this);

		//	Set the base locations to load plugins from
		Amslib_Plugin_Manager::addLocation($location);
		Amslib_Plugin_Manager::addLocation($location."/plugins");

		//	We can't use Amslib_Plugin_Manager for this, because it's an application plugin
		$this->config($name,$location);
		//	Now continue loading the plugin like normal
		$this->transfer();
		$this->load();

		$this->runCompletionCallbacks();
	}

	/**
	 * 	method:	getInstance
	 *
	 * 	todo: write documentation
	 */
	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	/**
	 * 	method:	addCompletionCallback
	 *
	 * 	Add a system-complete callback to the application which will execute when the system has
	 * 	finished doing everything it needs to start the system
	 *
	 * 	parameters:
	 * 		$function - the function/callback to execute
	 *
	 *	notes:
	 *		-	This is just useful sometimes because some plugins require waiting until the very
	 *			end in order to know what they should do, this allows them to do that without hacks
	 *		-	The callback is executed by using call_user_func, therefore any valid function usable with
	 *			that method is usable here
	 */
	public function addCompletionCallback($function)
	{
		$this->completionCallback[] = $function;
	}

	/**
	 * 	method:	runCompletionCallbacks
	 *
	 * 	Executes all the stored system-complete callbacks, meaning that after everything has loaded and the system
	 * 	is ready to run, it'll execute them in the sequence given when loading and those callbacks will do
	 * 	whatever they are attempting to do such as post-load configuration.
	 *
	 * 	See Amslib_Plugin_Application::addCompletionCallback for more details
	 */
	public function runCompletionCallbacks()
	{
		foreach(Amslib_Array::valid($this->completionCallback) as $cb){
			call_user_func($cb);
		}
	}

	/**
	 * 	method:	setPackageFilename
	 *
	 * 	todo: write documentation
	 */
	static public function setPackageFilename($domain,$file)
	{
		self::$packageName[$domain] = $file;
	}

	/**
	 * 	method:	getVersion
	 *
	 * 	todo: write documentation
	 */
	static public function getVersion($element=NULL)
	{
		return (!isset(self::$version[$element])) ? self::$version : self::$version[$element];
	}

	/**
	 * 	method:	setLanguageKey
	 *
	 *	Will retrieve the lang_key from the configuration and set it into the static data, ready to use when required
	 *	Then if the old key exists in the session, it will upgrade all the existing keys to the new language key, keeping
	 *	everything clean and tidy
	 */
	public function setLanguageKey()
	{
		$k = current(Amslib_Array::filter(Amslib_Array::valid($this->config["value"]),"name","lang_key",true));

		//	key wasn't found, do nothing
		if(empty($k)) return;

		//	key was found, attempt to upgrade old keys to new keys
		$old = self::$langKey;
		self::$langKey = $k["value"];

		//	loop through session, find matching keys against the old key, replace with the new keys
		foreach($_SESSION as $key=>$value) if(strpos($key,$old) !== false){
			unset($_SESSION[$key]);

			$key = str_replace($old,self::$langKey,$key);
			Amslib::setSESSION(Amslib_File::reduceSlashes($key),$value);
		}
	}

	/**
	 * 	method:	setLanguage
	 *
	 * 	Set the requested language code (4 character, e.g: en_GB, es_ES) for the specific domain
	 *
	 * 	parameters:
	 * 		$name - The name of the language domain being set, normally "content" or "website"
	 * 		$langCode - The 4 character code to set, e.g: en_GB, es_ES
	 *
	 * 	returns:
	 * 		Boolean false if setting the code was not executed properly or the 4 character code that was successfully set
	 *
	 *	notes:
	 *		-	Remember, the language system can have multiple "domains" which can be independantly
	 * 			controlled in order to mix languages within a single webpage, this might sound
	 * 			ridiculous, but imagine a website being administrated in spanish, but viewing
	 * 			the english content.
	 * 		-	The $name, specifies the language code given for a particular "domain", normally
	 * 			they are "content" or "website"
	 */
	static public function setLanguage($name,$langCode)
	{
		if(is_string($name) && strlen($name) && in_array($langCode,self::getLanguageList($name))){
			Amslib::setSESSION(Amslib_File::reduceSlashes(self::$langKey."/$name"),$langCode);
		}else{
			$langCode = false;
		}

		return $langCode;
	}

	/**
	 * 	method:	getLanguage
	 *
	 * 	Obtain the language set in the session for the specific type of language "domain" (normally website or content)
	 *
	 * 	parameters:
	 * 		$name - The name of the language to retrieve
	 *
	 * 	returns:
	 * 		Boolean false if obtaining a language has failed, or the 4 character language code, e.g: en_GB, en_ES
	 *
	 * 	notes:
	 *		-	See the notes from the method Amslib_Plugin_Application::setLanguage for what the
	 *			language code, domains terms mean.
	 * 		-	Remember also, if a language is not found, the first language in the approved list will be
	 * 			selected, stored and returned as a sensible default
	 */
	static public function getLanguage($name)
	{
		$lang = false;

		if(is_string($name) && strlen($name)){
			$lang = Amslib::getSESSION(Amslib_File::reduceSlashes(self::$langKey."/$name"));
		}

		if(!$lang) $lang = self::setLanguage($name,current(self::getLanguageList($name)));

		return $lang;
	}

	/**
	 * 	method:	registerLanguage
	 *
	 * 	todo: write documentation
	 */
	static public function registerLanguage($name,$langCode)
	{
		self::$registeredLanguages[$name][$langCode] = true;
	}

	/**
	 * 	method:	getLanguageList
	 *
	 * 	todo: write documentation
	 */
	static public function getLanguageList($name)
	{
		return isset(self::$registeredLanguages[$name])
					? array_keys(self::$registeredLanguages[$name])
					: array();
	}

	/**
	 * 	method:	runService
	 *
	 * 	todo: write documentation
	 */
	public function runService()
	{
		$route = Amslib_Router::getRoute();

		if(!$route) die(__FILE__.": route is invalid");

		//	check all the route data is valid before using it
		if(!isset($route["group"]) || !strlen($route["group"])) die(__FILE__.": route/group is invalid");
		if(!isset($route["name"]) || !strlen($route["name"])) die(__FILE__.": route/name is invalid");
		if(!isset($route["handler"]) || !is_array($route["handler"]) || empty($route["handler"])) die(__FILE__.": route/handler is invalid or empty");

		$this->api->setupService($route["group"],$route["name"]);

		$service = Amslib_Plugin_Service::getInstance();
		foreach(Amslib_Array::valid($route["handler"]) as $h){
			//	Special customisation for framework urls, which normally execute on objects regardless of plugin
			//	So we just use plugin as the key to trigger this
			//	NOTE: this means framework has become a system name and cannot be used as a name of any plugin?
			if(Amslib_Array::hasKeys($h,array("plugin","object","method")) && $h["plugin"] == "framework"){
				$plugin	=	$h["plugin"];
				$object	=	$h["object"];
				$method	=	$h["method"];
			}else{
				$plugin	=	isset($h["plugin"]) ? $h["plugin"] : $route["group"];
				$api	=	Amslib_Plugin_Manager::getAPI($plugin);
				$object	=	isset($h["object"]) ? $api->getObject($h["object"],true) : $api;
				$method	=	isset($h["method"]) ? $h["method"] : "missingServiceMethod";
			}

			$record		= true;
			$global		= false;
			$failure	= true;

			if(isset($h["record"])){
				$record = false;

				if(strpos($h["record"],"global")	!== false) 	$global = true;
				if(strpos($h["record"],"true")		!== false)	$record = true;
				if(strpos($h["record"],"record")	!==	false)	$record = true;
			}

			if(isset($h["failure"])){
				if(strpos($h["failure"],"ignore") !== false) $failure = false;
			}

			if(!isset($h["source"])) $h["source"] = "post";
			$h["source"] = strtolower($h["source"]);
			if(!in_array($h["source"],array("get","post"))) $h["source"] = "post";
			
			$service->setHandler($route["format"],$plugin,$object,$method,$h["source"],$record,$global,$failure);
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
	public function execute($params=array())
	{
		//	If the url executed belonds to a web service, run the service code
		if(Amslib_Router::isService()) $this->runService();
		
		//	Get the current route and acquire the api object for the current route and render it
		//	NOTE:	we do this so you can render pages from other plugins or the application based
		//			on what route has been opened, sometimes you want to define webpages in separate
		//			plugins and render them just based on the url and/or route
		$route = Amslib_Router::getRoute();
		$api = Amslib_Plugin_Manager::getAPI($route["group"]);

		//	If the url executed belongs to a page, render the default view of the application
		print($api->render("default",$params));
	}
}
