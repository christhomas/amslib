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

	const ERROR_API_INVALID				=	"api invalid, cannot continue";
	const ERROR_IMAGE_ID_INVALID		=	"image invalid, no id attribute";
	const ERROR_FONT_ID_INVALID			=	"font invalid, no id attribute";
	const ERROR_PLUGIN_SOURCE_INVALID	=	"plugin source was not valid";

	/**
	 * 	method:	setComponent
	 *
	 * 	todo: write documentation
	 */
	protected function setComponent($component,$directory,$prefix)
	{
		$this->prefix[$component] = array($directory,$prefix);
	}

	/**
	 * 	method:	getComponent
	 *
	 * 	todo: write documentation
	 */
	protected function getComponent($component,$name=NULL)
	{
		if(!isset($this->prefix[$component])) return NULL;

		if(!$name) return $this->prefix[$component];

		return Amslib_String::reduceSlashes(
			$this->getLocation()."/".implode("/",$this->prefix[$component])."$name.php"
		);
	}

	/**
	 * 	method:	findResource
	 *
	 * 	todo: write documentation
	 */
	protected function findResource($resource,$absolute=false)
	{
		//	If the resource has an attribute "absolute" don't process it, return it directly
		//	An absolute resource will also have a protocol token inside the string
		if($absolute || strpos($resource,"://") !== false) return $resource;

		$location	=	$this->getLocation();

		//	PREPARE THE STRING: expand any parameters inside the resource name
		$resource	=	self::expandPath($resource);
		//	NOTE: we have to do this to get around a bug in lchop/rchop
		$query		=	($str=Amslib_String::lchop($resource,"?")) == $resource ? false : $str;
		$resource	=	Amslib_String::rchop($resource,"?");
		$output		=	false;

		//	TEST 1:	look in the package directory for the file
		$test1 = Amslib_File::reduceSlashes("$location/$resource");
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
		//	NOTE:	Why do we create the model here and not inside the switch
		//			statement below? but then comment out the code in the
		//			switch statement? I'm not sure why I did that...
		$this->api->setModel($this->createObject($this->data["model"],true,true));

		//	If the API object is not valid, don't continue to process
		if(!$this->api){
			Amslib_Debug::log(self::ERROR_API_INVALID,$this->api,$this->getName());
			return false;
		}

		foreach(Amslib_Array::valid($this->data) as $key=>$value)
		{
			$prevent = array("api","requires","model","service");

			if(in_array($key,$prevent)) continue;

			$callback = array($this->api,"set".ucwords($key));

			if(!is_callable($callback)) continue;

			$params = array();

			foreach(Amslib_Array::valid($value) as $k=>$v)
			{
				//	NOTE:	perhaps this post-processing should be moved to
				//			where the data is being imported and stored then
				//			you wouldn't need to do most of this code and the
				//			other code is already very specific anyway, so it's
				//			just removing unnecessary complexity for no reason.
				switch($key){
					case "view":{
						$params = array($k,$v["value"]);
					}break;

					case "object":{
						if(!isset($v["file"])){
							Amslib_Debug::log("WARNING: object being configured and has no file array index",$key,$k,$v);

							continue;
						}

						$params = array($k,$v["file"]);
					}break;

					case "value":{
						$params = array($v["name"],$v["value"]);
					}break;

					case "model":{
						//	NOTE: 25/01/2014: why did I comment this out ?
						//	We need to change the array layout to make models
						//	a plural of one (lulz irony) Then we can reenable
						//	this code, it'll be simpler, but not ready to work yet.
						//$params = array($this->createObject($v,true,true));
					}break;

					case "translator":{
						$translator	=	$this->createTranslator($v);

						if(!$translator){
							Amslib_Debug::log("WARNING: a translator was requested, but could not be created",$v);
							continue;
						}

						$this->installLanguages($v["name"],$v);

						$params = array($v["name"],$translator);
					}break;

					case "image":{
						$params = array($k,$v);
					}break;

					case "font":{
						$type		=	isset($v["type"])		? $v["type"]		: NULL;
						$value		=	isset($v["value"])		? $v["value"]		: NULL;
						$condition	=	isset($v["condition"])	? $v["condition"]	: NULL;
						$autoload	=	isset($v["autoload"])	? $v["autoload"]	: NULL;
						$position	=	isset($v["position"])	? $v["position"]	: NULL;

						$params = array($type,$k,$value,$autoload,$condition,$position);
					}break;

					case "stylesheet":{
						//	NOTE (25/08/2014):	I'm not sure why I wrote this protection code,
						//						maybe it's old logic not needed anymore?
						if(is_object($v)){
							print("key[$key] = ".Amslib_Debug::pdump(true,$v));
							print("callback = ".Amslib_Debug::pdump(true,array($callback[0]->getName(),$callback[1])));
							print("value was object, cannot continue");
							continue;
						}

						$value		=	isset($v["value"])		? $v["value"]		: NULL;
						$condition	=	isset($v["condition"])	? $v["condition"]	: NULL;
						$autoload	=	isset($v["autoload"])	? $v["autoload"]	: NULL;
						$media		=	isset($v["media"])		? $v["media"]		: NULL;
						$position	=	isset($v["position"])	? $v["position"]	: NULL;

						$params		=	array($k,$value,$condition,$autoload,$media,$position);
					}break;

					case "javascript":{
						//	NOTE (25/08/2014):	I'm not sure why I wrote this protection code,
						//						maybe it's old logic not needed anymore?
						if(is_object($v)){
							print("key[$key] = ".Amslib_Debug::pdump(true,$v));
							print("callback = ".Amslib_Debug::pdump(true,array($callback[0]->getName(),$callback[1])));
							print("value was object, cannot continue");
							continue;
						}

						$value		=	isset($v["value"])		? $v["value"]		: NULL;
						$condition	=	isset($v["condition"])	? $v["condition"]	: NULL;
						$autoload	=	isset($v["autoload"])	? $v["autoload"]	: NULL;
						$position	=	isset($v["position"])	? $v["position"]	: NULL;

						$params		=	array($k,$value,$condition,$autoload,$position);
					}break;

					default:{
						Amslib_Debug::log("WARNING: unrecognised key '$key' being processed, ignoring");
						continue;
					}break;
				}

				if(!empty($params)){
					call_user_func_array($callback,$params);
				}else{
					Amslib_Debug::log("WARNING: {$callback[1]} called with empty parameters",$k,$v);
				}
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
					$message = str_replace("__ERROR__","find the getInstance method in the API object '{$object["value"]}'",$error);
					Amslib_Debug::log($message);
					die($message);
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
			$error = str_replace("__ERROR__","find class using configuration ".Amslib_Debug::dump($object)."'",$error);
			Amslib_Debug::log("stack_trace",$error,$object);
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

		//	Create the language translator object and insert it into the api
		$translator = new Amslib_Translator($config["type"],$config["name"]);
		$translator->addLanguage($config["language"]);

		switch($config["type"]){
			case "xml":{
				$location = self::expandPath($config["directory"]);

				$translator->setConfig("directory",$location);
			}break;

			case "database":{
				$translator->setConfig("database",		$this->data["model"]["value"]);
				$translator->setConfig("translation",	$config["translation"]);
				$translator->setConfig("lang",			$config["lang"]);
			}break;
		}

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
				if(!$object){
					Amslib_Debug::log("we found a translator which was not valid",$name,$object);
					continue;
				}

				//	Obtain the language the system should use when printing text
				$object->setLanguage(Amslib_Plugin_Application::getLanguage($name));
				$object->load();
			}

			$api->finalise();
		}else{
			Amslib_Debug::log("plugin not found?",$api);
		}
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		$this->data = array_fill_keys(array("api","model","translator","requires","value"),false);

		//	This stores where all the types components are stored as part of the application
		//	WARNING: object is soon to be deprecated
		//	NOTE: this isnt true, i am not, at the end planning to deprecate object
		$this->setComponent("object",		"objects",		"");
		$this->setComponent("controller",	"controllers",	"Ct_");
		$this->setComponent("model",		"models",		"Mo_");
		$this->setComponent("view",			"views",		"Vi_");
	}

	public function setComponentConfig($name,$array,$object)
	{
		$component = $this->getComponent($name);

		if(isset($array["attr"]["directory"])){
			$component[0] = $array["attr"]["directory"];
		}

		if(isset($array["attr"]["prefix"])){
			$component[1] = $array["attr"]["prefix"];
		}

		$this->setComponent($name,$component[0],$component[1]);
	}

	public function configAPI($name,$array,$object)
	{
		//	After we have this object, we must create it
		//	API Objects therefore cannot do anything in their constructor
		//	We should modify the API objects so they do their "work" in the "initialiseObject" method
		//	Therefore we can create the object and then ask it to "configObject" first passing it the config object
		//	Then once it has the $configuration object, it can ask for it's custom data

		$a			= $array["attr"];
		$a["value"]	= $array["value"];

		$file = $this->getComponent("object",$array["value"]);
		if(file_exists($file)){
			$a["file"] = $file;
		}else{
			$a["error"] = "file doesn't exist '$file'";
		}

		$this->data["api"] = $a;

		$this->api = $this->createAPI();
	}

	public function configObject($name,$array,$object)
	{
		//	NOTE: in the future, objects will disappear, will be split controllers and models
		//	NOTE: this isn't actually true, because objects are sometimes not controllers or models, but just simple objects
		//	NOTE: can we export objects between plugins?

		$a			= $array["attr"];
		$a["value"] = $array["value"];

		$file = $this->getComponent("object",$a["value"]);
		if(file_exists($file)) $a["file"] = $file;

		$this->data["object"][$a["value"]] = $a;
	}

	public function configController($name,$array,$object)
	{
		//	NOTE: can we export controllers between plugins

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
			Amslib_Debug::log("Controller object not found, serious error: ",$array,$a,$file);
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
	public function configModelConnection($name,$array,$object)
	{
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

		if(isset($a["import"])){
			$this->addImport($a["import"],$this,"model",$a);
		}else if(isset($a["export"])){
			$this->addExport($this,$a["export"],"model",$a);
		}else if(file_exists($file)){
			$a["file"] = $file;

			//	TODO:	this is a patch whilst I transition to the new [object, controller, model, view]
			//			xml layout, I have to patch the connection node is actually a model node
			//			so the rest of the code will continue to run whilst I upgrade to this way of working
			//			cause also, I have to upgrade all the plugins and then they won't be compatible
			//			with previous versions of the amslib plugin system
			$this->data["model"] = $a;
		}else{
			Amslib_Debug::log("Model Connection object not found, serious error: ", $array,$a,$file);
		}
	}

	public function configModel($name,$array,$object)
	{
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

		if(isset($a["import"])){
			$this->addImport($a["import"],$this,"model",$a);
		}else if(isset($a["export"])){
			$this->addExport($this,$a["export"],"model",$a);
		}else if(file_exists($file)){
			$a["file"] = $file;

			$this->data["object"][$array["value"]] = $a;
		}else{
			Amslib_Debug::log("Model object not found, serious error: ",$array,$a,$file);
		}
	}

	public function configView($name,$array,$object)
	{
		$a = array_merge(array("id"=>$array["value"],"value"=>$array["value"]),$array["attr"]);

		$a["value"] = $this->getComponent("view",$array["value"]);

		if(isset($a["import"])){
			$this->addImport($a["import"],$this,"view",$a);
		}else if(isset($a["export"])){
			$this->addExport($this,$a["export"],"view",$a);
		}else{
			$this->data["view"][$a["id"]] = $a;
		}
	}

	public function configService($name,$array,$object)
	{
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

	public function configJavascript($name,$array,$object)
	{
		$a = $array["attr"];

		$absolute	=	isset($a["absolute"]) ? true : false;
		$a["value"]	=	$this->findResource($array["value"],$absolute);

		//	If a valid id exists, insert the configuration
		if(isset($a["id"])) $this->data["javascript"][$a["id"]] = $a;
	}

	public function configStylesheet($name,$array,$object)
	{
		$a			=	$array["attr"];
		$absolute	=	isset($a["absolute"]) ? true : false;
		$a["value"]	=	$this->findResource($array["value"],$absolute);

		//	If a valid id exists, insert the configuration
		if(isset($a["id"])) $this->data["stylesheet"][$a["id"]] = $a;
	}

	public function configImage($name,$array,$object)
	{
		$a = $array["attr"];

		//	If there is no valid "id" element, skip this item
		if(!isset($a["id"])){
			Amslib_Debug::log(ERROR_IMAGE_ID_INVALID,$array);
			return;
		}

		if(isset($a["import"])){
			$this->addImport($a["import"],$this,"image",$a);
		}else{
			$absolute	=	isset($a["absolute"]) ? true : false;
			$a["value"]	=	$this->findResource($array["value"],$absolute);

			if(isset($a["export"])){
				$this->addExport($this,$a["export"],"image",$a);
			}else{
				$this->data["image"][$a["id"]] = $a["value"];
			}
		}
	}

	public function configFont($name,$array,$object)
	{
		if(!array_key_exists("child",$array)) return;

		foreach(Amslib_Array::valid($array["child"]) as $font){
			$a			=	$font["attr"];
			$a["value"]	=	trim($font["value"]);
			$a["type"]	=	$font["tag"];

			//	If there is no valid "id" element, skip this item
			if(!isset($a["id"])){
				Amslib_Debug::log(self::ERROR_FONT_ID_INVALID,$font);
				continue;
			}

			if(isset($a["import"])){
				$this->addImport($a["import"],$this,"font",$a);
			}else if(isset($a["export"])){
				$this->addExport($this,$a["export"],"font",$a);
			}else{
				$this->data["font"][$a["id"]] = $a;
			}
		}
	}

	public function configTranslator($name,$array,$object)
	{
		if(!array_key_exists("child",$array)) return;

		//	Import all the translator parameters, such as import/export
		$a = $array["attr"];

		foreach(Amslib_Array::valid($array["child"]) as $c){
			if($c["tag"] == "language"){
				$a[$c["tag"]][] = $c["value"];
			}else{
				$a[$c["tag"]] = $c["value"];
			}
		}

		//	There are three valid combinations of array keys, one has to succeed to do anything useful
		if(Amslib_Array::hasKeys($a,array("name","import"))){
			$this->addImport($a["import"],$this,"translator",$a);
		}else if(Amslib_Array::hasKeys($a,array("name","export"))){
			$this->addExport($this,$a["export"],"translator",$a);
		}else if(Amslib_Array::hasKeys($a,array("name","type","language"))){
			//	One of these has to succeed
			$valid_xml	=	Amslib_Array::hasKeys($a,array("directory"));
			$valid_db	=	Amslib_Array::hasKeys($a,array("translation","lang"));

			if($valid_xml || $valid_db){
				$this->data["translator"][$a["name"]] = $a;
			}
		}
	}

	/**
	 * 	method: configValue
	 *
	 * 	Read the XML array of data and add this to the plugin data array, or import/export it somewhere else
	 *
	 * 	notes:
	 * 		-	I would like in the future to support handling import/export information at the value level
	 * 			because I think this would make the XML definitions more compact and easier to write
	 */
	public function configValue($name,$array,$object)
	{
		if(!array_key_exists("child",$array)) return;

		$a = $array["attr"];

		$value = array();

		foreach(Amslib_Array::valid($array["child"]) as $c){
			$value[] = array("name"=>$c["tag"],"value"=>$c["value"]);
		}

		if(isset($a["import"])){
			$this->addImport($a["import"],$this,"value",$value);
		}else if(isset($a["export"])){
			$this->addExport($this,$a["export"],"value",$value);
		}else{
			$this->data["value"] = $value;
		}
	}

	public function configPath($name,$array,$object)
	{
		if(!array_key_exists("child",$array)) return;

		foreach(Amslib_Array::valid($array["child"]) as $c){
			$v = Amslib_Plugin::expandPath($c["value"]);

			if($c["tag"] == "include"){
				Amslib::addIncludePath(Amslib_File::absolute($v));
			}else{
				Amslib_Website::setPath($c["tag"],$v);

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

	public function configPlugin($name,$array,$object)
	{
		if($this->getName() == $array["value"]){
			return;
		}

		$a = $array["attr"];

		$alias = isset($a["alias"]) ? $a["alias"] : NULL;
		if($alias) Amslib_Plugin_Manager::setPluginAlias($array["value"],$alias);

		$replace = isset($a["replace"]) ? $a["replace"] : NULL;
		if($replace) Amslib_Plugin_Manager::setPluginReplace($replace,$array["value"]);

		//	NOTE: we should deprecate "prevent" in favour of "block" in the future
		$prevent	= isset($a["prevent"]) ? $a["prevent"] : NULL;
		$block		= isset($a["block"]) ? $a["block"] : NULL;
		if($prevent || $block) Amslib_Plugin_Manager::setPluginBlock($array["value"]);

		//	Only process plugins which have no replace or prevent instructions,
		//	these are instructions about loading, not instructions to ACTUALLY load
		if($prevent || $block || $replace) return;

		//	If the plugin is already created, don't attempt to configure or load it again
		$plugin = Amslib_Plugin_Manager::getPlugin($array["value"]);

		if($plugin) return;

		try{
			//	Since this plugin is not replaced, nor prevented from loading, lets load it!!
			$location	=	$this->getLocation();
			$plugin		=	Amslib_Plugin_Manager::config($array["value"],$location);
		}catch(Exception $e){
			Amslib_Debug::log("Exception occured whilst configuring plugin, message = ",$e->getMessage());
		}

		if($plugin){
			$this->data["requires"][$array["value"]] = $plugin;
		}else{
			Amslib_Debug::log("PLUGIN LOAD FAILURE: ".$array["value"],$location);
		}
	}

	public function configRouter($name,$array,$object)
	{
		$data = $array["attr"];

		if(!isset($data["type"]) || !in_array($data["type"],array("xml","database"))){
			$data["type"] = "xml";
		}

		switch($data["type"]){
			case "xml":{
				//	NOTE: is it possible in the future to ask the router to add a parameter by default to each route
				//	NOTE: if it was, I wouldn't have to add all the time the plugin to the routes that belong to this configuration
				//	NOTE: which would solve a silly problem which has happened for a long time, but only really happens
				//			in situations like the power panel, where the route has to know which plugin was responsible
				//	NOTE: however, this is not true JUST of the power panel, it would matter for ANY plugin based architecture
				//			where the system would render a plugin, based on a route, you'd obviously need to know the relationship
				//	NOTE: this obviously won't work if you try to use a database configuration object
				$config = $this->source->getValue("filename");
			}break;

			case "database":{
				//	NOTE: actually the problem of knowing which route belongs to which plugin is a problem stopping me
				//			from implementing this router in full, right now I have a half-way-point version which
				//			is not possible to use in the same way as the XML router is now.
				$config = Amslib_Array::reindexByKeyValue($array["child"],"tag","value");
			}break;
		}

		Amslib_Router::load($config,$data["type"],$this->getName());
	}

	public function configExtension($name,$array,$object)
	{
		if(!array_key_exists("child",$array)) return;

		$data = $array["attr"];

		if(!isset($data["type"]) || !in_array($data["type"],array("scan","load"))){
			$data["type"] = "load";
		}

		foreach(Amslib_Array::valid($array["child"]) as $c){
			$data[$c["tag"]] = $c["value"];
		}

		//	We don't have the correct tags, so this extension was invalid
		if(!isset($data["selector"]) || !isset($data["callback"])){
			Amslib_Debug::log("INVALID CALLBACK",$data);
			return false;
		}

		$data["callback"] = explode(",",$data["callback"]);

		//	We don't have one or two parts to the callback, so this extension is invalid
		if(!in_array(count($data["callback"]),array(1,2))){
			Amslib_Debug::log("INVALID CALLBACK",$data);
			return false;
		}

		$callback = array(
			"scan"	=>	"addScanSelector",
			"load"	=>	"addLoadSelector"
		);

		$method = array(
			get_class($this->source),
			$callback[$data["type"]]
		);

		//Amslib_Debug::log($method,$data);

		call_user_func($method,$data["selector"],$data["callback"]);
	}

	public function configCustom($name,$array,$object)
	{
		if($this->api && method_exists($this->api,"configCustom")){
			$this->api->configCustom($this->source);
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

		if($this->source){
			//	Prepare, execute and process all the selectors loaded
			$this->source->execute($this);

			//	Save memory, be water my friend, delete unwanted things
			//	NOTE: probably this is not the best way to do this...
			$this->deleteConfigSource();

			$this->isConfigured = true;

			//	Now the configuration data is loaded, initialise the plugin with any custom requirements
			$this->initialisePlugin();
		}else{
			Amslib_Debug::log(self::ERROR_PLUGIN_SOURCE_INVALID,$this->source);
		}

		return $this->isConfigured;
	}

	public function addImport($src,$dst,$key,$value)
	{
		Amslib_Plugin_Manager::addImport($src,$dst,$key,$value);
	}

	public function addExport($src,$dst,$key,$value)
	{
		Amslib_Plugin_Manager::addExport($src,$dst,$key,$value);
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
				print(__METHOD__.": PACKAGE FAILED TO OPEN: plugin[".$this->getName()."], file[$filename]<br/>");
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
	 * 	method:	setValue
	 *
	 * 	todo: write documentation
	 */
	public function setValue($key,$value)
	{
		switch($key){
			case "value":{
				$this->data[$key] = Amslib_Array::valid($this->data[$key]);
				$this->data[$key] = array_merge($this->data[$key],$value);
			}break;

			case "translator":{
				$this->data[$key][$value["name"]] = $value;
			}break;

			case "model":{
				$this->data[$key] = $value;
			}break;

			case "image":{
				$this->data[$key][$value["id"]] = $value["value"];
			}break;

			case "view":{
				$this->data[$key][$value["id"]] = $value;
			}break;

			default:{
				die(__METHOD__.": UNCONVERTED CODE: ".Amslib_Debug::pdump(true,$key,$value));
				//	THIS IS THE OLD UNCONVERTED CODE

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
			}break;
		}
	}

	/**
	 * 	method:	getValue
	 *
	 * 	todo: write documentation
	 */
	public function getValue($key,$name=NULL)
	{
		switch($key){
			case "translator":{
				if(isset($this->data[$key][$name]) && $t=$this->data[$key][$name]){
					if(isset($t["type"]))	$this->createTranslator($t);
					if(isset($t["cache"]))	return $t;
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
		$this->location = Amslib_File::absolute($location);

		//	NOTE:	(08/03/2015) I don't think the below setPaths will work the way expected
		//			as each plugin will keep resetting them with whatever location they are
		//			given, hence there is no "plugin" location, it could diff and current plugin
		//			will obviously change depending on the plugin being loaded, so this is
		//			way off for a start.

		//	NOTE:	(05/05/2014) why the duplicated name....or more or less
		//			duplicated, obviously I have old code to fix
		Amslib_Website::setPath("plugin",$this->location);
		Amslib_Website::setPath("current_plugin",$this->location);
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
	 * 	method:	setName
	 *
	 * 	todo: write documentation
	 */
	public function setName($name)
	{
		if(is_string($name) && strlen($name)){
			$this->name = $name;
		}
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
	public function getAPI($name=NULL)
	{
		return $name ? Amslib_Plugin_Manager::getAPI($name) : $this->api;
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

	/*******************************************************************
		HELPER FUNCTIONS

		Below are methods that allow you to plugin functionality
		by just knowing the name of the plugin and the manager
		will find out which appropriate plugin to call to execute
		the functionality
	********************************************************************/

	/**
	 * 	method:	expandPath
	 *
	 * 	todo: write documentation
	 */
	static public function expandPath($path)
	{
		//	NOTE: this doesn't happen anymore, I think I got rid of this idea because it was never used
		//	Loop through all the paths given in the htaccess file and attempt to replace them
		foreach(Amslib_Router::listPaths() as $key){
			$path = str_replace($key, Amslib_Router::getPath($key), $path);
		}

		return Amslib_Website::expandPath($path);
	}

	/**
	 * 	method:	render
	 *
	 * 	todo: write documentation
	 */
	static public function render($plugin,$view="default",$parameters=array())
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->render($view,$parameters) : false;
	}

	/**
	 * 	method:	renderView
	 *
	 * 	todo: write documentation
	 */
	static public function renderView($plugin,$view="default",$parameters=array())
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->renderView($view,$parameters) : false;
	}

	static public function listObject($plugin)
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->listObject() : false;
	}

	/**
	 * 	method:	getObject
	 *
	 * 	todo: write documentation
	 * 	todo: why is singleton=false here and in Amslib_MVC::getObject, singleton=true?
	 */
	static public function getObject($plugin,$id,$singleton=false)
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->getObject($id,$singleton) : false;
	}

	/**
	 * 	method:	setStylesheet
	 *
	 * 	todo: write documentation
	 */
	static public function setStylesheet($plugin,$id,$file,$conditional=NULL)
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->setStylesheet($id,$file,$conditional) : false;
	}

	/**
	 * 	method:	addStylesheet
	 *
	 * 	todo: write documentation
	 */
	static public function addStylesheet($plugin,$stylesheet)
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->addStylesheet($stylesheet) : false;
	}

	/**
	 * 	method:	setJavascript
	 *
	 * 	todo: write documentation
	 */
	static public function setJavascript($plugin,$id,$file,$conditional=NULL)
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->setJavascript($id,$file,$conditional) : false;
	}

	/**
	 * 	method:	addJavascript
	 *
	 * 	todo: write documentation
	 */
	static public function addJavascript($plugin,$javascript)
	{
		$api = Amslib_Plugin_Manager::getAPI($plugin);

		return $api ? $api->addJavascript($javascript) : false;
	}

	static public function getCallback($callback)
	{
		//	Case 1: the string, function, static method is callable without any processing
		if(is_callable($callback)){
			return $callback;
		}

		//	Example: [plugin]v1_event, [object]V1_Event, [method]somethingToCall
		$parts = explode(",",$callback);

		//	if there are no parts, you cannot decode anything from this callback
		if(empty($parts)) return false;
		$plugin = array_shift($parts);
		$plugin = Amslib_Plugin_Manager::getAPI($plugin);

		//	If the plugin doesn't exist, there is nothing to execute
		if(!$plugin) return false;

		//	If there are no more parts, then you don't have a method to call, again, cannot proceed
		if(empty($parts)) return false;
		$method = array_pop($parts);

		//	Get the object to use in the callback
		$object = array_shift($parts);
		$object = empty($object) ? $plugin : $plugin->getObject($object);

		//	Build the callback and test you can call it
		$callback = array($object,$method);

		if(is_callable($callback)){
			return $callback;
		}

		//	failing this, you cannot do anything
		return false;
	}

	////////////////////////////////////////////////
	//	DEPRECATED METHODS BELOW
	////////////////////////////////////////////////
	static private function ____DEPRECATED_METHODS_BELOW(){}

	static protected function setPath($name,$path)
	{
		Amslib_Website::setPath($name,$path);
	}
}
