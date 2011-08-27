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
 * file: Amslib_Plugin2.php
 * title: Antimatter Plugin: Core plugin object version 2
 * description: An object to manage how a plugin it loaded through to
 * 				how the MVC object is created and configured
 * version: 2.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/
class Amslib_Plugin2
{
	//	NOTE:	The paths registered in the application which are ways to replace 
	//			templated strings with their dynamically valid replacements
	static protected $path;
	
	//	The name of the plugin
	protected $name;
	
	//	The location of the plugin on the filesystem
	protected $location;
	
	//	The API object created by the plugin
	protected $api;	
	
	//	The filename on disk with the plugin configuration (typically package.xml)
	protected $filename;
	
	//	The XPath object used to query the xml configuration
	protected $xpath;
	
	//	The Current XML Configuration storing all the data from the package XML file
	protected $config;
	
	//	The list of XML XPath expressions that will be searched for data and stored
	protected $searchXPath;

	//	We load the router afterwards because it means the parent routes always override the child routes
	//	which is desireable behaviour, a child plugin is merely loaded to help the parent, not override the parent
	//	therefore when you talk about routes, you obviously don't want a child to override the routes of the parent
	//	since then unpredictable behaviour might arise, like what happened in the version 1, where you'd load
	//	an administration panel plugin which clashed with it's equal in the website for the same routes, 
	//	meaning you'd get admin panel routes installed into the website router, obviously this is not a good thing.
	protected function loadRouter()
	{
		//	This loads the routes from the plugin into the central router system
		$source = Amslib_Router3::getObject("source:xml");
		$this->routes = $source->load($this->filename);
	}
	
	protected function findResource($resource,$absolute=false)
	{
		//	PREPARE THE STRING: expand any parameters inside the resource name
		$resource = self::expandPath($resource);

		//	TEST 1: If the resource has an attribute "absolute" don't process it, return it directly
		if($absolute) return $resource;
		
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
	
	static protected function setPath($name,$path)
	{
		self::$path[$name] = $path;
	} 
	
	static public function expandPath($path)
	{
		$path	=	str_replace("__WEBSITE__",			self::$path["website"],	$path);
		$path	=	str_replace("__ADMIN__",			self::$path["admin"],	$path);
		$path	=	str_replace("__AMSLIB__",			self::$path["amslib"],	$path);
		$path	=	str_replace("__DOCROOT__",			self::$path["docroot"],	$path);

		return Amslib_File::reduceSlashes($path);
	}
	
	public function __construct($name=NULL,$location=NULL)
	{
		$this->searchXPath = array(
			"layout",
			"view",
			"object",
			"service",
			"image",
			"javascript",
			"stylesheet",
			"font",
			"value",
			"translator",
			//	NOTE:	Always make sure that "requires" is the last path searched because
			//			you need to load and add the plugin's configuration before you load the children
			"requires"
		);

		//	Only load the plugin if the require basic information is given, otherwise defer to whoever created the object
		if($name && $location) $this->load($name,$location);
	}
	
	protected function loadConfiguration($xpath)
	{
		//	Search each path and store all the configuration data
		foreach($this->searchXPath as $p){
			$list = $xpath->query("//package/$p");
			
			foreach($list as $node){
				switch($node->nodeName){
					case "requires":{
						$plugin = $xpath->query("plugin",$node);
						
						foreach($plugin as $p){
							$this->config[$node->nodeName][$p->nodeValue] = Amslib_Plugin_Manager2::add($p->nodeValue,$this->location);
						}
					}break;
	
					case "layout":
					case "view":
					case "service":{
						$child = $xpath->query("name",$node);
						
						foreach($child as $c){
							$p = array();
							$p["id"] = $c->nodeValue;
							foreach($c->attributes as $k=>$v) $p[$k] = $v->nodeValue;
							$p["value"] = $c->nodeValue;
							
							$this->config[$node->nodeName][$p["id"]] = $p;	
						}
					}break;
					
					case "javascript":
					case "stylesheet":
					case "image":{
						$child = $xpath->query("file",$node);
						
						foreach($child as $c){
							$p = array();
							foreach($c->attributes as $k=>$v) $p[$k] = $v->nodeValue;
							$absolute	=	isset($p["absolute"]) ? true : false;
							$p["value"]	=	$this->findResource($c->nodeValue,$absolute);
							
							$this->config[$node->nodeName][$p["id"]] = $p;
						}
					}break;
					
					case "font":{
						$p = array();
						foreach($node->attributes as $k=>$v) $p[$k] = $v->nodeValue;
						$p["value"] = $node->nodeValue;
							
						$this->config[$node->nodeName][$p["id"]] = $p;
					}break;
	
					case "translator":{
						$child = $xpath->query("*",$node);
						$p = array();
						$l = array();
						
						//	Import all the translator parameters, such as import/export
						foreach($node->attributes as $k=>$v) $p[$k] = $v->nodeValue;
						
						foreach($child as $c){
							$v = $c->nodeValue;
							
							foreach($c->attributes as $k=>$v) $p[$k] = $v->nodeValue;
							$p["value"] = $c->nodeValue;
							
							if($c->nodeName == "name") $name = $v;
							if($c->nodeName == "language") $p[$c->nodeName][] = $v;
							else $p[$c->nodeName] = $v;
						}
						
						$this->config[$node->nodeName][$name] = $p;
					}break;
					
					case "value":
					case "version":{
						$child = $xpath->query("*",$node);
						
						foreach($child as $c){
							$p = array();
							foreach($c->attributes as $k=>$v) $p[$k] = $v->nodeValue;
							$p["value"] = $c->nodeValue;
							
							if($p) $this->config[$node->nodeName][$c->nodeName] = $p;
						}
					}break;
					
					case "object":{
						$child = $xpath->query("*",$node);
						
						foreach($child as $c){
							$p = array();
							foreach($c->attributes as $k=>$v) $p[$k] = $v->nodeValue;
							$p["value"] = $c->nodeValue;
							
							//	NOTE:	hmmm, doesn't this violate the rule Amslib_MVC3 is responsible for the 
							//			filesystm layout? since we are putting the filesystem location directly in here 
							$file = "$this->location/objects/{$p["value"]}.php";
							if(file_exists($file)) $p["file"] = $file;

							$this->config[$node->nodeName][$c->nodeName] = $p;
						}
					}break;
					
					case "path":{
						$child = $xpath->query("*",$node);
						
						foreach($child as $c){
							$v = Amslib_Plugin2::expandPath($c->nodeValue);
							
							if($c->nodeName == "include"){
								Amslib::addIncludePath(Amslib_File::absolute($v));
							}else{
								Amslib_Plugin2::setPath($c->nodeName,$v);
								
								switch($c->nodeName){
									case "plugin":{
										Amslib_Plugin_Manager2::addLocation($v);
									}break;
									
									case "docroot":{
										Amslib_File::documentRoot($v);			
									}break;
								};
							}
						}
					}break;
					
					default:{
						$this->config[$node->nodeName] = Amslib_Plugin2::expandPath($node->nodeValue);	
					}break;
				}
			}
		}
		
		//	Transfer these resources to specific keys which are controlled differently
		$this->config["api"]	=	isset($this->config["object"]["api"]) ? $this->config["object"]["api"] : false;
		$this->config["model"]	=	isset($this->config["object"]["model"]) ? $this->config["object"]["model"] : false;

		unset($this->config["object"]["api"],$this->config["object"]["model"]);
	}
	
	protected function processRelocations()
	{
		//	Process all the import/export/transfer requests on all resources
		foreach($this->config as $key=>$block){
			if(!is_array($block)) continue;

			//	Import from the named plugin the requested key into THIS object
			if(isset($block["import"])){
				$plugin = Amslib_Plugin_Manager2::getPlugin($block["import"]);
				$this->setConfig($key,$plugin->getConfig($key));
				//	If transfer is requested, you must eliminate the original data
				if(isset($block["transfer"])) $plugin->removeConfig($key);
			}
			
			//	Export from THIS object the requested key to the named plugin
			if(isset($block["export"])){
				$plugin = Amslib_Plugin_Manager2::getPlugin($block["export"]);
				$plugin->setConfig($key,$this->getConfig($key));
				//	If transfer is requested, you must eliminate the original data
				if(isset($block["transfer"])) $this->removeConfig($key);
			}
		}
	}
	
	protected function processConfiguration()
	{
		$this->api = $this->createAPI();
		
		//	If the API is not valid, return false to trigger an error.
		if(!$this->api) return false;
		
		$keys = array("layout","view","object","value","service","font","image","javascript","stylesheet");
		
		//	Remove any types which cannot be processed with this object
		foreach($keys as $k=>$v){
			if(!isset($this->config[$v]) || empty($this->config[$v]) || !method_exists($this->api,"set".ucwords($v))){
				continue;		
			}
			
			$callback	=	"set".ucwords($v);
			$func		=	array($this->api,$callback);
			
			foreach($this->config[$v] as $name=>$c){
				if(in_array($v,array("layout","view","object","service","value"))){
					$params		=	array($name,$c["value"]);
				}else if($v == "font"){
					$params		=	array($c["type"],$name,$c["value"]);
				}else{
					$value		=	isset($c["value"]) ? $c["value"] : NULL;
					$condition	=	isset($c["condition"]) ? $c["condition"] : NULL;
					$autoload	=	isset($c["autoload"]) ? $c["autoload"] : NULL;
					$media		=	isset($c["media"]) ? $c["media"] : NULL;
					
					$params		=	array($name,$value,$condition,$autoload,$media);
				}

				call_user_func_array($func,$params);
			}
		}
	}
	
	protected function createObject($object,$singleton=false,$dieOnError=false)
	{
		if(!$object || empty($object)) return false;
		
		if(isset($object["file"])) Amslib::requireFile($object["file"]);
		
		$error =  "FATAL ERROR(".__CLASS__."::".__METHOD__.") Could not __ERROR__ for plugin '{$this->name}'<br/>";
		
		//	here we are not interested in import/export, since all that should have happened
		
		if(class_exists($object["value"])){
			if($singleton){
				if(method_exists($object["value"],"getInstance")){
					return call_user_func(array($object["value"],"getInstance"));
				}else{
					die(str_replace("__ERROR__","find the getInstance method in the API object '{$object["value"]}'",$error));
				}
			}else{
				return new $object["value"];
			}
		}else if($dieOnError){
			die(str_replace("__ERROR__","find class '{$object["value"]}'",$error));
		}
		
		return false;
	}
	
	protected function createAPI()
	{
		$api = $this->createObject($this->config["api"],true,true);

		//	An API Object was not created, so create a default Amslib_MVC3 object instead
		if($api == false) $api = new Amslib_MVC3();

		//	Setup the api with basic name and filesystem location
		$api->setLocation($this->getLocation());
		$api->setName($this->getName());
		$api->setPlugin($this);

		//	Assign the model to the plugin
		$api->setModel($this->createObject($this->config["model"],true,true));

		//	Load all routes into the plugin, so you can identify which plugin is responsible for a route
		foreach($this->routes as $name=>$route) $api->setRoute($name,$route);

		return $api;
	}
	
	protected function loadTranslators()
	{
		if(!empty($this->config["translator"])) foreach($this->config["translator"] as $name=>$t){
			//print("Translators[{$this->name}] = ".Amslib::var_dump($t,true));
			if(Amslib_Array::hasKeys($t,array("name","type","language","location"))){
				//	The location parameter could contain special keys which need to be expanded
				//	The location parameter has a specific key called __CURRENT_PLUGIN__ which 
				//		isn't available normally, so expand this one separately
				$location = $t["location"];
				if(strpos($location,"__CURRENT_PLUGIN__") !== false){
					$location = str_replace("__CURRENT_PLUGIN__",$this->location,$location);
				}
				$location = Amslib_Plugin::expandPath($location);
				
				//	Obtain the language the system should use when printing text
				$language = Amslib_Plugin_Application::getLanguage($name);
				if(!$language) $language = reset($t["language"]);
				
				//	Create the language translator object and insert it into the api
				$translator = new Amslib_Translator2($t["type"]);
				$translator->addLanguage($t["language"]);
				$translator->setLocation($location);
				$translator->setLanguage($language);
				$translator->load();
				
				$this->api->setTranslator($name,$translator);			
				
				//	If there was a "router" parameter, insert all the languages into the router system
				if(isset($t["router"])){
					foreach($t["language"] as $langCode){
						Amslib_Router_Language3::add($langCode,str_replace("_","-",$langCode));
					}
				}

				//	Now register all the languages with the application
				foreach($t["language"] as $langCode){
					Amslib_Plugin_Application::registerLanguage($name, $langCode);
				}				
			}
		}
	}
	
	protected function initialisePlugin(){	/* do nothing by default */ }
	protected function finalisePlugin(){	/* do nothing by default */ }

	public function load($name,$location)
	{
		$this->name		=	$name;
		$this->location	=	$location;
		$this->filename	=	$location."/package.xml";
		
		$document = new DOMDocument('1.0', 'UTF-8');
		if(@$document->load($this->filename)){
			$document->preserveWhiteSpace = false;
			$xpath = new DOMXPath($document);
			
			//	initialise the plugin, do some tasks before starting
			$this->initialisePlugin();
			//	load configuration tree
			$this->loadConfiguration($xpath);
			//	process all resource relocations within plugin tree
			$this->processRelocations();
			//	load all routes, ensuring parent routes override child routes
			$this->loadRouter();
			//	run all the configuration into the api object
			$this->processConfiguration();
			//	load all the translators for this plugin so text renders correctly
			$this->loadTranslators();
			//	initialise the api object, it's now ready to use externally
			$this->api->initialise();
			//	finalise the plugin, finish any last requests by the plugin
			$this->finalisePlugin();
			
			return $this->api;
		}

		//	TODO: This needs to be better than "OMG WE ARE ALL GONNA DIE!!!"
		print(get_class($this)."::load(): PACKAGE FAILED TO OPEN: file[$this->filename]<br/>");

		return false;
	}
	
	public function setConfig($name,$value)
	{
		$this->config[$name] = $value;
	}
	
	public function getConfig($name)
	{
		return isset($this->config[$name]) ? $this->config[$name] : false;		
	}
	
	public function removeConfig($key)
	{
		unset($this->config[$key]);
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