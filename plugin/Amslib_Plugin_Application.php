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
	protected $path;
	protected $translator;

	protected function expandTemplates($string)
	{
		$string =	str_replace("__WEBSITE__",	$this->path["website"],	$string);
		$string =	str_replace("__ADMIN__",	$this->path["admin"],	$string);
		$string	=	str_replace("__AMSLIB__",	$this->path["amslib"],	$string);
		$string =	str_replace("__DOCROOT__",	$this->path["docroot"],	$string);

		return str_replace("//","/",$string);
	}

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
		$node->nodeValue = $this->expandTemplates($node->nodeValue);

		return parent::findResource($plugin,$node);
	}

	protected function setVersion()
	{
		self::$version = array(
			"date"		=>	$this->readValue("//package/version/date"),
			"number"	=>	$this->readValue("//package/version/number"),
			"name"		=>	$this->readValue("//package/version/name"),
		);
	}

	protected function setPaths()
	{
		$path = $this->readSingleNode("//package/path");
		if($path)
		{
			foreach($path->childNodes as $p)
			{
				$name	=	$p->nodeName;
				$value	=	$this->expandTemplates($p->nodeValue);
	
				//	Ignore this type of node
				if($name[0] == "#") continue;
	
				if($name == "include"){
					Amslib::addIncludePath(Amslib_Filesystem::absolute($value));
				}else{
					$this->path[$name] = $value;
					
					if($name == "plugin"){
						Amslib_Plugin_Manager::addLocation(Amslib_Filesystem::absolute($this->path["plugin"]));
					}
				}
			}
	
			//	Die with an error, all of these parameters must be a valid string
			//	or the whole system doesnt work
			//	NOTE:	Is this true? will the whole system fail without this info?
			//	NOTE:	Perhaps I should gracefully fail with a nice error instead of dying horribly
			//	NOTE:	I think if the system is the admin yes, but if it is the normal website no
			if(	strlen($this->path["website"] != "__WEBSITE__") == 0 ||
				strlen($this->path["admin"] != "__ADMIN__") == 0 ||
				strlen($this->path["plugin"] != "__PLUGIN__") == 0){
					print("<pre>");
					var_dump($this->path);
					print("</pre>");
				}
		}
	}

	protected function loadTranslators()
	{
		$translators = $this->xpath->query("//package/translator");
		
		foreach($translators as $t){
			if($t->childNodes->length){
				$data = array();
				
				foreach($t->childNodes as $node){
					if($node->nodeType == 3) continue;

					if($node->nodeName == "language"){
						$data[$node->nodeName][] = $node->nodeValue;
					}else{
						$data[$node->nodeName] = $node->nodeValue;	
					}
				}
				
				$this->translator[$data["name"]] = new Amslib_Translator2($data["type"]);
				$this->translator[$data["name"]]->addLanguage($data["language"]);
				$this->translator[$data["name"]]->setLanguage($this->getLanguage($data["name"]));
				$this->translator[$data["name"]]->load($data["location"]);
			}
		}
	}

	protected function initialiseModel()
	{
		parent::initialiseModel();
		
		if(class_exists("Admin_Panel_Model",true)){
			//	NOTE: You can't use the method getModel() here, because the api object doesnt exist yet
			Admin_Panel_Model::setConnection($this->model);
		}
	}

	protected function initialisePlugin()
	{
		//	Set the version of the admin this panel is running
		$this->setVersion();
		//	Set all the important paths for the admin to run
		$this->setPaths();
		//	Load the content translators, which will provide all the page text translations
		$this->loadTranslators();
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
		$source = $this->expandTemplates($source);

		//	Initialise and execute the router
		//	TODO: As noted in initRouter, why are we hardcoding an XML source?
		$xml = Amslib_Router3::getObject("source:xml");
		$xml->load($source);

		Amslib_Router3::setSource($xml);
		Amslib_Router3::execute();
	}

	//	TODO:	This method, combined with loadValues from 
	//			Amslib_Plugin are in need for some SERIOUS refactoring
	protected function configurePlugins()
	{
		$config = $this->xpath->query("//package/plugin");

		foreach($config as $block){
			$name = $block->getAttribute("name");

			if($name){
				$api = Amslib_Plugin_Manager::getAPI($name);

				if($api){
					if(!empty($block->childNodes)) foreach($block->childNodes as $item){
						if($item->nodeName == "plugin_override"){
							if(!empty($item->childNodes)) foreach($item->childNodes as $override){
								if($override->nodeType == 3) continue;
								$name = $override->getAttribute("name");
								list($p,$v) = explode("/",$override->nodeValue);
								
								if($p) $p = Amslib_Plugin_Manager::getAPI($p);
								
								if($name && $p && $v){
									switch($override->nodeName){
										case "layout":{			$p->setLayout($name,$v);		}break;
										case "view":{			$p->setView($name,$v);			}break;
										case "service":{		$p->setService($name,$v);		}break;
										case "object":{			$p->setObject($name,$v);		}break;
										case "stylesheet":{		$p->setStylesheet($name,$v);	}break;
										case "javascript":{		$p->setJavascript($name,$v);	}break;
									}
								}
							}
						}else{
							$api->setValue($item->nodeName,$item->nodeValue);	
						}
					}
				}
			}
		}
	}

	public function __construct($name,$location)
	{
		parent::__construct();

		$this->path = array(
			"amslib"	=>	Amslib::locate(),
			"website"	=>	"__WEBSITE__",
			"admin"		=>	"__ADMIN__",
			"plugin"	=>	"__PLUGIN__",
			"docroot"	=>	Amslib_Filesystem::documentRoot()
		);
		
		$api = $this->load($name,$location);
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
		Admin_Panel_Model::setConnection($model);

		parent::setModel($model);
	}

	static public function getVersion($element=NULL)
	{
		return (!isset(self::$version[$element])) ? self::$version : self::$version[$element];
	}

	public function getTranslator($name)
	{
		return isset($this->translator[$name]) ? $this->translator[$name] : false;
	}
	
	public function setLanguage($name,$langCode)
	{
		Amslib::insertSessionParam(get_class($this)."_".$name,$langCode);
	}
	
	public function getLanguage($name)
	{
		return Amslib::sessionParam(get_class($this)."_".$name);
	}

	//	NOTE:	This method looks like it's out of date and needs
	//			to be revamped with a new way to obtain this info
	//			because it looks a little bit hacky
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
	
	public function render()
	{
		//	Request the website render itself now
		print($this->api->render());
	}
}