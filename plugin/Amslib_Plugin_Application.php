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
		if(!$langCode) self::setLanguage("website",current(self::getLanguageList("website")));

		return true;
	}

	protected function initRouter()
	{
		//	TODO:	need to get rid of the need to do this constructor
		//	FIXME:	initialising the router object like this is fucking ugly....
		//	TODO:	Why are we hardcoding the type of source to XML? what if we chose a DB source instead?
		//	FIXME:	we have to coordinate code with executeRouter in order to make sure it still works
		//			perhaps this should be done in one place only?
		$r = Amslib_Router::getInstance();
		Amslib_Router::setSource(Amslib_Router::getObject("source:xml"));
	}

	//	NOTE:	It might be worth noting that in the future, this functionality might be useless
	//			because we don't load any routes from the amslib_router.xml in the administration panel
	//			any way, so this functionality is practically worthless.
	protected function executeRouter()
	{
		//	Initialise and execute the router
		//	FIXME: allow the use of a database source for routes and not just XML
		//	FIXME: we already load this in the Amslib_Plugin level, why are we doing it twice??
		$source = !isset($this->config["router_source"])
			? $this->filename
			: str_replace("__SELF__",$this->filename,$this->config["router_source"]);

		$xml = Amslib_Router::getObject("source:xml");
		$xml->load($source);

		Amslib_Router::setSource($xml);
		Amslib_Router::execute();
		
		//	hack into place the automatic adding of all the stylesheets and javascripts
		$p = Amslib_Router::getParameter("plugin",false);
		if($p){
			$s = Amslib_Router::getStylesheets();
			$j = Amslib_Router::getJavascripts();
			$p = Amslib_Plugin_Manager::getAPI($p);
			
			if($p){
				foreach(Amslib_Array::valid($s) as $css) $p->addStylesheet($css);
				foreach(Amslib_Array::valid($j) as $js) $p->addJavascript($js);
			}
		}
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
		$this->setLanguageKey();
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
		Amslib::insertSessionParam(self::$langKey."/$name",$langCode);
	}

	static public function getLanguage($name)
	{
		return Amslib::sessionParam(self::$langKey."/$name");
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

	protected function runService()
	{
		$plugin		=	Amslib_Router::getParameter("plugin",false);
		$handler	=	Amslib_Router::getParameter("service",false);

		if($plugin && $handler){
			$api = Amslib_Plugin_Manager::getAPI($plugin);

			if($api){
				//	This method is called to setup any special data we might need
				$this->api->setupService($plugin,$handler);

				$service = new Amslib_Plugin_Service();
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
		if(Amslib_Router::getResource() === "Service") $this->runService();

		//	Request the website render itself now
		print($this->api->render());
	}
}