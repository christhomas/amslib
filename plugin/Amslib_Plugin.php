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
	protected $isConfigured;

	//	The load state of the plugin is true/false depending on whether it has been loaded
	protected $isLoaded;

	//	The name of the plugin
	protected $name;

	//	The location of the plugin on the filesystem
	protected $location;

	//	The API object (typically an Amslib_MVC class) created by the plugin
	protected $api;

	//	The configuration data loaded from the system
	protected $data;

	//	The configuration source, where all the data is retrieved from
	protected $source;

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
		$this->api->setModel($this->createObject($this->data["model"],true,true));

		//	If the API object is not valid, don't continue to process
		if(!$this->api) return false;

		$keys = array("object","value","translator","view","service","font","image","javascript","stylesheet");

		foreach($keys as $k=>$v){
			//	Don't process any keys which are not set, empty or the required method to process it is not available
			if(!isset($this->data[$v]) || empty($this->data[$v]) || !method_exists($this->api,"set".ucwords($v))){
				continue;
			}

			$callback	=	"set".ucwords($v);
			$func		=	array($this->api,$callback);

			foreach($this->data[$v] as $name=>$c){
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
		//	invalid requests get returned false
		if(!$object || empty($object)) return false;

		//	existing objects just get returned from their cache
		if(isset($object["cache"])) return $object["cache"];

		//	otherwise, we go through the process of loading them, first of all, make sure the object file is in the system
		if(isset($object["file"]))	Amslib::requireFile($object["file"],array("require_once"=>true));

		//	Create the generic string for all the errors
		$error =  "FATAL ERROR(".__METHOD__.") Could not __ERROR__ for plugin '{$this->name}'<br/>";

		//	false by default to signal an error in creation
		$cache = false;

		if(isset($object["value"]) && class_exists($object["value"])){
			if($singleton){
				if(method_exists($object["value"],"getInstance")){
					$cache = call_user_func(array($object["value"],"getInstance"));
				}else{
					//	your object was not a compatible singleton, we need to find the getInstance method
					die(str_replace("__ERROR__","find the getInstance method in the API object '{$object["value"]}'",$error));
				}
			}else{
				//	yay! we have a proper object
				$cache = new $object["value"];
			}
		}else if($dieOnError){
			//	The class was not found, we cannot continue
			//	NOTE:	I am not sure why we cannot continue here, it might not be something
			//			critical and yet we're stopping everything
			//	NOTE:	It's hard to die here because perhaps there is a better way of not dying everytime an object
			//			which is requested that doesn't exist won't load, we immediately kill everything
			//			perhaps a nicer way would be to notify the system of the error, but build better code
			//			so that if an object doesnt exist, we could use a dummy object with a __call interface
			//			and register all the methods as returning false, therefore hiding the missing object
			//			but not killing the process, but handling all the failures which happen and properly written code
			//			which respects the methods that return false, will keep functioning but failing to operating,
			//			although they will not crash
			$error = str_replace("__ERROR__","find class using configuration ".Amslib::var_dump($object)."'",$error);
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
		$api = $this->createObject($this->data["api"],true,true);

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
				//	NOTE: I don't think I actually use __CURRENT_PLUGIN__...or do I? where is this
				//	actually used? or even which code actually creates anything using this __CURRENT_PLUGIN__ tag
				$location = str_replace("__CURRENT_PLUGIN__",$this->location,$config["location"]);
				$location = Amslib_Plugin::expandPath($location);
			}break;

			case "database":{
				$database = $this->data["model"]["value"];
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
		$this->data = array("api"=>false,"model"=>false,"translator"=>false,"requires"=>false,"value"=>false);

		//	This stores where all the types components are stored as part of the application
		//	WARNING: object is soon to be deprecated
		$this->setComponent("object",		"objects",		"");
		$this->setComponent("controller",	"controllers",	"Ct_");
		$this->setComponent("model",		"models",		"Mo_");
		$this->setComponent("view",			"views",		"Vi_");
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
		foreach(Amslib_Array::valid($this->data["requires"]) as $name=>$plugin){
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
		foreach($this->data as $key=>&$block){
			//	NOTE: perhaps I need to add "object" to here in the future? read the notes at the array("object") block below
			if(in_array($key,array("model"))){
			 	//	Models are treated slightly differently because they are singular, not plural
				//	NOTE: maybe in future models will be plural too, perhaps it's better to make it work plurally anyway
				list($m,$i,$e,$p) = $this->transferOptions($block);
				if(!$p || (!$i && !$e)) continue;

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
			 		if(!$p || (!$i && !$e)) continue;

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
					if(!$p || (!$i && !$e)) continue;

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

	public function configAPI($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		//	After we have this object, we must create it
		//	API Objects therefore cannot do anything in their constructor
		//	We should modify the API objects so they do their "work" in the "initialiseObject" method
		//	Therefore we can create the object and then ask it to "configObject" first passing it the config object
		//	Then once it has the $configuration object, it can ask for it's custom data

		$a			= $array["attr"];
		$a["value"]	= $array["value"];

		$file = $this->getComponent("object",$array["value"]);
		if(file_exists($file)) $a["file"] = $file;

		$this->data["api"] = $a;
	}

	public function configObject($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		//	NOTE: in the future, objects will disappear, will be split controllers and models
		//	NOTE: this isn't actually true, because objects are sometimes not controllers or models, but just simple objects

		$a			= $array["attr"];
		$a["value"] = $array["value"];

		$file = $this->getComponent("object",$a["value"]);
		if(file_exists($file)) $a["file"] = $file;

		$this->data["object"][$a["value"]] = $a;
	}

	public function configController($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		//	does $node have "directory" attribute?
		$directory = "controllers";
		//	does $node have "prefix" attribute?
		$prefix = "";
		//	does $node have "scan" attribute?
		$this->setComponent("controller",$directory,$prefix);

		$a			= $array["attr"];
		$a["value"] = $array["value"];

		$file = $this->getComponent("controller",$array["value"]);

		if(file_exists($file)){
			$a["file"] = $file;
		}else{
			//Amslib::errorLog
			print("Controller object not found, serious error: ".Amslib::var_dump(array("array"=>$array,"a"=>$a,"file"=>$file),true));
		}

		//	NOTE:	we are overriding this because we want to change the xml and code separately
		//			whilst we transition from one system to another
		$this->data["object"][$a["value"]] = $a;
		//$this->data[$node->nodeName][$p["value"]] = $p;
	}

	/**
	 *	method:	configModelConnection
	 *
	 *	todo:	code documentation
	 *
	 *	note:	This code is almost 100% copied from configModel(), see there for the same code documentation
	 *	note:	I am doing this duplicated like this so I avoid pre-optimisation before I see the final working code
	 */
	public function configModelConnection($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		//	if this model node has a connection attribute, we need to instruct the system to import
		//	the model from the plugin name held in the value attribute, but we need to construct
		//	an array of data which allows us to know this when processing the data
		//	-	this appears to be out of date now, since we have a <connection> xml node which does this,
		//		it's not an attribute anymore
		$directory = "models";
		$prefix = "";
		$this->setComponent("model",$directory,$prefix);

		$a			= $array["attr"];
		$a["value"]	= $array["value"];

		$file = $this->getComponent("model",$array["value"]);

		if(file_exists($file)){
			$a["file"] = $file;
		}else{
			//Amslib::errorLog
			print("Model Connection object not found, serious error: ".Amslib::var_dump(array("array"=>$array,"a"=>$a,"file"=>$file),true));
		}

		//	TODO:	this is a patch whilst I transition to the new [object, controller, model, view]
		//			xml layout, I have to patch the connection node is actually a model node
		//			so the rest of the code will continue to run whilst I upgrade to this way of working
		//			cause also, I have to upgrade all the plugins and then they won't be compatible
		//			with previous versions of the amslib plugin system
		$this->data["model"] = $a;
	}

	public function configModel($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		//	does $node have "directory" attribute?
		//	-	this would allow us to customise where on the filesystem the files would be loaded
		$directory = "models";
		//	does $node have "prefix" attribute?
		//	-	this would allow us to customise the filename format for each of the files being loaded
		$prefix = "";
		//	does $node have "scan" attribute?
		//	-	the scan attribute was so you'd just load all the files from a particular directory
		//		which matched the filename pattern being loaded, so you didn't have to constantly
		//		update it when you added new objects ot the system
		//	-	however the scan attribute allows you to make more mistakes by leaving old files on
		//		the system which are used by accident and you'd have to manually detect these errors
		//	does $node have "connection" attribute?
		//	-	I believe this was deprecated in favour or just having an extra xml node <connection>
		$this->setComponent("model",$directory,$prefix);

		$a			= $array["attr"];
		$a["value"] = $array["value"];

		$file = $this->getComponent("model",$array["value"]);

		if(file_exists($file)){
			$array["file"] = $file;
		}else{
			//Amslib::errorLog
			print("Model object not found, serious error: ".Amslib::var_dump(array("array"=>$array,"a"=>$a,"file"=>$file),true));
		}

		$this->data["object"][$array["value"]] = $array;
	}

	public function configView($name,$array)
	{
		$a = array_merge(array("id"=>$array["value"],"value"=>$array["value"]),$array["attr"]);

		$a["value"] = $this->getComponent("view",$array["value"]);

		$this->data["view"][$a["id"]] = $a;

		//print(__METHOD__.": plugin[".$this->getName()."] => ".Amslib::var_dump($this->data["view"],true));
	}

	public function configService($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		$a				= $array["attr"];
		$a["action"]	= $name;
		$a["rename"]	= $array["value"];

		//	You require at minimum these two attributes, or cannot accept it
		if(!isset($a["plugin"]) || !isset($a["name"])) continue;
		//	If the rename attribute doesn't exist or is empty, replace it with the name attribute
		if(!isset($a["rename"]) || !$a["rename"] || strlen($a["rename"])){
			$a["rename"] = $a["name"];
		}

		$this->data["service"][] = $a;
	}

	public function configJavascript($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		$a = $array["attr"];

		$absolute	=	isset($a["absolute"]) ? true : false;
		$a["value"]	=	$this->findResource($array["value"],$absolute);

		//	If a valid id exists, insert the configuration
		if(isset($a["id"])) $this->data["javascript"][$a["id"]] = $a;
	}

	public function configStylesheet($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		$a			=	$array["attr"];
		$absolute	=	isset($a["absolute"]) ? true : false;
		$a["value"]	=	$this->findResource($array["value"],$absolute);

		//	If a valid id exists, insert the configuration
		if(isset($a["id"])) $this->data["stylesheet"][$a["id"]] = $a;
	}

	public function configImage($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		$a = $array["attr"];

		$absolute	=	isset($a["absolute"]) ? true : false;
		$a["value"]	=	$this->findResource($array["value"],$absolute);

		//	If a valid id exists, insert the configuration
		if(isset($a["id"])) $this->data["image"][$a["id"]] = $a;
	}

	public function configFont($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		foreach($array["child"] as $font){
			$a			=	$font["attr"];
			$a["value"]	=	trim($font["value"]);
			$a["type"]	=	$font["tag"];
			if(!isset($a["autoload"])) $a["autoload"] = NULL;

			//	If a valid id exists, insert the configuration
			if(isset($a["id"])) $this->data["font"][$a["id"]] = $a;
		}
	}

	public function configTranslator($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		//	Import all the translator parameters, such as import/export
		$a = $array["attr"];

		foreach($array["child"] as $c){
			if($c["tag"] == "language"){
				$a[$c["tag"]][] = $c["value"];
			}else{
				$a[$c["tag"]] = $c["value"];
			}
		}

		//	Test all required data is present, if so, add the translator to be configured later
		if(	Amslib_Array::hasKeys($a,array("name","type","location","language")) ||
					Amslib_Array::hasKeys($a,array("name","import")) ||
					Amslib_Array::hasKeys($a,array("name","export")))
		{
			$this->data["translator"][] = $a;
		}
	}

	public function configValue($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		$a = $array["attr"];

		foreach($array["child"] as $c){
			$this->data["value"][] = array_merge(
					array("name"=>$c["tag"],"value"=>$c["value"]),
					$a
			);
		}
	}

	public function configPath($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		foreach($array["child"] as $c){
			$v = Amslib_Plugin::expandPath($c["value"]);

			if($c["tag"] == "include"){
				Amslib::addIncludePath(Amslib_File::absolute($v));
			}else{
				Amslib_Plugin::setPath($c["tag"],$v);

				switch($c["tag"]){
					case "plugin":{
						Amslib_Plugin_Manager::addLocation($v);
					}break;

					case "docroot":{
						Amslib_File::documentRoot($v);
					}break;
				}
			}
		}
	}

	public function configPlugin($name,$array)
	{
		//print(__METHOD__.": plugin[".$this->getName()."], name[$name] => ".Amslib::var_dump($array,true));

		if($this->getName() != $array["value"]){
			$a = $array["attr"];

			$replace = isset($a["replace"]) ? $a["replace"] : NULL;;
			if($replace) Amslib_Plugin_Manager::replacePluginLoad($replace,$array["value"]);

			$prevent = isset($a["prevent"]) ? $a["prevent"] : NULL;
			if($prevent) Amslib_Plugin_Manager::preventPluginLoad($prevent,$array["value"]);

			//	Only process plugins which have no replace or prevent instructions,
			//	these are instructions about loading, not instructions to ACTUALLY load
			if($prevent || $replace) return;

			//	Since this plugin is not replaced, nor prevented from loading, lets load it!!
			$location	=	$this->getLocation();
			$plugin		=	Amslib_Plugin_Manager::config($array["value"],$location);

			if($plugin){
				$this->data["requires"][$array["value"]] = $plugin;
			}else{
				Amslib::errorLog("PLUGIN LOAD FAILURE: ".$array["value"],$location);
			}
		}
	}

	/**
	 * 	method:	config
	 *
	 * 	todo: write documentation
	 */
	public function config($name)
	{
		$this->isConfigured	=	false;
		$this->isLoaded		=	false;
		$this->name			=	$name;

		//print("source = ".Amslib::var_dump(array($this->getName(),$this->source),true));

		//	NOTE: this obviously won't work if you try to use a database configuration object
		Amslib_Router::load($this->source->getValue("filename"),"xml",$this->getName());

		//	NOTE:	I chose to make this an array because I could allow it to be
		//			configured and then run it through a generic process step below,
		//			allowing flexibility in how these sections are loaded and processed
		$selectors = array(
			"package > object api"			=>	array($this,"configAPI"),
			"package > object name"			=>	array($this,"configObject"),
			"package > controller name"		=>	array($this,"configController"),
			"package > model connection"	=>	array($this,"configModelConnection"),
			"package > model name"			=>	array($this,"configModel"),
			"package > view name"			=>	array($this,"configView"),
			"package > service import"		=>	array($this,"configService"),
			"package > service export"		=>	array($this,"configService"),
			"package > javascript file"		=>	array($this,"configJavascript"),
			"package > stylesheet file"		=>	array($this,"configStylesheet"),
			"package > image file"			=>	array($this,"configImage"),
			"package > font"				=>	array($this,"configFont"),
			"package > translator"			=>	array($this,"configTranslator"),
			"package > value"				=>	array($this,"configValue"),
			"package > path"				=>	array($this,"configPath"),
			"package > requires plugin"		=>	array($this,"configPlugin")
		);

		$this->source->prepare();
		foreach($selectors as $s=>$c){
			$this->source->process($s,$c);
		}

		//	NOTE:	There are no implementation of this method as yet
		//	NOTE:	we need to try creating one for the Power_Panel_Application, for the navigation items
		//	NOTE: 	Or perhaps we could start by creating notifications and registering the Amstudios_Notifications plugin as the handler?
		if($this->api){
			$this->api->configObject($this->source);
		}

		$this->deleteConfigSource();
		//print("TESTING, config[".$this->getName()."] = ".Amslib::var_dump($this->data,true));

		$this->isConfigured = true;

		//	Now the configuration data is loaded, initialise the plugin with any custom requirements
		$this->initialisePlugin();

		return $this->isConfigured;
	}

	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	public function load()
	{
		if($this->isLoaded) return $this->api;

		if($this->isConfigured)
		{
			//	Process all child plugins before the parents
			foreach(Amslib_Array::valid($this->data["requires"]) as $plugin) $plugin->load();

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

		return false;
	}

	public function setConfigSource($config=NULL)
	{
		//	If you don't provide a configuration object, provide a default one with everything default
		if(!$config){
			$config = new Amslib_Plugin_Config_XML();
		}

		if($config){
			$config->setValue("location",$this->getLocation());

			if($config->getStatus()){
				$this->source = $config;
			}else{
				//	The configuration source was setup, however it failed to return that it was ok
				//	This could be that the actual data source is missing?
				//	TODO: This needs to be better than "OMG WE ARE ALL GONNA DIE!!!"
				$filename = $config->getValue("filename");
				print("[".get_class($this)."/".$this->getName()."]::load(): PACKAGE FAILED TO OPEN: file[$filename]<br/>");
			}
		}else{
			//	There is no valid configuration object created or requested
			die("NO CONFIGURATION OBJECT DETECTED");
		}
	}

	public function deleteConfigSource()
	{
		$this->source = NULL;
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
			$this->data[$key] = Amslib_Array::valid($this->data[$key]);
			foreach($this->data[$key] as &$v){
				if($v["name"] == $value["name"] && !isset($v["export"]) && !isset($v["import"])){

					$v["value"] = $value["value"];

					return;
				}
			}

			//	The value didnt already exist, so we must create it
			$this->data[$key][] = $value;
		}else if(is_string($key)){
			$this->data[$key] = $value;
		}else{
			$this->data[$key[0]][$key[1]] = $value;
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
				foreach($this->data[$key] as $t){
					if($t["name"] == $name){
						if(isset($t["type"]))	$this->createTranslator($t);
						if(isset($t["cache"]))	return $t;
					}
				}
			}break;

			case "model":{
				$this->createObject($this->data[$key]);
				return $this->data[$key];
			}break;

			default:{
				if($name === NULL){
					return isset($this->data[$key]) ? $this->data[$key] : false;
				}else{
					if(is_array($name)) $name = current($name);
					$name = (string)$name;

					return isset($this->data[$key][$name]) ? $this->data[$key][$name] : false;
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
			unset($this->data[$type]);
		}else if(is_array($type)){
			unset($this->data[$type][$id]);
		}
	}

	/**
	 * 	method:	setLocation
	 *
	 * 	todo: write documentation
	 */
	public function setLocation($location)
	{
		$this->location = $location;
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
}
