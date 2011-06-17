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
 * file: Amslib_Plugin.php
 * title: Antimatter Plugin: Core plugin object
 * description: An object to manage how a plugin it loaded through to
 * 				how the MVC object is created and configured
 * version: 1.4
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/
class Amslib_Plugin
{
	//	NOTE:	The paths registered in the application which are ways to replace 
	//			templated strings with their dynamically valid replacements
	static protected $path;
	
	//	The XPATH object used to query the plugin package xml
	protected $xpath;
	
	//	The name of the plugin
	protected $name;
	
	//	The location of the plugin on the filesystem
	protected $location;
	
	//	The packageXML file being processed
	protected $packageXML;
	
	//	The API object created by the plugin
	protected $api;
	
	//	The Model object for the plugin to access the database
	protected $model;
	
	//	The routes configured for this plugin
	protected $routes;
	
	//	The plugin dependencies for this plugin
	protected $dependencies;
	
	static protected function setPath($name,$path)
	{
		self::$path[$name] = $path;
	} 
	
	static public function expandPath($path)
	{
		$path	=	str_replace("__WEBSITE__",	self::$path["website"],	$path);
		$path	=	str_replace("__ADMIN__",	self::$path["admin"],	$path);
		$path	=	str_replace("__AMSLIB__",	self::$path["amslib"],	$path);
		$path	=	str_replace("__DOCROOT__",	self::$path["docroot"],	$path);

		return Amslib_File::reduceSlashes($path);
	}

	protected function createAPI()
	{
		$list = $this->xpath->query("//package/object/api");

		$api = false;

		if($list->length == 1){
			$node = $list->item(0);
			if($node){
				$object = $node->nodeValue;

				Amslib::requireFile("$this->location/objects/{$object}.php");

				$error = "FATAL ERROR(Amslib_Plugin::createAPI) Could not __ERROR__ for plugin '$this->name'<br/>";

				if(!class_exists($object)){
					//	class does not exist
					die(str_replace("__ERROR__","find class '{$object}'",$error));
				}

				if(!method_exists($object,"getInstance")){
					//	class exists, but method does not
					die(str_replace("__ERROR__","find the getInstance method in the API object '{$object}'",$error));
				}

				$api = call_user_func(array($object,"getInstance"));
			}else{
				//	ERROR: XML Node was invalid?
			}
		}else{
			//	ERROR: More than one API object is not allowed
		}

		//	An API Object was not created, so create a default Amslib_MVC3 object instead
		if($api == false) $api = new Amslib_MVC3();

		//	Setup the api with basic name and filesystem location
		$api->setLocation($this->getLocation());
		$api->setName($this->getName());
		$api->setPlugin($this);

		//	Assign the model to the plugin
		$api->setModel($this->model);

		//	Load all the routes from the router system into the mvc layout
		foreach($this->routes as $name=>$route) $api->setRoute($name,$route);

		return $api;
	}

	/**
	 * method: initialiseModel
	 * 
	 * notes:
	 * 	-	You can't use the method getModel here, because the api object doesnt exist yet
	 */
	protected function initialiseModel()
	{
		$list = $this->xpath->query("//package/object/model");

		$this->model = false;

		if($list->length == 1){
			$node = $list->item(0);

			if($node){
				$object	=	$node->nodeValue;

				//	ATTEMPT 1: Import the database model from another plugin
				if($this->model == false && $node->getAttribute("import")){
					$model = false;
					
					$api = Amslib_Plugin_Manager::getAPI($object);
					if($api)	$model = $api->getModel();
					if($model)	$this->model = $model;
				}

				//	ATTEMPT 2: Is it a plain, ordinary object that exists in the system?
				if($this->model == false){
					try{
						$this->model = call_user_func(array($object,"getInstance"));
					}catch(Exception $e){}
				}

				//	ATTEMPT 3: Is the object the model from the current plugin
				if($this->model == false){
					$file = "$this->location/objects/{$object}.php";

					if(file_exists($file)){
						Amslib::requireFile("$this->location/objects/{$object}.php");

						if(class_exists($object)){
							$this->model = call_user_func(array($object,"getInstance"));
						}
					}
				}
			}
		}
	}

	//	NOTE: why am I passing in the $plugin parameter here, then never using it??
	protected function findResource($plugin,$node)
	{
		$resource = $node->nodeValue;
		
		//	PREPARE THE STRING: expand any parameters inside the resource name
		$resource = self::expandPath($resource);

		//	TEST 1: If the resource has an attribute "absolute" don't process it, return it directly
		if($node->getAttribute("absolute")) return $resource;
		
		//	TEST 2: Test whether the file "exists" without any assistance
		if(file_exists(Amslib::rchop($resource,"?"))) return Amslib_File::relative($resource);

		//	TEST 3: Does the file exists relative to the document root?
		$test3 = Amslib_File::absolute($resource);
		if(file_exists(Amslib::rchop($test3,"?"))) return Amslib_File::relative($resource);

		//	TEST 4:	look in the package directory for the file
		$test4 = Amslib_File::reduceSlashes("$this->location/$resource");
		if(file_exists(Amslib::rchop($test4,"?"))) return Amslib_File::relative($test4);

		//	TEST 5:	search the include path for the file
		$test5 = Amslib_File::find($resource,true);
		if(file_exists(Amslib::rchop($test5,"?"))) return Amslib_File::relative($test5);

		//	FAILED: you could not find the file
		return false;
	}

	protected function openPackage()
	{
		$document = new DOMDocument('1.0', 'UTF-8');
		if(@$document->load($this->packageXML)){
			$document->preserveWhiteSpace = false;
			$this->xpath = new DOMXPath($document);

			return true;
		}

		//	TODO: This needs to be better than "OMG WE ARE ALL GONNA DIE!!!"
		print("Amslib_Plugin::openPackage(): PACKAGE FAILED TO OPEN: file[$this->packageXML]<br/>");

		return false;
	}

	protected function runPackage()
	{
		$this->initialisePlugin();
		$this->loadDependencies();
		$this->loadRouter();
		$this->initialiseModel();
		$this->loadConfiguration();
		$this->configurePlugins();
		$this->finalisePlugin();
	}

	protected function loadDependencies()
	{
		$deps = $this->xpath->query("//package/requires/plugin");

		$this->dependencies = array();

		for($a=0;$a<$deps->length;$a++){
			$name = $deps->item($a)->nodeValue;

			$this->dependencies[$name] = Amslib_Plugin_Manager::add($name,$this->location);
		}
	}

	protected function processBlock($name,$value,$callback)
	{
		$nodes = $this->xpath->query("//package/$name/$value");
		for($a=0;$a<$nodes->length;$a++){
			$n		=	$nodes->item($a);
			$id		=	$n->getAttribute("id");

			if(in_array($name,array("controller","layout","view","object","service"))){
				$item	=	$n->nodeValue;
				$params	=	array($id,$item);
			}else{
				$cond	=	$n->getAttribute("condition");
				$auto	=	$n->getAttribute("autoload");
				$media	=	$n->getAttribute("media");
				$item	=	($name == "google_font") ? $n->nodeValue : $this->findResource($this->name,$n);
				$params	=	array($id,$item,$cond,$auto,$media);
			}

			if(method_exists($this->api,$callback)){
				call_user_func_array(array($this->api,$callback),$params);
			}
		}
	}

	protected function loadConfiguration()
	{
		$this->api = $this->createAPI();

		//	If the API is not valid, return false to trigger an error.
		if(!$this->api) return false;

		$this->processBlock("controller",	"name",	"setController");
		$this->processBlock("layout",		"name",	"setLayout");
		$this->processBlock("view",			"name",	"setView");
		$this->processBlock("object",		"name",	"setObject");
		$this->processBlock("service",		"file",	"setService");
		$this->processBlock("service",		"name",	"setService2");
		$this->processBlock("image",		"file",	"setImage");
		$this->processBlock("javascript",	"file",	"setJavascript");
		$this->processBlock("stylesheet",	"file",	"setStylesheet");
		$this->processBlock("google_font",	"file",	"setGoogleFont");
		
		//	It's needed to load translators before initialise is run
		//	because any defaults that might be required in the plugin
		//	might require translated text
		$this->loadTranslators();
		
		$this->api->initialise();
	}
	
	protected function loadTranslators()
	{
		$nodes = $this->xpath->query("//package/translator");
		
		foreach($nodes as $t){
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
				
				if(Amslib_Array::hasKeys($data,array("name","type","language","location"))){
					//	The location parameter could contain special keys which need to be expanded
					//	The location parameter has a specific key called __CURRENT_PLUGIN__ which 
					//		isn't available normally, so expand this one separately
					$location = $data["location"];
					if(strpos($location,"__CURRENT_PLUGIN__") !== false){
						$location = str_replace("__CURRENT_PLUGIN__",$this->location,$location);
					}
					$location = Amslib_Plugin::expandPath($location);
					
					//	Obtain the language the system should use when printing text
					$language = Amslib_Plugin_Application::getLanguage($data["name"]);
					if(!$language) $language = reset($data["language"]);
					
					//	Create the language translator object and insert it into the api
					$translator = new Amslib_Translator2($data["type"]);
					$translator->addLanguage($data["language"]);
					$translator->setLocation($location);
					$translator->setLanguage($language);
					$translator->load();
					
					$this->api->setTranslator($data["name"],$translator);			
					
					//	If there was a "router" parameter, insert all the languages into the router system
					if(isset($data["router"])){
						foreach($data["language"] as $langCode){
							Amslib_Router_Language3::add($langCode,str_replace("_","-",$langCode));
						}
					}

					//	Now register all the languages with the application
					foreach($data["language"] as $langCode){
						Amslib_Plugin_Application::registerLanguage($data["name"], $langCode);
					}
				}
			}
		}
	}

	protected function loadRouter()
	{
		//	This loads the routes from the plugin into the central router system
		$source = Amslib_Router3::getObject("source:xml");
		$this->routes = $source->load($this->packageXML);
	}

	protected function initialisePlugin(){	/*	By default, do nothing	*/	}
	protected function finalisePlugin(){	/*	By default, do nothing	*/	}
	
	protected function configurePlugins()
	{
		$config = $this->xpath->query("//package/plugin_config");

		foreach($config as $block){
			$name	=	$block->getAttribute("name");
			$api	=	($name) ? Amslib_Plugin_Manager::getAPI($name) : $this->api;
			
			if(!$api || empty($block->childNodes)) continue;

			foreach($block->childNodes as $item){
				if($item->nodeType == 3) continue;

				if($item->nodeName == "package"){
					if(!empty($item->childNodes)) foreach($item->childNodes as $override){
						if($override->nodeType == 3) continue;
						$n	=	$override->getAttribute("name");
						$p	=	$override->getAttribute("plugin");
						$r	=	$override->getAttribute("replace");
						
						$p = ($p == $this->name) ? $this->api : Amslib_Plugin_Manager::getAPI($p);
						
						//	If the name, plugin or value are not valid, don't process it
						if(!$n || !$p || !$r) continue;

						switch($override->nodeName){
							case "layout":{			$api->setLayout($n,$p->getLayout($r),true);			}break;
							case "view":{			$api->setView($n,$p->findView($r),true);			}break;
							case "service":{		$api->setService2($n,$p->getService($r,true),true);	}break;
							case "object":{			$api->setObject($n,$p->getObject($r),true);			}break;
							case "image":{			$api->setImage($n,$p->getImage($r),true);			}break;
						}
					}
				}else{
					$api->setValue($item->nodeName,$item->nodeValue);	
				}
			}
		}
	}

	public function __construct($name=NULL,$location=NULL)
	{
		//	If you passed the information to load the plugin
		//	automatically, then load the plugin automatically :)
		if($name && $location) $this->load($name,$location);
	}

	public function load($name,$location)
	{
		$this->name			=	$name;
		$this->location		=	$location;
		$this->packageXML	=	$location."/package.xml";

		if($this->openPackage()){
			$this->runPackage();

			return $this->api;
		}

		return false;
	}

	public function getLocation()
	{
		return $this->location;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getAPI()
	{
		return $this->api;
	}

	public function setAPI($api)
	{
		$this->api = $api;
	}

	public function getModel()
	{
		return $this->api->getModel();
	}

	public function setModel($model)
	{
		$this->api->setModel($model);
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}