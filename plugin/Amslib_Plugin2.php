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
	
	//	The ready state of the plugin is true/false depending on whether it has been configured
	protected $isReady;
	
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
	protected $search;
	
	//	The path prefixes of each component type
	protected $prefix = array();
	
	protected function setComponent($component,$directory,$prefix)
	{
		$this->prefix[$component] = "/$directory/$prefix";
	}
	
	protected function getComponent($component,$name)
	{
		return $this->location.$this->prefix[$component]."$name.php";
	}

	protected function findResource($resource,$absolute=false)
	{
		//	PREPARE THE STRING: expand any parameters inside the resource name
		$resource = self::expandPath($resource);

		//	TEST 1: If the resource has an attribute "absolute" don't process it, return it directly
		if($absolute) return $resource;
		
		//	TEST 2:	look in the package directory for the file
		$test2 = Amslib_File::reduceSlashes("$this->location/$resource");
		if(file_exists(Amslib::rchop($test2,"?"))) return Amslib_File::relative($test2);		
		
		//	TEST 3: Test whether the file "exists" without any assistance
		if(file_exists(Amslib::rchop($resource,"?"))) return Amslib_File::relative($resource);

		//	TEST 4: Does the file exists relative to the document root?
		$test4 = Amslib_File::absolute($resource);
		if(file_exists(Amslib::rchop($test4,"?"))) return Amslib_File::relative($resource);

		//	TEST 5:	search the include path for the file
		$test5 = Amslib_File::find($resource,true);
		if(file_exists(Amslib::rchop($test5,"?"))) return Amslib_File::relative($test5);

		//	FAILED: you could not find the file
		return false;
	}
	
	protected function process()
	{
		$this->api = $this->createAPI();
		$this->api->setModel($this->createObject($this->config["model"],true,true));
		
		//	If the API object is not valid, don't continue to process
		if(!$this->api) return false;
		
		//	Load the router, if one is present
		$source = Amslib_Router3::getObject("source:xml");
		$this->routes = $source->load($this->filename);
		
		//	Load all routes into the plugin, so you can identify which plugin is responsible for a route
		foreach($this->routes as $name=>$route) $this->api->setRoute($name,$route);

		$keys = array("object","translator","layout","view","value","service","font","image","javascript","stylesheet");
		
		foreach($keys as $k=>$v){
			//	Don't process any keys which are not set, empty or the required method to process it is not available
			if(!isset($this->config[$v]) || empty($this->config[$v]) || !method_exists($this->api,"set".ucwords($v))){
				continue;		
			}

			$callback	=	"set".ucwords($v);
			$func		=	array($this->api,$callback);
			
			foreach($this->config[$v] as $name=>$c){
				//	NOTE: If a parameter is marked for export, don't process it.
				if(isset($c["export"])) continue;
					
				if(in_array($v,array("layout","view","service"))){
					$params		=	array($name,$c["value"]);
				}else if($v == "object"){
					$params		=	array($name,$c["file"]);
				}else if($v == "font"){
					$params		=	array($c["type"],$name,$c["value"]);
				}else if($v == "value"){
					$params		=	array($c["name"],$c["value"]);
				}else if($v == "model"){
					//	We need to change the array layout to make models a plural of one (lulz irony)
					//	Then we can reenable this code, it'll be simpler, but not ready to work yet.
					//$params		=	array($this->createObject($c,true,true));
				}else if($v == "translator"){
					$translator	=	$this->createTranslator($name);
					
					if(!$translator) continue;	
					
					$this->installLanguages($name,$c);
					
					$params		=	array($name,$translator);
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
	
	protected function installLanguages($name,$lang)
	{
		foreach($lang["language"] as $langCode){
			//	If there was a "router" parameter, insert all the languages into the router system
			if(isset($lang["router"])){
				Amslib_Router_Language3::add($langCode,str_replace("_","-",$langCode));
			}

			//	Now register all the languages with the application
			Amslib_Plugin_Application2::registerLanguage($name, $langCode);
		}
	}
	
	protected function createObject(&$object,$singleton=false,$dieOnError=false)
	{
		if(!$object || empty($object)) return false;
		
		if(isset($object["cache"])) return $object["cache"];
		if(isset($object["file"]))	Amslib::requireFile($object["file"]);
		
		$error =  "FATAL ERROR(".__METHOD__.") Could not __ERROR__ for plugin '{$this->name}'<br/>";
		
		$cache = false;
		
		if(class_exists($object["value"])){
			if($singleton){
				if(method_exists($object["value"],"getInstance")){
					$cache = call_user_func(array($object["value"],"getInstance"));
				}else{
					die(str_replace("__ERROR__","find the getInstance method in the API object '{$object["value"]}'",$error));
				}
			}else{
				$cache = new $object["value"];
			}
		}else if($dieOnError){
			die(str_replace("__ERROR__","find class '{$object["value"]}'",$error));
		}
		
		if($cache) $object["cache"] = $cache;
		
		return $cache;
	}
	
	protected function createAPI()
	{ 
		$api = $this->createObject($this->config["api"],true,true);

		//	An API Object was not created, so create a default Amslib_MVC4 object instead
		if($api == false) $api = new Amslib_MVC4();

		//	Setup the api with basic name and filesystem location
		$api->setLocation($this->getLocation());
		$api->setName($this->getName());
		$api->setPlugin($this);
	
		return $api;
	}
	
	protected function createTranslator($name)
	{
		if(!isset($this->config["translator"][$name])) return false;
		
		$t = &$this->config["translator"][$name];
		if(isset($t["cache"])) return $t["cache"];
			
		//	Replace __CURRENT_PLUGIN with plugin location and expand any other paths
		$location = str_replace("__CURRENT_PLUGIN__",$this->location,$t["location"]);
		$location = Amslib_Plugin::expandPath($location);
		
		//	Obtain the language the system should use when printing text
		$language = Amslib_Plugin_Application2::getLanguage($name);
		if(!$language) $language = reset($t["language"]);
		
		//	Create the language translator object and insert it into the api
		$translator = new Amslib_Translator2($t["type"]);
		$translator->addLanguage($t["language"]);
		$translator->setLocation($location);
		$translator->setLanguage($language);
		$translator->load();
		
		$t["cache"] = $translator;

		return $translator;
	}
	
	protected function initialisePlugin(){	/* do nothing by default */ }
	protected function finalisePlugin(){	/* do nothing by default */ }
	
	
	public function __construct()
	{
		$this->search = array(
			"layout",
			"view",
			"object",
			"service",
			"image",
			"javascript",
			"stylesheet",
			"font",
			"value",
			"translator"
		);
		
		$this->config = array("api"=>false,"model"=>false,"translator"=>false,"requires"=>false);
		
		//	This stores where all the types components are stored as part of the application
		$this->setComponent("layout",	"layouts",	"La_");
		$this->setComponent("view",		"views",	"Vi_");
		$this->setComponent("object",	"objects",	"");
		$this->setComponent("service",	"services",	"Sv_");
	}
	
	public function transfer()
	{
		//	Process all child transfers before the parents	
		foreach(Amslib_Array::valid($this->config["requires"]) as $name=>$plugin){
			if($plugin && method_exists($plugin,"transfer")) $plugin->transfer();
			//else Amslib_FirePHP::output("failed to call plugin[$name]->transfer",$this);
		}

		//	FIXME: omg, it's just a bunch of repeated code, all over the place, ripe for refactoring....
		
		//	Process all the import/export/transfer requests on all resources
		//	NOTE: translators don't support the "move" parameter
		foreach($this->config as $key=>$block){
			if(in_array($key,array("object","view","layout","service","stylesheet","image","javascript","translator"))){
				//	TODO: if I used $item["name"] instead of $name as the key, I could merge against the "value" block below
				foreach(Amslib_Array::valid($block) as $name=>$item){
					if(isset($item["import"])){
						$plugin = Amslib_Plugin_Manager2::getPlugin($item["import"]);
						$this->setConfig(array($key,$name),$plugin->getConfig($key,$name));
						if(isset($item["move"])) $plugin->removeConfig($key,$name);
					}else if(isset($item["export"])){
						$plugin = Amslib_Plugin_Manager2::getPlugin($item["export"]);
						$plugin->setConfig(array($key,$name),$this->getConfig($key,$name));
						if(isset($item["move"])) $this->removeConfig($key,$name);
					}
				}
			}else if($key == "model"){
				//	Models are treated slightly differently because they are singular, not plural
				//	NOTE: maybe in future models will be plural too, perhaps it's better to make it work plurally anyway
				if(isset($block["import"])){
					$plugin = Amslib_Plugin_Manager2::getPlugin($block["import"]);
					$this->setConfig($key,$plugin->getConfig($key));
					if(isset($block["move"])) $plugin->removeConfig($key);
				}else if(isset($block["export"])){
					$plugin = Amslib_Plugin_Manager2::getPlugin($block["export"]);
					$plugin->setConfig($key,$this->getConfig($key));
					if(isset($block["move"])) $this->removeConfig($key);
				}
			}else if($key == "value"){
				foreach(Amslib_Array::valid($block) as $item){
					if(isset($item["import"])){
						$plugin = Amslib_Plugin_Manager2::getPlugin($item["import"]);
						$this->setConfig(array($key,$item["name"]),$plugin->getConfig($key,$item["name"]));
						if(isset($item["move"])) $plugin->removeConfig($key,$item["name"]);	
					}else if(isset($item["export"])){
						$plugin = Amslib_Plugin_Manager2::getPlugin($item["export"]);
						$plugin->setConfig(array($key,$item["name"]),$this->getConfig($key,$item["name"]));
						if(isset($item["move"])) $this->removeConfig($key,$item["name"]);
					}
				}
			}
		}
	}

	public function config($name,$location)
	{
		$this->isReady	=	false;
		$this->name		=	$name;
		$this->location	=	$location;
		$this->filename	=	$location."/package.xml";

		$xpath			=	false;
		$document		=	new DOMDocument('1.0', 'UTF-8');
		
		if(@$document->load($this->filename)){
			$document->preserveWhiteSpace = false;
			$xpath = new DOMXPath($document);
		}
		
		if(!$xpath) return $this->isReady;
		
		//	Search each path and store all the configuration data
		foreach($this->search as $p){
			$list = $xpath->query("//package/$p");
			
			foreach($list as $node){
				switch($node->nodeName){
					case "layout":
					case "view":
					case "service":{
						$child = $xpath->query("name",$node);
						
						foreach($child as $c){
							$p = array();
							$p["id"] = $c->nodeValue;
							foreach($c->attributes as $k=>$v) $p[$k] = $v->nodeValue;
							$p["value"] = $this->getComponent($node->nodeName,$c->nodeValue);
							
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
						
						//	Import all the translator parameters, such as import/export
						foreach($node->attributes as $k=>$v) $p[$k] = $v->nodeValue;
						
						$p["name"] = $node->nodeValue;
						
						foreach($child as $c){
							if($c->nodeName == "language") $p[$c->nodeName][] = $c->nodeValue;
							else $p[$c->nodeName] = $c->nodeValue;
						}
						
						//	Test all required data is present, if so, add the translator to be configured later
						if(	Amslib_Array::hasKeys($p,array("name","type","location","language")) ||
							Amslib_Array::hasKeys($p,array("name","import")) ||
							Amslib_Array::hasKeys($p,array("name","export")))
						{
							$this->config[$node->nodeName][$p["name"]] = $p;
						}
					}break;
					
					case "version":{
						$child = $xpath->query("*",$node);
						
						foreach($child as $c){
							$this->config[$node->nodeName][$c->nodeName] = $c->nodeValue;
						}
					}break;
					
					case "value":{
						$child = $xpath->query("*",$node);
						$p = array();
						foreach($node->attributes as $k=>$v) $p[$k] = $v->nodeValue;
						
						foreach($child as $c){
							$this->config[$node->nodeName][] = array_merge(array("name"=>$c->nodeName,"value"=>$c->nodeValue),$p);
						}
					}break;
					
					case "object":{
						$child = $xpath->query("*",$node);
						
						foreach($child as $c){
							$p = array();
							foreach($c->attributes as $k=>$v) $p[$k] = $v->nodeValue;
							$p["value"] = $c->nodeValue;
							
							$file = $this->getComponent("object",$p["value"]);
							if(file_exists($file)) $p["file"] = $file;
							
							//	Make sure generic objects are referenced properly
							$name = $c->nodeName == "name" ? $p["value"] : $c->nodeName;
							
							if(in_array($name,array("api","model"))){
								$this->config[$name] = $p;
							}else{
								$this->config[$node->nodeName][$name] = $p;
							}
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
		
		//	load all the child plugins
		$list = $xpath->query("//package/requires/plugin");
			
		foreach($list as $node){
			$this->config["requires"][$node->nodeValue] = Amslib_Plugin_Manager2::config($node->nodeValue,$this->location);
		}
		
		$this->isReady = true;
		
		//	Now the configuration data is loaded, initialise the plugin with any custom requirements
		$this->initialisePlugin();
		
		return $this->isReady;
	}

	public function load()
	{
		if($this->isReady)
		{
			//	Process all child plugins before the parents	
			foreach(Amslib_Array::valid($this->config["requires"]) as $plugin) $plugin->load();
			
			//	run all the configuration into the api object
			$this->process();
			//	initialise the api object, it's now ready to use externally
			$this->api->initialise();
			//	finalise the plugin, finish any last requests by the plugin
			$this->finalisePlugin();
			//	Insert into the plugin manager
			Amslib_Plugin_Manager2::insert($this->name,$this);

			return $this->api;
		}

		//	TODO: This needs to be better than "OMG WE ARE ALL GONNA DIE!!!"
		print(get_class($this)."::load(): PACKAGE FAILED TO OPEN: file[$this->filename]<br/>");

		return false;
	}
	
	public function setConfig($key,$value)
	{
		//	This is important, because otherwise values imported/exported through transfer() will not execute in process()
		unset($value["import"],$value["export"]);
		
		if(is_string($key)){
			$this->config[$key] = $value;
		}else if(is_array($key) && isset($key[0]) && $key[0] == "value"){
			$k = Amslib_Array::findKey($this->config[$key[0]],"name",$key[1]);
			if($k !== false) $this->config[$key[0]][$k] = $value;
		}else{
			$this->config[$key[0]][$key[1]] = $value;
		}
	}
	
	public function getConfig($type,$name=NULL)
	{
		switch($type){
			case "translator":{
				$this->config[$type][$name]["cache"] = $this->createTranslator($name);
				return $this->config[$type][$name];	
			}break;
			
			case "model":{
				$this->createObject($this->config[$type]);
				return $this->config[$type];		
			}break;
			
			case "value":{
				return Amslib_Array::find($this->config[$type], "name", $name);
			}break;
			
			default:{
				if($name === NULL){
					return isset($this->config[$type]) ? $this->config[$type] : false;	
				}else{
					return isset($this->config[$type][$name]) ? $this->config[$type][$name] : false;	
				}	
			}break;
		}
			
		return false;
	}	
	
	public function removeConfig($type,$id=NULL)
	{
		if($id === NULL){
			unset($this->config[$type]);
		}else if(is_array($type)){
			unset($this->config[$type][$id]);
		}
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

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}