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
	//	NOTE: Not sure that this variable is useful anymore
	static protected $version;
	//	NOTE: I think these two variables are assuming that a website needs or has language support
	static protected $langKey = "/amslib/lang/shared";
	static protected $registeredLanguages = array();

	protected $completionCallback;

	/**
	 * 	method:	initialisePlugin
	 *
	 * 	todo: write documentation
	 */
	protected function initialisePlugin()
	{
		/* do nothing by default */
	}

	/**
	 * 	method:	finalisePlugin
	 *
	 * 	todo: write documentation
	 */
	protected function finalisePlugin()
	{
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
		foreach(Amslib_Plugin_Manager::listPlugin() as $plugin){
			$api = Amslib_Plugin_Manager::getAPI($plugin);

			if($api){
				$translators = $api->listTranslators(false);

				foreach(Amslib_Array::valid($translators) as $name=>$object){
					//	Obtain the language the system should use when printing text
					$object->setLanguage(Amslib_Plugin_Application::getLanguage($name));
					$object->load();
				}

				//	Now inform all the plugins the application has loaded
				$callback = array($api,"finaliseApplication");
				if(method_exists($callback[0],$callback[1])){
					call_user_func($callback);
				}
			}else{
				Amslib_Debug::log("plugin list",Amslib_Plugin_Manager::listPlugin());
				Amslib_Debug::log("plugin for translator not found?",$plugin);
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
		foreach(Amslib_Plugin_Manager::listPlugin() as $name){
			$p = Amslib_Plugin_Manager::getAPI($name);
			if($p){
				$p->autoloadResources();
			}else{
				Amslib_Debug::log("plugin not found?",$p);
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
		//	Finalise the router
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
	public function __construct($name,$location,$config=NULL)
	{
		parent::__construct();

		//	unless I think of a reason to not do this, always initialise sessions
		@session_start();

		//	This is needed so non-routed-services will work without modification
		//	NOTE: probably I shouldn't need to do this, I need to find a way to make this redundant
		Amslib_Router::initialise();
		//	NOTE: I think that this method is redundant and the system should do it for me
		//	NOTE: I'm not sure whether this method is actually useful anymore, I think it's out of date maybe
		Amslib_Website::set();

		$base = Amslib_Router::getBase();

		Amslib_Website::setPath("amslib",		Amslib::locate());
		Amslib_Website::setPath("website",		$base);
		Amslib_Website::setPath("website_ext",	Amslib_Router_URL::externalURL($base));
		Amslib_Website::setPath("admin",		"__ADMIN__");
		Amslib_Website::setPath("plugin",		"__PLUGIN__");
		Amslib_Website::setPath("docroot",		Amslib_File::documentRoot());

		$this->completionCallback = array();

		//	Set the name, location and config of the plugin that was found in the hard disk
		$this->setName($name);
		$this->setLocation($location);
		$this->setConfigSource($config);
	}

	public function initialise()
	{
		//	Quickly insert this plugin object before we load anything else
		//	We do this so other plugins that might call the application can access this plugins
		//	configuration before the entire tree is loaded, because in a tree algorithm, all the
		//	other plugins are loaded into the system before this one, which causes a problem because
		//	of course if you need to talk to or through the application object, if you have not fully
		//	explored the tree, it won't exist, this obviously causes a lot of problems, so we need to
		//	"insert" a incomplete plugin here, so at least the plugin configurtion is available even
		//	if the actual API is not, when configuring the plugin tree, we 100% of the time always deal
		//	with the plugin object, not the API which is created after the plugin is fully loaded
		Amslib_Plugin_Manager::insert($this->getName(),$this);

		//	Set the base locations to load plugins from
		//	NOTE: this obviously means it's not configurable, since I'm hardcoding the path for this here
		Amslib_Plugin_Manager::addLocation($this->getLocation());
		Amslib_Plugin_Manager::addLocation($this->getLocation()."/plugins");

		//	We can't use Amslib_Plugin_Manager for this, because it's an application plugin
		$this->config($this->getName());
		//	Process all the imports and exports so all the plugins contain the correct data
		Amslib_Plugin_Manager::processImport();
		Amslib_Plugin_Manager::processExport();
		//	Now we have to load all the plugins into the system
		$this->load();

		//	NOTE:	perhaps we can add a default callback for running the method Amslib_Plugin_Manager::processTransfers
		//	NOTE:	after all the plugins are loaded, do we need ot keep the plugin objects in memory, perhaps we should
		//			dump them all once all the API objects are created
		//	NOTE:	perhaps we register a completion callback to manually take care of this, it sounds like a useful way
		//			to save some memory, all those xml objects must take up space and especially the bulky confguration arrays
		//	NOTE:	we currently are deleting it inside the plugin code, perhaps this is not elegant

		$this->runCompletionCallbacks();
	}

	public function setDebug($state)
	{
		Amslib_Debug::enable($state);
	}

	public function setShutdown($url)
	{
		Amslib::shutdown($url);
	}

	/**
	 * 	method:	setConfigSource
	 *
	 * 	Sets the configuration source and initialises all the selectors to import this source
	 *
	 * 	parameters:
	 * 		$config	-	The configuration source to use
	 *
	 * 	notes:
	 * 		-	Thanks alfonso (05/05/2014) for realising I should have $config=NULL to match the parent method :( oops
	 */
	public function setConfigSource($config=NULL)
	{
		parent::setConfigSource($config);

		$callback = array(get_class($this->source),"initialiseSelectors");

		if(is_callable($callback)){
			//	Initialise all the selectors
			call_user_func($callback);

			return true;
		}

		Amslib_Debug::log(__METHOD__,"initialiseSelectors not available",$callback);

		return false;
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
		//	TODO: make sure this is a callable function before you add it, is_callable() ? perhaps ?
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
			//	TODO: we don't guard against possible faulty callbacks
			call_user_func($cb);
		}
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
		$k = current(Amslib_Array::filter(Amslib_Array::valid($this->data["value"]),"name","lang_key",true));

		//	key wasn't found, do nothing
		if(empty($k)) return;

		//	key was found, attempt to upgrade old keys to new keys
		$old = self::$langKey;
		self::$langKey = $k["value"];

		//	loop through session, find matching keys against the old key, replace with the new keys
		foreach($_SESSION as $key=>$value) if(strpos($key,$old) !== false){
			unset($_SESSION[$key]);

			$key = str_replace($old,self::$langKey,$key);
			Amslib_SESSION::set(Amslib_File::reduceSlashes($key),$value);
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
			Amslib_SESSION::set(Amslib_File::reduceSlashes(self::$langKey."/$name"),$langCode);
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
			$lang = Amslib_SESSION::get(Amslib_File::reduceSlashes(self::$langKey."/$name"));
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

		//	NOTE:	Perhaps dying horribly like you just drove off a cliff in your
		//				lamborghini with a martini in one hand, a hooker blowing you whilst
		//				smoking an enormous cigar made of 100 dollar bills is NOT the best
		//				way to handle failure?

		//	NOTE:	However, check all the route data is valid before using it

		if(!$route){
			Amslib_Debug::log($m=__METHOD__.": route is invalid, check error log",$route);
			die($m);
		}

		if(!isset($route["group"]) || !strlen($route["group"])){
			Amslib_Debug::log($m=__METHOD__.": route/group is invalid, check error log",$route);
			die($m);
		}

		if(!isset($route["name"]) || !strlen($route["name"])){
			Amslib_Debug::log($m=__METHOD__.": route/name is invalid, check error log",$route);
			die($m);
		}

		if(!isset($route["handler"]) || !is_array($route["handler"]) || empty($route["handler"])){
			Amslib_Debug::log($m=__METHOD__.": route/handler is invalid or empty, check error log",$route);
			die($m);
		}

		if(!isset($route["output"])){
			Amslib_Debug::log($m=__METHOD__.": route/handler is invalid, there was no output specified",$route);
			die($m);
		}

		$this->api->setupService($route["group"],$route["name"]);

		$service = Amslib_Plugin_Service::getInstance();
		$service->installHandlers($route["group"],$route["output"],$route["handler"]);
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
		//	NOTE:	this isService() call, I think is a bit hacky, I would
		//			like to do away with it and have the framework do it
		if(Amslib_Router::isService()) $this->runService();

		//	Get the current route and acquire the api object for the current route and render it
		//	NOTE:	we do this so you can render pages from other plugins or the application based
		//			on what route has been opened, sometimes you want to define webpages in separate
		//			plugins and render them just based on the url and/or route
		$route = Amslib_Router::getRoute();

		if(!$route || !isset($route["group"])){
			Amslib_Debug::log("ROUTE OR ROUTE/GROUP DOES NOT EXIST",$route);
			return;
		}

		$api = $this->getAPI($route["group"]);

		if(!$api || !method_exists($api,"render")){
			Amslib_Debug::log("API OR ITS RENDER METHOD DOES NOT EXIST",get_class($api),$route);
			return;
		}

		//	If the url executed belongs to a page, render the default view of the application
		print($api->render("default",$params));
	}
}
