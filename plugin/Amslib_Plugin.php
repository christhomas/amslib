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
 * 	class:	Amslib_Plugin
 *
 *	group:	plugin
 *
 *	file:	Amslib_Plugin.php
 *
 *	description:
 *		An object to manage how a plugin it loaded through to
 * 		how the MVC object is created and configured
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Plugin
{
	//	NOTE:	The paths registered in the application which are ways to replace
	//			templated strings with their dynamically valid replacements
	static protected $path;

	//	The ready state of the plugin is true/false depending on whether it has been configured
	protected $isReady;

	//	The load state of the plugin is true/false depending on whether it has been loaded
	protected $isLoaded;

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

	/**
	 * 	method:	setComponent
	 *
	 * 	todo: write documentation
	 */
	protected function setComponent($component,$directory,$prefix)
	{
		$this->prefix[$component] = "/$directory/$prefix";
	}

	/**
	 * 	method:	getComponent
	 *
	 * 	todo: write documentation
	 */
	protected function getComponent($component,$name)
	{
		return $this->location.$this->prefix[$component]."$name.php";
	}

	/**
	 * 	method:	getPackageFilename
	 *
	 * 	todo: write documentation
	 */
	protected function getPackageFilename()
	{
		return $this->location."/package.xml";
	}

	/**
	 * 	method:	findResource
	 *
	 * 	todo: write documentation
	 */
	protected function findResource($resource,$absolute=false)
	{
		//	If the resource has an attribute "absolute" don't process it, return it directly
		if($absolute) return $resource;

		//	PREPARE THE STRING: expand any parameters inside the resource name
		$resource	=	self::expandPath($resource);
		$resource	=	str_replace("__PLUGIN__",$this->location,$resource);
		//	NOTE: we have to do this to get around a bug in lchop/rchop
		$query		=	($str=Amslib::lchop($resource,"?")) == $resource ? false : $str;
		$resource	=	Amslib::rchop($resource,"?");
		$output		=	false;

		//	TEST 1:	look in the package directory for the file
		$test1 = Amslib_File::reduceSlashes("$this->location/$resource");
		if(!$output && file_exists($test1)){
			$output = Amslib_File::relative($test1);
		}

		//	TEST 2: Test whether the file "exists" without any assistance
		if(!$output && file_exists($resource)){
			$output = Amslib_File::relative($resource);
		}

		//	TEST 3: Does the file exists relative to the document root?
		$test3 = Amslib_File::absolute($resource);
		if(!$output && file_exists($test3)){
			//	Why do we see whether $test3 exists, then discard it and use $resource?
			$output = Amslib_File::relative($resource);
		}

		//	TEST 4:	search the include path for the file
		$test4 = Amslib_File::find($resource,true);
		if($output && file_exists($test4)){
			$output = Amslib_File::relative($test4);
		}

		//	Return either $output value on it's own, or appended with the query if required
		//	If output is false or valid string, it'll return $output at minimum,
		//		only appending the query if it's valid too
		return $output && $query ? "$output?$query" : $output;
	}

	/**
	 * 	method:	process
	 *
	 * 	todo: write documentation
	 */
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

	//	NOTE: hmmmm, I really hate this code....I am not 100% sure what it does
	//	NOTE: investigate a way to remove this code and/or improve it to the point where it's logical again
	/**
	 * 	method:	installLanguages
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	createObject
	 *
	 * 	todo: write documentation
	 */
	protected function createObject(&$object,$singleton=false,$dieOnError=false)
	{
		if(!$object || empty($object)) return false;

		if(isset($object["cache"])) return $object["cache"];
		if(isset($object["file"]))	Amslib::requireFile($object["file"],array("require_once"=>true));

		//	Create the generic string for all the errors
		$error =  "FATAL ERROR(".__METHOD__.") Could not __ERROR__ for plugin '{$this->name}'<br/>";

		$cache = false;

		if(class_exists($object["value"])){
			if($singleton){
				if(method_exists($object["value"],"getInstance")){
					$cache = call_user_func(array($object["value"],"getInstance"));
				}else{
					//	your object was not a compatible singleton, we need to find the getInstance method
					die(str_replace("__ERROR__","find the getInstance method in the API object '{$object["value"]}'",$error));
				}
			}else{
				$cache = new $object["value"];
			}
		}else if($dieOnError){
			//	The class was not found, we cannot continue
			//	NOTE:	I am not sure why we cannot continue here, it might not be something
			//			critical and yet we're stopping everything
			$error = str_replace("__ERROR__","find class '{$object["value"]}'",$error);
			Amslib::errorLog("stack_trace",$error,$object);
			die($error);
		}

		if($cache) $object["cache"] = $cache;

		return $cache;
	}

	/**
	 * 	method:	createAPI
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	createTranslator
	 *
	 * 	todo: write documentation
	 */
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

		//	Create the language translator object and insert it into the api
		$translator = new Amslib_Translator($config["type"],$config["name"]);
		$translator->addLanguage($config["language"]);
		$translator->setLocation($location);

		$config["cache"] = $translator;

		return $translator;
	}

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
		$api = $this->getAPI();

		if($api){
			$translators = $api->listTranslators(false);

			foreach(Amslib_Array::valid($translators) as $name=>$object){
				//	Obtain the language the system should use when printing text
				$object->setLanguage(Amslib_Plugin_Application::getLanguage($name));
				$object->load();
			}

			$api->finalise();
		}else{
			Amslib::errorLog("plugin not found?",$api);
		}
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		$this->search = array(
			"view",
			"object",
			"controller",
			"model",
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
		//	WARNING: object is soon to be deprecated
		$this->setComponent("object",		"objects",		"");
		$this->setComponent("controller",	"controllers",	"Ct_");
		$this->setComponent("model",		"models",		"Mo_");
		$this->setComponent("view",			"views",		"Vi_");
	}

	//	Type 1 data transfers
	/**
	 * 	method:	transferData
	 *
	 * 	todo: write documentation
	 */
	protected function transferData($src,$dst,$key,$value,$move)
	{
		if(!$dst || !$src) return;

		$dst->setConfig($key,$value);
		if($move) $src->removeConfig($key);
	}

	//	Type 2 data transfers
	/**
	 * 	method:	transferData2
	 *
	 * 	todo: write documentation
	 */
	protected function transferData2($src,$dst,$key,$value,$move)
	{
		if(!$dst || !$src) return;

		$k = array($key,$value);
		$v = $src->getConfig($key,$value);

		$dst->setConfig($k,$v);
		if($move) $src->removeConfig($key,$value);
	}

	public function transferOptions($block)
	{
		$m	=	isset($block["move"]);
		$i	=	isset($block["import"]) && $block["import"] != $this->getName() ? $block["import"] : false;
		$e	=	isset($block["export"]) && $block["export"] != $this->getName() ? $block["export"] : false;
		$p	=	Amslib_Plugin_Manager::getPlugin($i ? $i : ($e ? $e : false));

		return array($m,$i,$e,$p);
	}

	/**
	 * 	method:	transfer
	 *
	 * 	todo: write documentation
	 */
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
		//	NOTE: 14/11/2013: I made some progress here by the creation of the transferOptions method, cleans up a bunch of repeated noise

		//	Process all the import/export/transfer requests on all resources
		//	NOTE: translators don't support the "move" parameter
		foreach($this->config as $key=>&$block){
			//	NOTE: perhaps I need to add "object" to here in the future? read the notes at the array("object") block below
			if(in_array($key,array("model"))){
			 	//	Models are treated slightly differently because they are singular, not plural
				//	NOTE: maybe in future models will be plural too, perhaps it's better to make it work plurally anyway
				list($m,$i,$e,$p) = $this->transferOptions($block);
				if(!$p) continue;

			 	$s	=	$this;
			 	$sv	=	$s->getConfig($key);
			 	$dv	=	$p->getConfig($key);

			 	if($i) 			$this->transferData($p,$s,$key,$dv,$m);
			 	else if($e) 	$this->transferData($s,$p,$key,$sv,$m);
			}else if(in_array($key,array("object","view","stylesheet","image","javascript","translator"))){
				//	NOTE:	transferring objects by name won't work because in the other plugin it won't
				//			know how to autoload the object from it's class because 99.99% sure the object
				//			being transferred won't be in the path, therefore it won't be autoloadable and
				//			it'll fail
				//	NOTE:	perhaps therefore, we need to transfer objects like we do models, create the
				//			object first and transfer that
				//	NOTE:	Ahhhhh, thats why I documented this above at the top
				foreach(Amslib_Array::valid($block) as $iname=>$item)
				{
					//	NOTE:	I found a bug here I think, or at least, a redundancy
					//			since key CANNOT have the value "value" since the above block doesn't define it
					//			as being valid for this particular section of code
					if(in_array($key,array("value","translator"))) $iname = $item["name"];

					list($m,$i,$e,$p) = $this->transferOptions($item);
			 		if(!$p) continue;

			 		$s	=	$this;
			 		$sv	=	$iname;
			 		$dv	=	$iname;

			 		//	FIXME: I tried to apply this here too, it broke everything I tried :(
			 		//if($e) unset($block[$iname]);

			 		if($i) 			$this->transferData2($p,$s,$key,$dv,$m);
			 		else if($e) 	$this->transferData2($s,$p,$key,$sv,$m);
				}
			}else if(in_array($key,array("value"))){
				foreach(Amslib_Array::valid($block) as $k=>$item)
				{
					list($m,$i,$e,$p) = $this->transferOptions($item);
					if(!$p) continue;

					$s	=	$this;
					$sv	=	$item;
					$dv	=	$item;

					if($e) unset($block[$k]);

					if($i) 			$this->transferData($p,$s,$key,$dv,$m);
					else if($e) 	$this->transferData($s,$p,$key,$sv,$m);
				}
			}else if(in_array($key,array("service"))){
				foreach(Amslib_Array::valid($block) as $item)
				{
					//	import means take "name" route from "plugin" and install it under the "local_name" route
					if($item["action"] == "import"){
						//	TODO	we are processing an xml block named "service" but allowing the service to be a route????
						//	FIXME:	this is obviously fucking stupid....perhaps the block should be called "router" or done
						//			a different way, because this is obviously not the right way
						$r = $item["service"] == "true"
							? Amslib_Router::getService($item["name"],$item["plugin"])
							: Amslib_Router::getRoute($item["name"],$item["plugin"]);

						Amslib_Router::setRoute($item["rename"],$this->getName(),NULL,$r,false);

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

	/**
	 * 	method:	getAttributeArray
	 *
	 * 	todo: write documentation
	 */
	protected function getAttributeArray($node,$array=array())
	{
		foreach($node->attributes as $k=>$v) $array[$k] = $v->nodeValue;

		return $array;
	}

	/**
	 * 	method:	config
	 *
	 * 	todo: write documentation
	 */
	public function config($name,$location)
	{
		$this->isReady	=	false;
		$this->isLoaded	=	false;
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
					case "object":{
						//	NOTE: in the future, objects will disappear, will be split controllers and models
						$child = $xpath->query("*",$node);

						foreach($child as $c){
							$p = $this->getAttributeArray($c);

							$p["value"] = $c->nodeValue;

							$file = $this->getComponent("object",$p["value"]);
							if(file_exists($file)) $p["file"] = $file;

							if(in_array($c->nodeName,array("api","model"))){
								//	NOTE: this kind of assignment means it's possible to only have one API and Model object
								$this->config[$c->nodeName] = $p;
							}else{
								$this->config[$node->nodeName][$p["value"]] = $p;
							}
						}
					}break;

					case "controller":{
						$child = $xpath->query("*",$node);

						//	does $node have "directory" attribute?
						$directory = "{$node->nodeName}s";
						//	does $node have "prefix" attribute?
						$prefix = "";
						//	does $node have "scan" attribute?

						$this->setComponent($node->nodeName,$directory,$prefix);

						foreach($child as $c){
							$p = $this->getAttributeArray($c);

							$p["value"] = $c->nodeValue;

							$file = $this->getComponent($node->nodeName,$p["value"]);
							if(file_exists($file)) $p["file"] = $file;

							if($c->nodeName == "api"){
								//	NOTE: this kind of assignment means it's possible to only have one API
								$this->config[$c->nodeName] = $p;
							}else{
								//	NOTE:	we are overriding this because we want to change the xml and code separately
								//			whilst we transition from one system to another
								$this->config["object"][$p["value"]] = $p;
								//$this->config[$node->nodeName][$p["value"]] = $p;
							}
						}
					}break;

					case "model":{
						$child = $xpath->query("*",$node);

						//	does $node have "directory" attribute?
						$directory = "{$node->nodeName}s";
						//	does $node have "prefix" attribute?
						$prefix = "";
						//	does $node have "scan" attribute?
						//	does $node have "connection" attribute?

						$this->setComponent($node->nodeName,$directory,$prefix);

						//	if this model node has a connection attribute, we need to instruct the system to import
						//	the model from the plugin name held in the value attribute, but we need to construct
						//	an array of data which allows us to know this when processing the data

						foreach($child as $c){
							$p = $this->getAttributeArray($c);

							$p["value"] = $c->nodeValue;

							$file = $this->getComponent($node->nodeName,$p["value"]);

							if(file_exists($file)){
								$p["file"] = $file;
							}else{
								Amslib::errorLog("Model object not found, serious error",$p,$file);
							}

							if($c->nodeName == "connection"){
								//	TODO:	this is a patch whilst I transition to the new [object, controller, model, view]
								//			xml layout, I have to patch the connection node is actually a model node
								//			so the rest of the code will continue to run whilst I upgrade to this way of working
								//			cause also, I have to upgrade all the plugins and then they won't be compatible
								//			with previous versions of the amslib plugin system
								$this->config["model"] = $p;
							}else{
								//	NOTE:	we are overriding this because we want to change the xml and code separately
								//			whilst we transition from one system to another
								$this->config["object"][$p["value"]] = $p;
								//$this->config[$node->nodeName][$p["value"]] = $p;
							}
						}
					}break;

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
				$plugin = false;

				$replace = $node->getAttribute("replace");
				if($replace) Amslib_Plugin_Manager::replacePluginLoad($replace,$node->nodeValue);

				$prevent = $node->getAttribute("prevent");
				if($prevent) Amslib_Plugin_Manager::preventPluginLoad($prevent,$node->nodeValue);

				//	Only process plugins which have no replace or prevent instructions,
				//	these are instructions about loading, not instructions to ACTUALLY load
				if(!$prevent && !$replace){
					$plugin = Amslib_Plugin_Manager::config($node->nodeValue,$this->location);

					if($plugin){
						$this->config["requires"][$node->nodeValue] = $plugin;
					}else{
						Amslib::errorLog("PLUGIN LOAD FAILURE",$node->nodeValue,$location);
					}
				}
			}
		}

		$this->isReady = true;

		//	Now the configuration data is loaded, initialise the plugin with any custom requirements
		$this->initialisePlugin();

		return $this->isReady;
	}

	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	public function load()
	{
		if($this->isLoaded) return $this->api;

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

			$this->isLoaded = true;

			return $this->api;
		}

		//	TODO: This needs to be better than "OMG WE ARE ALL GONNA DIE!!!"
		print(get_class($this)."::load(): PACKAGE FAILED TO OPEN: file[$this->filename]<br/>");

		return false;
	}

	/**
	 * 	method:	setConfig
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	getConfig
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	removeConfig
	 *
	 * 	todo: write documentation
	 */
	public function removeConfig($type,$id=NULL)
	{
		if($id === NULL){
			unset($this->config[$type]);
		}else if(is_array($type)){
			unset($this->config[$type][$id]);
		}
	}

	/**
	 * 	method:	getLocation
	 *
	 * 	todo: write documentation
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * 	method:	getName
	 *
	 * 	todo: write documentation
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * 	method:	getAPI
	 *
	 * 	todo: write documentation
	 */
	public function getAPI()
	{
		return $this->api;
	}

	/**
	 * 	method:	setAPI
	 *
	 * 	todo: write documentation
	 */
	public function setAPI($api)
	{
		$this->api = $api;
	}

	/**
	 * 	method:	getModel
	 *
	 * 	todo: write documentation
	 */
	public function getModel()
	{
		return $this->api->getModel();
	}

	/**
	 * 	method:	setModel
	 *
	 * 	todo: write documentation
	 */
	public function setModel($model)
	{
		$this->api->setModel($model);
	}

	/**
	 * 	method:	setPath
	 *
	 * 	todo: write documentation
	 */
	static protected function setPath($name,$path)
	{
		self::$path[$name] = $path;
	}

	/**
	 * 	method:	expandPath
	 *
	 * 	todo: write documentation
	 */
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
}
