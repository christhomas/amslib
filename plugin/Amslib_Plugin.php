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
 * title: Antimatter Plugin: Core plugin object version 3
 * description: An object to manage how a plugin it loaded through to
 * 				how the MVC object is created and configured
 * version: 3.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/
class Amslib_Plugin
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

	protected function getPackageFilename()
	{
		return $this->location."/package.xml";
	}

	protected function findResource($resource,$absolute=false)
	{
		//	If the resource has an attribute "absolute" don't process it, return it directly
		if($absolute) return $resource;

		//	PREPARE THE STRING: expand any parameters inside the resource name
		$resource		=	self::expandPath($resource);
		$resource		=	str_replace("__PLUGIN__",$this->location,$resource);
		//	NOTE: we have to do this to get around a bug in lchop/rchop
		$query_string	=	($str=Amslib::lchop($resource,"?")) == $resource ? false : $str;
		$resource		=	Amslib::rchop($resource,"?");

		//	LOLOL: this following code is shit....chris is stupid sometimes..but I can't think of a better way to write it

		//	TEST 1:	look in the package directory for the file
		$test2 = Amslib_File::reduceSlashes("$this->location/$resource");
		if(file_exists($test2)){
			$output = Amslib_File::relative($test2);

			return $query_string ? "$output?$query_string" : $output;
		}

		//	TEST 2: Test whether the file "exists" without any assistance
		if(file_exists($resource)){
			$output = Amslib_File::relative($resource);

			return $query_string ? "$output?$query_string" : $output;
		}

		//	TEST 3: Does the file exists relative to the document root?
		$test4 = Amslib_File::absolute($resource);
		if(file_exists($test4)){
			$output = Amslib_File::relative($resource);

			return $query_string ? "$output?$query_string" : $output;
		}

		//	TEST 4:	search the include path for the file
		$test5 = Amslib_File::find($resource,true);
		if(file_exists($test5)){
			$output = Amslib_File::relative($test5);

			return $query_string ? "$output?$query_string" : $output;
		}

		//	FAILED: you could not find the file
		return false;
	}

	protected function process()
	{
		$this->api = $this->createAPI();
		$this->api->setModel($this->createObject($this->config["model"],true,true));

		//	If the API object is not valid, don't continue to process
		if(!$this->api) return false;

		$keys = array("object","value","translator","view","service","font","image","javascript","stylesheet");

		foreach($keys as $k=>$v){
			//	Don't process any keys which are not set, empty or the required method to process it is not available
			if(!isset($this->config[$v]) || empty($this->config[$v]) || !method_exists($this->api,"set".ucwords($v))){
				continue;
			}

			$callback	=	"set".ucwords($v);
			$func		=	array($this->api,$callback);

			foreach($this->config[$v] as $name=>$c){
				//	NOTE: If a parameter is marked for export and has move attribute, don't process it.
				if(isset($c["export"]) && isset($c["move"])) continue;

				if(in_array($v,array("view"))){
					$params		=	array($name,$c["value"]);
				}else if($v == "object" && isset($c["file"]	)){
					$params		=	array($name,$c["file"]);
				}else if($v == "font"){
					$params		=	array($c["type"],$name,$c["value"],$c["autoload"]);
				}else if($v == "value"){
					$params		=	array($c["name"],$c["value"]);
				}else if($v == "model"){
					//	We need to change the array layout to make models a plural of one (lulz irony)
					//	Then we can reenable this code, it'll be simpler, but not ready to work yet.
					//$params		=	array($this->createObject($c,true,true));
				}else if($v == "translator"){
					$translator	=	$this->createTranslator($c);

					if(!$translator) continue;

					$this->installLanguages($c["name"],$c);

					$params		=	array($c["name"],$translator);
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
				Amslib_Router_Language::add($langCode,str_replace("_","-",$langCode));
			}

			//	Now register all the languages with the application
			Amslib_Plugin_Application::registerLanguage($name, $langCode);
		}
	}

	protected function createObject(&$object,$singleton=false,$dieOnError=false)
	{
		if(!$object || empty($object)) return false;

		if(isset($object["cache"])) return $object["cache"];
		if(isset($object["file"]))	Amslib::requireFile($object["file"],array("require_once"=>true));

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
			$error = str_replace("__ERROR__","find class '{$object["value"]}'",$error);
			Amslib::errorLog($error);
			die($error);
		}

		if($cache) $object["cache"] = $cache;

		return $cache;
	}

	protected function createAPI()
	{
		//	Create the API object
		$api = $this->createObject($this->config["api"],true,true);

		//	An API Object was not created, so create a default Amslib_MVC object instead
		if($api == false) $api = new Amslib_MVC();

		//	Setup the api with basic name and filesystem location
		$api->setLocation($this->getLocation());
		$api->setName($this->getName());
		$api->setPlugin($this);

		return $api;
	}

	protected function createTranslator(&$config)
	{
		if(isset($config["cache"])) return $config["cache"];

		if(!Amslib_Array::hasKeys($config,array("type","language"))) return false;

		switch($config["type"]){
			case "xml":{
				//	Replace __CURRENT_PLUGIN with plugin location and expand any other paths
				$location = str_replace("__CURRENT_PLUGIN__",$this->location,$config["location"]);
				$location = Amslib_Plugin::expandPath($location);
			}break;

			case "database":{
				$database = $this->config["model"]["value"];
				$location = str_replace("__CURRENT_PLUGIN__",$database,$config["location"]);
			}break;
		}

		//	Obtain the language the system should use when printing text
		$language = Amslib_Plugin_Application::getLanguage($config["name"]);
		if(!$language) $language = reset($config["language"]);

		//	Create the language translator object and insert it into the api
		$translator = new Amslib_Translator($config["type"],$config["name"]);
		$translator->addLanguage($config["language"]);
		$translator->setLocation($location);
		$translator->setLanguage($language);
		$translator->load();

		$config["cache"] = $translator;

		return $translator;
	}

	protected function initialisePlugin(){	/* do nothing by default */ }
	protected function finalisePlugin(){	/* do nothing by default */ }

	public function __construct()
	{
		$this->search = array(
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

		$this->config = array("api"=>false,"model"=>false,"translator"=>false,"requires"=>false,"value"=>false);

		//	This stores where all the types components are stored as part of the application
		$this->setComponent("view",		"views",	"Vi_");
		$this->setComponent("object",	"objects",	"");
	}

	//	Type 1 data transfers
	protected function transferData($src,$dst,$key,$value,$move)
	{
		if(!$dst || !$src) return;

		$dst->setConfig($key,$value);
		if($move) $src->removeConfig($key);
	}

	//	Type 2 data transfers
	protected function transferData2($src,$dst,$key,$value,$move)
	{
		if(!$dst || !$src) return;

		$k = array($key,$value);
		$v = $src->getConfig($key,$value);

		$dst->setConfig($k,$v);

		if($move) $src->removeConfig($key,$value);
	}

	public function transfer()
	{
		//	Process all child transfers before the parents
		foreach(Amslib_Array::valid($this->config["requires"]) as $name=>$plugin){
			if($plugin && method_exists($plugin,"transfer")) $plugin->transfer();
			else Amslib_FirePHP::output("failed to call plugin[$name]->transfer",$this);
		}

		//	FIXME: omg, it's just a bunch of repeated code, all over the place, ripe for refactoring....
		//	FIXME: it's actually hilarious how much repeated code I have, yet I didnt refactor it yet :)
		//	NOTE: ok, I've started to refactor the code as much as I can, but I think I got stuck and can't go further
		//	NOTE: 08/03/2012: it's getting better :) yeah! finally found a nice way to refactor everything
		//	NOTE: 24/08/2012: actually, I think it has a problem of being too generic, therefore impossible to refactor correctly

		//	Process all the import/export/transfer requests on all resources
		//	NOTE: translators don't support the "move" parameter
		foreach($this->config as $key=>&$block){
			//	NOTE: perhaps I need to add "object" to here in the future?
			if(in_array($key,array("model"))){
			 	//	Models are treated slightly differently because they are singular, not plural
				//	NOTE: maybe in future models will be plural too, perhaps it's better to make it work plurally anyway
			 	$m	=	isset($block["move"]);
			 	$i	=	isset($block["import"]) && $block["import"] != $this->getName() ? $block["import"] : false;
			 	$e	=	isset($block["export"]) && $block["export"] != $this->getName() ? $block["export"] : false;
			 	$p	=	Amslib_Plugin_Manager::getPlugin($i ? $i : ($e ? $e : false));
			 	if(!$p) continue;
			 	$v1	=	$p->getConfig($key);
			 	$v2	=	$this->getConfig($key);

			 	if($i) 			$this->transferData($p,$this,$key,$v1,$m);
			 	else if($e) 	$this->transferData($this,$p,$key,$v2,$m);
			}else if(in_array($key,array("object","view","stylesheet","image","javascript","translator"))){
				//	NOTE:	transferring objects by name won't work because in the other plugin it won't
				//			know how to autoload the object from it's class because 99.99% sure the object
				//			being transferred won't be in the path, therefore it won't be autoloadable and
				//			it'll fail
				//	NOTE:	perhaps therefore, we need to transfer objects like we do models, create the
				//			object first and transfer that
				foreach(Amslib_Array::valid($block) as $iname=>$item)
				{
					if(in_array($key,array("value","translator"))) $iname = $item["name"];

					$m	=	isset($item["move"]);
					$i	=	isset($item["import"]) && $item["import"] != $this->getName() ? $item["import"] : false;
			 		$e	=	isset($item["export"]) && $item["export"] != $this->getName() ? $item["export"] : false;
			 		$p	=	Amslib_Plugin_Manager::getPlugin($i ? $i : ($e ? $e : false));
			 		if(!$p) continue;
			 		$v	=	$iname;

			 		//	FIXME: I tried to apply this here too, it broke everything I tried :(
			 		//if($e) unset($block[$iname]);

			 		if($i) 			$this->transferData2($p,$this,$key,$v,$m);
			 		else if($e) 	$this->transferData2($this,$p,$key,$v,$m);
				}
			}else if(in_array($key,array("value"))){
				foreach(Amslib_Array::valid($block) as $k=>$item)
				{
					$m	=	isset($item["move"]);
					$i	=	isset($item["import"]) && $item["import"] != $this->getName() ? $item["import"] : false;
					$e	=	isset($item["export"]) && $item["export"] != $this->getName() ? $item["export"] : false;
					$p	=	Amslib_Plugin_Manager::getPlugin($i ? $i : ($e ? $e : false));
					if(!$p) continue;
					$v	=	$item;

					if($e) unset($block[$k]);

					if($i) 			$this->transferData($p,$this,$key,$v,$m);
					else if($e) 	$this->transferData($this,$p,$key,$v,$m);
				}
			}else if(in_array($key,array("service"))){
				foreach(Amslib_Array::valid($block) as $item)
				{
					//	import means take "name" route from "plugin" and install it under the "local_name" route
					if($item["action"] == "import"){
						$r = $item["service"] == "true"
							? Amslib_Router::getService($item["name"],$item["plugin"])
							: Amslib_Router::getRoute($item["name"],$item["plugin"]);

						Amslib_Router::setRoute($item["rename"],$this->getName(),$r,false);

						//	the following parameters are available
						//
						//	plugin, name, service, rename
						//		plugin => src plugin to obtain service from
						//		name => the service to obtain
						//		service => whether it's a service or path route
						//		rename => the local name to store the copy in
					}else{
						Amslib_FirePHP::output("export",$item);
						//	Hmmm, I need a test case cause otherwise I won't know if this works
					}
				}
			}
		}
	}

	protected function getAttributeArray($node,$array=array())
	{
		foreach($node->attributes as $k=>$v) $array[$k] = $v->nodeValue;

		return $array;
	}

	public function config($name,$location)
	{
		$this->isReady	=	false;
		$this->name		=	$name;
		$this->location	=	$location;
		$this->filename	=	$this->getPackageFilename();

		try{
			$xpath			=	false;
			$document		=	new DOMDocument('1.0', 'UTF-8');

			if($document->load($this->filename)){
				$document->preserveWhiteSpace = false;
				$xpath = new DOMXPath($document);
			}
	
			if(!$xpath){
				Amslib::errorLog("xpath failed to load on filename",$this->filename);

				return $this->isReady;
			}
		}catch(Exception $e){
			Amslib::errorLog("exception caught, message",$e->getMessage());
		}

		//	Load the router, if one is present
		Amslib_Router::load($this->filename,"xml",$this->getName());

		//	Search each path and store all the configuration data
		foreach($this->search as $p){
			$list = $xpath->query("//package/$p");

			foreach($list as $node){
				switch($node->nodeName){
					case "view":{
						$child = $xpath->query("name",$node);

						foreach($child as $c){
							$p = array_merge(
								array("id"=>$c->nodeValue,"value"=>$c->nodeValue),
								$this->getAttributeArray($c)
							);

							$p["value"] = $this->getComponent($node->nodeName,$c->nodeValue);

							$this->config[$node->nodeName][$p["id"]] = $p;
						}
					}break;

					case "service":{
						$child = $xpath->query("import|export",$node);

						foreach($child as $c){
							$p				=	$this->getAttributeArray($c);
							$p["action"]	=	$c->nodeName;
							$p["rename"]	=	$c->nodeValue;

							//	You require at minimum these two attributes, or cannot accept it
							if(!isset($p["plugin"]) || !isset($p["name"])) continue;
							//	If the rename attribute doesn't exist or is empty, replace it with the name attribute
							if(!isset($p["rename"]) || !$p["rename"] || strlen($p["rename"])){
								$p["rename"] = $p["name"];
							}

							$this->config[$node->nodeName][] = $p;
						}
					}break;

					case "javascript":
					case "stylesheet":
					case "image":{
						$child = $xpath->query("file",$node);

						foreach($child as $c){
							$p = $this->getAttributeArray($c);

							$absolute	=	isset($p["absolute"]) ? true : false;
							$p["value"]	=	$this->findResource($c->nodeValue,$absolute);

							//	If a valid id exists, insert the configuration
							if(isset($p["id"])) $this->config[$node->nodeName][$p["id"]] = $p;
						}
					}break;

					case "font":{
						$child = $xpath->query("*",$node);

						foreach($child as $font){
							$p = $this->getAttributeArray($font);

							$p["value"]	=	trim($font->nodeValue);
							$p["type"]	=	$font->nodeName;
							if(!isset($p["autoload"])) $p["autoload"] = NULL;

							//	If a valid id exists, insert the configuration
							if(isset($p["id"])) $this->config[$node->nodeName][$p["id"]] = $p;
						}
					}break;

					case "translator":{
						$child = $xpath->query("*",$node);
						//	Import all the translator parameters, such as import/export
						$p = $this->getAttributeArray($node);

						foreach($child as $c){
							if($c->nodeName == "language") $p[$c->nodeName][] = $c->nodeValue;
							else $p[$c->nodeName] = $c->nodeValue;
						}

						//	Test all required data is present, if so, add the translator to be configured later
						if(	Amslib_Array::hasKeys($p,array("name","type","location","language")) ||
							Amslib_Array::hasKeys($p,array("name","import")) ||
							Amslib_Array::hasKeys($p,array("name","export")))
						{
							$this->config[$node->nodeName][] = $p;
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
						$p = $this->getAttributeArray($node);

						foreach($child as $c){
							$this->config[$node->nodeName][] = array_merge(
								array("name"=>$c->nodeName,"value"=>$c->nodeValue),
								$p
							);
						}
					}break;

					case "object":{
						$child = $xpath->query("*",$node);

						foreach($child as $c){
							$p = $this->getAttributeArray($c);

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
							$v = Amslib_Plugin::expandPath($c->nodeValue);

							if($c->nodeName == "include"){
								Amslib::addIncludePath(Amslib_File::absolute($v));
							}else{
								Amslib_Plugin::setPath($c->nodeName,$v);

								switch($c->nodeName){
									case "plugin":{
										Amslib_Plugin_Manager::addLocation($v);
									}break;

									case "docroot":{
										Amslib_File::documentRoot($v);
									}break;
								};
							}
						}
					}break;

					default:{
						$this->config[$node->nodeName] = Amslib_Plugin::expandPath($node->nodeValue);
					}break;
				}
			}
		}

		//	load all the child plugins
		$list = $xpath->query("//package/requires/plugin");

		foreach($list as $node){
			if($this->getName() != $node->nodeValue){
				$replace = $node->getAttribute("replace");
				if($replace) Amslib_Plugin_Manager::replacePluginLoad($replace,$node->nodeValue);

				$prevent = $node->getAttribute("prevent");
				if($prevent) Amslib_Plugin_Manager::preventPluginLoad($prevent,$node->nodeValue);

				$plugin = Amslib_Plugin_Manager::config($node->nodeValue,$this->location);

				if($plugin){
					$this->config["requires"][$node->nodeValue] = $plugin;
				}else{
					//Amslib_FirePHP::output("AP::config(), child plugin config failed",array($node->nodeValue,$location));
				}
			}
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
			//	Insert into the plugin manager
			Amslib_Plugin_Manager::insert($this->name,$this);
			//	finalise the plugin, finish any last requests by the plugin
			$this->finalisePlugin();

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

		if($key == "value"){
			//	Search and update any existing values
			$this->config[$key] = Amslib_Array::valid($this->config[$key]);
			foreach($this->config[$key] as &$v){
				if($v["name"] == $value["name"] && !isset($v["export"]) && !isset($v["import"])){
					$v["value"] = $value["value"];

					return;
				}
			}

			//	The value didnt already exist, so we must create it
			$this->config[$key][] = $value;
		}else if(is_string($key)){
			$this->config[$key] = $value;
		}else{
			$this->config[$key[0]][$key[1]] = $value;
		}
	}

	public function getConfig($key,$name=NULL)
	{
		switch($key){
			case "translator":{
				foreach($this->config[$key] as $t){
					if($t["name"] == $name){
						if(isset($t["type"]))	$this->createTranslator($t);
						if(isset($t["cache"]))	return $t;
					}
				}
			}break;

			case "model":{
				$this->createObject($this->config[$key]);
				return $this->config[$key];
			}break;

			default:{
				if($name === NULL){
					return isset($this->config[$key]) ? $this->config[$key] : false;
				}else{
					if(is_array($name)) $name = current($name);
					$name = (string)$name;

					return isset($this->config[$key][$name]) ? $this->config[$key][$name] : false;
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
		//	Loop through all the paths given in the htaccess file and attempt to replace them
		foreach(Amslib_Router::listPaths() as $key){
			$path = str_replace($key, Amslib_Router::getPath($key), $path);
		}

		$path	=	str_replace("__WEBSITE__",			self::$path["website"],		$path);
		$path	=	str_replace("__ADMIN__",			self::$path["admin"],		$path);
		$path	=	str_replace("__AMSLIB__",			self::$path["amslib"],		$path);
		$path	=	str_replace("__DOCROOT__",			self::$path["docroot"],		$path);

		return Amslib_File::reduceSlashes($path);
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}
