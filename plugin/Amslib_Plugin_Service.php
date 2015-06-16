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
 * 	class:	Amslib_Plugin_Service
 *
 *	group:	plugin
 *
 *	file:	Amslib_Plugin_Service.php
 *
 *	description:
 *		todo, write description
 *
 * 	todo:
 * 		write documentation
 *
 * 	notes:
 * 		I think it's wrong now to assume that this object is in any way related to plugins
 * 		it's actually an object executing a series of service handlers and recording the results
 * 		to be passed back to the caller (through the session or through a json data structure)
 *
 * 		Over the next couple of revisions, I'm going to start to break parts of the code away
 * 		from being so attached to the plugin system, so things can start to be used in isolation
 * 		even if you're not using the plugin system, this means I can do router/webservices without
 * 		almost requiring the plugin system to be used.
 *
 */
class Amslib_Plugin_Service
{
	const SR = "amslib.service";
	const VD = "validation.data";
	const VE = "validation.errors";
	const SD = "service.data";
	const SE = "service.errors";
	const DB = "database.errors";
	const HD = "handlers";
	const SC = "success";
	const US = "url_success";
	const UF = "url_failure";

	protected $successCB;
	protected $failureCB;
	protected $format;
	protected $data;

	/**
	 * 	boolean: $optimise
	 *
	 * 	Whether the webservice result will attempt to optimise it's result so remove not-useful-elements
	 *
	 * 	This will, if enabled do the following
	 * 		-	If there is only one handler, it'll remove the handler and the result will equal the data contained within it
	 * 		-	If there is only one block of data, in that single handler, the result will equal the data inside the first block
	 *
	 * 	This allows the return structure to be much simpler than requiring people to see things they might not be required
	 * 	to know about, allowing a simpler structure, unless more complex results are generated, then the full result set
	 * 	will be returned
	 */
	protected $optimise;

	//	NOTE:	technically "session" is the wrong word, since we could be building a JSON
	//			structure to send back, it's an old word associated with the old system
	//			which only worked through PHP sessions
	protected $session;
	protected $handlerList;
	protected $terminatorList;
	protected $activeHandler;

	//	Used in the website to retrieve the session data after processing
	static protected $serviceData	=	NULL;
	static protected $handler		=	NULL;
	static protected $var			=	array();

	/**
	 * 	method:	getHandlerData
	 *
	 * 	todo: write documentation
	 */
	static protected function getHandlerData($plugin,$default,$key)
	{
		$plugin = self::pluginToName($plugin);

		if(!self::$handler && count(self::$serviceData[self::HD])){
			self::processHandler();
		}

		if(!self::$handler){
			//	TODO: move into the logging system intead of here
			error_log("** ".__METHOD__." ** ".Amslib_Debug::pdump(true,self::$handler)." was invalid");
			return NULL;
		}

		return isset(self::$handler[$plugin]) && isset(self::$handler[$plugin][$key])
			? Amslib_Array::valid(self::$handler[$plugin][$key])
			: $default;
	}

	/**
	 * 	method:	storeData
	 *
	 * 	todo: write documentation
	 */
	protected function storeData($status,$force=false)
	{
		$this->setServiceStatus($status);
		
		if($this->activeHandler["record"] || $force){
			if(!empty($this->data)){
				$this->session[self::HD][]	= $this->data;
			}
		}

		//	this is why record=global overwrites previous session data, because it
		//	effectively overwrites the last one with the new one, there is no
		//	"handler" concept here like there is with recording the data into the session
		if($this->activeHandler["global"]){
			$this->setVar(NULL,$this->data);
		}
	}

	/**
	 * 	method: getResultData
	 *
	 * 	Process the session data into the final result before passing back to a method
	 * 	which will output it somehow
	 *
	 * 	returns:
	 * 		An array structure processed into the final result
	 *
	 * 	notes:
	 * 		-	It's not safe to remove any more levels here, such as service.data or service.errors, etc
	 * 			because it's the minimum necessary to work
	 */
	protected function getResultData()
	{
		//	Optionally remove the handlers, if there is only one of them
		if($this->getOptimiseState() && count($this->session[self::HD]) == 1){
			$data = $this->session[self::HD][0];

			if(count($data) == 1){
				$data = current($data);
			}

			unset($this->session[self::HD]);
			$this->session = array_merge($this->session,$data);
			$this->session["optimised"] = true;
		}

		return $this->session;
	}

	/**
	 * 	method:	successSESSION
	 *
	 * 	todo: write documentation
	 */
	protected function successSESSION()
	{
		$_SESSION[self::SR] = $this->getResultData();

		Amslib_Website::redirect($this->getSuccessURL(),true);
	}

	/**
	 * 	method:	failureSESSION
	 *
	 * 	todo: write documentation
	 */
	protected function failureSESSION()
	{
		$_SESSION[self::SR] = $this->getResultData();

		Amslib_Website::redirect($this->getFailureURL(),true);
	}

	/**
	 * 	method:	successJSON
	 *
	 * 	todo: write documentation
	 */
	protected function successJSON()
	{
		//	NOTE: I don't like the method name "outputJSON", I think it's ugly and not elegant
		Amslib_Website::outputJSON($this->getResultData(),true);
	}

	/**
	 * 	method:	failureJSON
	 *
	 * 	todo: write documentation
	 */
	protected function failureJSON()
	{
		Amslib_Website::outputJSON($this->getResultData(),true);
	}

	/**
	 * 	method:	sanitiseURL
	 *
	 * 	todo: write documentation
	 */
	protected function sanitiseURL($url)
	{
		//	Capture the http:// part so you can replace it afterwards
		//	NOTE: what happens if you are running from https:// ?
		$http = strpos($url,"http://") !== false ? "http://" : "";
		//	strip away the http:// part first, because it won't survive the reduceSlashes otherwise
		$url = $http.Amslib_File::reduceSlashes(str_replace("http://","",$url));

		return $url == "/" ? "" : $url;
	}

	/**
	 * 	method:	finalise
	 *
	 * 	todo: write documentation
	 */
	protected function finalise($state)
	{
		$source = array();

		$type = $state ? "success" : "failure";

		//	Call the success or failure terminators
		foreach(Amslib_Array::valid($this->terminatorList[$type]) as $t){
			$response = $this->runHandler($t["object"],$t["method"],$source);

			//	Store the result of the service and make ready to start a new service
			$this->storeData($response);
		}

		//	Call the common terminators
		foreach(Amslib_Array::valid($this->terminatorList["common"]) as $t){
			$response = $this->runHandler($t["object"],$t["method"],$source);

			//	Store the result of the service and make ready to start a new service
			$this->storeData($response);
		}
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		-	The object is basically hard coded to ONLY use $_POST as an input source, but this might not be true
	 * 		-	In situations where $_GET is used, you'd need to post some parameters through $_GET, others $_POST
	 * 		-	So this should be made more flexible, so the webservice can define the input source and this accomodates
	 */
	public function __construct()
	{
		//	Reset the service data and session structures
		$this->data		=	array();
		$this->session	=	array(self::HD=>array());

		//	FIXME: we are hardcoding a route "home" which might not exist, this could be a bad idea
		$default_url	=	Amslib_Router::getURL("home");

		$url_return		=	Amslib_POST::get("url_return",	Amslib_POST::get("return_url",$default_url));
		$url_return		=	Amslib_String::rchop($url_return,"?");

		$url_success	=	Amslib_POST::get("url_success",	Amslib_POST::get("success_url",$url_return));
		$url_success	=	Amslib_String::rchop($url_success,"?");

		$url_failure	=	Amslib_POST::get("url_failure",	Amslib_POST::get("failure_url",$url_return));
		$url_failure	=	Amslib_String::rchop($url_failure,"?");

		//	TODO:	I should remove all the url parameters from the source data so it doesn't pass through as data
		//	NOTE:	well then in this case perhaps I should have to namespace them too so they are in a separate
		//			part of the source data and not obvious "url_return" might be a bit generic
		//	NOTE:	but if I namespace them, I don't want to pass that complexity onto the programmer
		//	NOTE:	perhaps this means I need to add functions to build these parameters for the programmer
		//			instead of making them build them personally

		$this->setSuccessURL($url_success);
		$this->setFailureURL($url_failure);

		//	blank the appropriate session key to stop previous sessions overlapping
		//	NOTE:	what if you are using the json output? then there is no key to erase,
		//			so you're just creating session keys and not using them
		Amslib_SESSION::get(self::SR,false,true);

		//	Obtain the old return_ajax parameter, either as json or false
		$return_ajax = Amslib_POST::get("return_ajax",false) ? "json" : false;
		//	Obtain the new output_format parameter, or default to the return_ajax value if not found
		$format = Amslib_POST::get("output_format",$return_ajax);
		//	Sanitise the format to be one of three possible options, or default to false
		//	NOTE: we might want to use redirect, but we cannot store anything useful in the session
		//	NOTE: however this is valid reasoning: to do the job, redirect, but don't bother writing session data cause it cannot be used
		if(!in_array($format,array(false,"json","session"))) $format = false;
		//	Now it should be safe to set the output format, false will of course reset
		$this->setOutputFormat($format);

		//	Initialise all the terminator groups that can exist
		$this->terminatorList = array_fill_keys(array("common","success","failure"),array());
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

	public function getSessionData()
	{
		return $this->session;
	}
	
	public function setInputFormat($handler)
	{
		//	by default, all sources are "post" if not specified
		if(!isset($handler["source"])) $handler["source"] = "post";
		
		//	Set the source to what was requested, or default in any other case to the $_POST array
		switch($handler["source"]){
			case "get":{
				$this->source = &$_GET;
			}break;
		
			case "post":{
				$this->source = &$_POST;
			}break;
		
			case "previous":{
				if(!count($this->data)){
					$this->source = array();
				}else{
					$this->source = Amslib_Webservice::createResponse("amslib");
					$this->source->addHandler($this->data);
					$this->source->setStatus(true);
				}
			}break;
		}
	}

	public function setOutputFormat($format)
	{
		//	This prevents the output format from being reset after it's set,
		//	however if you attempt to invalidate it, it will allow you
		if($format && $this->format) return $this->format;

		$this->format = $format;

		switch($this->format){
			case "session":{
				$this->successCB = "successSESSION";
				$this->failureCB = "failureSESSION";
			}break;

			case "json":{
				$this->successCB = "successJSON";
				$this->failureCB = "failureJSON";
			}break;

			case "xml":{
				//	NOTE: I just add the code here because it's easy to do, but this isn't supported yet
				$this->successCB = "successXML";
				$this->failureCB = "failureXML";
			}break;

			default:{
				$this->successCB = false;
				$this->failureCB = false;
			}break;
		}

		return $this->format;
	}

	public function getOutputFormat()
	{
		return $this->format;
	}

	/**
	 * 	method:	setSuccessURL
	 *
	 * 	todo: write documentation
	 */
	public function setSuccessURL($url)
	{
		$url = $this->sanitiseURL($url);

		if(strlen($url)) $this->session[self::US] = $url;
	}

	/**
	 * 	method:	getSuccessURL
	 *
	 * 	todo: write documentation
	 */
	public function getSuccessURL()
	{
		return $this->session[self::US];
	}

	/**
	 * 	method:	setFailureURL
	 *
	 * 	todo: write documentation
	 */
	public function setFailureURL($url)
	{
		$url = $this->sanitiseURL($url);

		if(strlen($url)) $this->session[self::UF] = $url;
	}

	/**
	 * 	method:	getFailureURL
	 *
	 * 	todo: write documentation
	 */
	public function getFailureURL()
	{
		return $this->session[self::UF];
	}

	/**
	 * 	method:	setReturnURL
	 *
	 * 	todo: write documentation
	 */
	public function setReturnURL($url)
	{
		$this->setSuccessURL($url);
		$this->setFailureURL($url);
	}

	/**
	 * 	method: setOptimiseState
	 *
	 * 	todo: write documentation
	 */
	public function setOptimiseState($state)
	{
		$this->optimise = $state == "true";
	}

	/**
	 * 	method: getOptimiseState
	 *
	 * 	todo: write documentation
	 */
	public function getOptimiseState()
	{
		return $this->optimise;
	}

	/**
	 * 	method:	setVar
	 *
	 * 	todo: write documentation
	 */
	public function setVar($key,$data)
	{
		if($key === NULL && is_array($data)){
			self::$var = array_merge(self::$var,$data);
		}else if(is_string($key) && strlen($key)){
			self::$var[$key] = $data;
		}else if($data === NULL){
			self::$var = array();
		}
	}

	/**
	 * 	method:	getVar
	 *
	 * 	todo: write documentation
	 */
	public function getVar($key=NULL)
	{
		if($key == NULL) return self::$var;

		return is_string($key) && strlen($key) && isset(self::$var[$key])
			? self::$var[$key]
			: NULL;
	}

	public function installHandlers($group,$output,$handlerList)
	{
		foreach(Amslib_Array::valid($handlerList) as $h)
		{
			//	Special customisation for framework urls, which normally execute on objects regardless of plugin
			//	So we just use plugin as the key to trigger this
			//	NOTE: this means framework has become a system name and cannot be used as a name of any plugin?
			//	NOTE: perhaps amslib can become a plugin, therefore the plugin will be "amslib" and not "framework" ?
			if(Amslib_Array::hasKeys($h,array("plugin","object","method")) && $h["plugin"] == "framework"){
				//	do nothing
			}else{
				//	This significantly helps with debugging, because you can quickly see
				//	the plugin which if it was missing, defaults to group, but this information
				//	doesn't get output into the debugging information.
				if(!isset($h["plugin"])) $h["plugin"] = $group;

				$debug = array();

				if($h["plugin"]){
					$debug["api"] = $h["plugin"];

					$h["api"] = Amslib_Plugin_Manager::getAPI($h["plugin"]);
				}else{
					$list = Amslib_Array::valid(Amslib_Plugin_Manager::listPlugin());
					$list = implode(",",$list);
					Amslib_Debug::log("plugin requested was not valid, list = $list");
				}

				if($h["api"]){
					$debug["object"] = isset($h["object"]) ? $h["object"] : $debug["api"];

					$h["object"] = isset($h["object"]) ? $h["api"]->getObject($h["object"],true) : $h["api"];
				}else{
					$list = Amslib_Array::valid(Amslib_Plugin_Manager::listAPI());
					$list = implode(",",$list);
					Amslib_Debug::log("api object '{$debug["api"]}' was not valid, api list = $list");
				}

				if($h["object"]){
					$h["method"] = isset($h["method"]) ? $h["method"] : false;
				}else{
					$list = Amslib_Array::valid(Amslib_Plugin::listObject($h["plugin"]));
					$list = implode(",",$list);
					Amslib_Debug::log("object '{$debug["object"]}' on api '{$debug["api"]}' not valid, object list = $list");
				}
			}

			$params = array($h["plugin"],$h["object"],$h["method"],$h["input"],$h["record"],$h["global"],$h["failure"]);

			if($h["type"] == "service"){
				$method = "setHandler";
				array_unshift($params,$output);
			}else{
				$method = "setTerminator";
				array_unshift($params,str_replace("terminator_","",$h["type"]));
			}

			call_user_func_array(array($this,$method),$params);
		}
	}


	/**
	 * 	method:	setHandler
	 *
	 * 	todo: write documentation
	 * 	note: is the $format parameter here related with the previous now deprecated method setFormat ?
	 * 	note: if it is, then perhaps I should change it to $output instead?
	 * 	note: but $output is very ambiguous...or maybe it just appears that way.
	 */
	public function setHandler($format,$plugin,$object,$method,$source="post",$record=true,$global=false,$failure=true)
	{
		//	NOTE: perhaps we should check this information before blindly accepting it

		//	here we store handlers loaded from the service path before we execute them.
		$this->handlerList[] = array(
			"format"	=>	$format,
			"plugin"	=>	$plugin,
			"object"	=>	$object,
			"method"	=>	$method,
			"source"	=>	$source,
			"record"	=>	$record,
			"global"	=>	$global,
			"failure"	=>	$failure
		);
	}

	/**
	 * 	method:	setTerminator
	 *
	 * 	todo: write documentation
	 */
	public function setTerminator($type,$plugin,$object,$method,$source="post",$record=true,$global=false,$failure=true)
	{
		//	NOTE: perhaps we should check this information before blindly accepting it

		if(!in_array($type,array("common","success","failure"))){
			return false;
		}

		$this->terminatorList[$type][] = array(
			"plugin"	=>	$plugin,
			"object"	=>	$object,
			"method"	=>	$method,
			"source"	=>	$source,
			"record"	=>	$record,
			"global"	=>	$global,
			"failure"	=>	$failure
		);
	}

	/**
	 * 	method:	runHandler
	 *
	 * 	todo: write documentation
	 */
	public function runHandler($object,$method,&$source)
	{
		$callback = false;
		
		//	Reset the data array to empty so it's empty and ready for the next execution
		$this->data = array();

		//	NOTE:   this might seem a little harsh, but it's a critical error, your object doesn't have
		//	the method you said it would, probably this means something in your code is broken
		//	and you need to know about it and fix it.

		if(is_string($object) && class_exists($object) && is_callable("$object::$method")){
			$callback = "$object::$method";
		}else if(!is_object($object)){
			Amslib_Debug::log("\$object parameter was not an object, ".Amslib_Debug::dump($object));
			$object = "__INVALID_OBJECT__";
			die("FAILURE[c:$object][m:$method]-> object did not exist, so cannot execute service handler");
		}else if(!method_exists($object,$method)){
			Amslib_Debug::log("'$method' was not found on object, ".Amslib_Debug::dump($method));
			$object = get_class($object);
			die("FAILURE[c:$object][m:$method]-> method did not exist, so cannot execute service handler");
		}else{
			$callback = array($object,$method);
		}

		return call_user_func_array($callback,array($this,&$source));
	}

	/**
	 * 	method:	runManagedHandler
	 *
	 * 	todo: write documentation
	 *
	 * 	NOTE: the code is ready however the system is not
	 * 	NOTE: the problem is that service handlers are not programmed to understand the extra attributes
	 * 	NOTE: the other problem is that service import/export definitions are not programmed as well
	 * 	NOTE: so the idea will have to stay here until I can dedicate time to implementing those.
	 */
	public function runManagedHandler($rules,$object,$method,&$source)
	{
		//	This method needs to exist on the object to retrieve the validation rules
		$getRules = array($object,"getValidationRules");

		//	continue only if the method is available to call
		if(!method_exists($getRules)) return false;

		$rules = call_user_func($getRules,$rules);

		//	continue only if the rules are valid and a non-empty array
		if(!$rules || !is_array($rules) || empty($rules)) return false;

		//	Now lets execute the handler!
		$v = new Amslib_Validator($source);
		$v->addRules($rules);

		$s = $v->execute();
		$d = $v->getValidData();

		if($s){
			//	Set the source to the valid data
			$source = $d;

			//	Here we call the handler, this is a SUCCESS only handler, although the data might fail, the data was valid
			return $this->runHandler($object,$method,$source);
		}else{
			$service->setValidationData($object,$d);
			$service->setValidationErrors($object,$v->getErrors());
		}

		$service->setDatabaseErrors($object,$object->getDBErrors());

		return false;
	}

	public function setSourceData($key,$value)
	{
		if(is_string($key) && is_array($this->source)){
			$this->source[$key] = $value;
		}
	}

	/**
	 * 	method:	execute
	 *
	 * 	todo: write documentation
	 */
	public function execute($optimise=false)
	{
		$state = false;

		$this->setOptimiseState($optimise);

		foreach($this->handlerList as $h){
			//	TODO: investigate why h["plugin"] was returning false??
			$this->activeHandler = $h;

			$this->setInputFormat($h);
			$this->setOutputFormat($h["format"]);

			//	Run the handler, either in managed or unmanaged mode
			$response = isset($h["managed"])
				? $this->runManagedHandler($h["managed"],$h["object"],$h["method"],$this->source)
				: $this->runHandler($h["object"],$h["method"],$this->source);

			//	Need to somehow merge against Amslib_Webservice_* classes
			//	because now some of this code is overlapping a lot
			if(is_array($this->source) && isset($this->source["/amslib/webservice/session/request/"])){
				$this->session["/amslib/webservice/session/remote/"] = session_id();
			}

			//	Store the result of the service and make ready to start a new service
			$this->storeData($response);

			if(!$response){
				if($h["failure"] === "break"){
					$state = false;
					break;
				}else if($h["failure"] === "stop"){
					break;
				}else if($h["failure"] === "ignore"){
					//	do nothing
				}
			}else{
				$state = $response;
			}
		}

		$this->finalise($state);

		//	run the failure or success callback to send data back to the receiver
		call_user_func(array($this,$state ? $this->successCB : $this->failureCB));

		//	If you arrive here, something very seriously wrong has happened
		die("FAILURE: All services should terminate with redirect or json");
	}

	/**
	 * 	method:	pluginToName
	 *
	 * 	todo: write documentation
	 */
	static public function pluginToName($plugin)
	{
		if(is_object($plugin)) $plugin = get_class($plugin);
		if(!is_string($plugin) && !is_numeric($plugin)) $plugin = "__ERROR_PLUGIN_UNKNOWN__";

		return $plugin;
	}

	/**
	 * 	method:	setValidationData
	 *
	 * 	todo: write documentation
	 */
	public function setValidationData($plugin,$data)
	{
		$this->data[self::pluginToName($plugin)][self::VD] = $data;
	}

	/**
	 * 	method:	setValidationErrors
	 *
	 * 	todo: write documentation
	 */
	public function setValidationErrors($plugin,$errors)
	{
		if(empty($errors)){
			$errors["no_errors"] = true;
			$errors["debug_help"] = "No Errors set and array was empty, perhaps validation failed because source was empty?";
		}

		$this->data[self::pluginToName($plugin)][self::VE] = $errors;
	}

	/**
	 * 	method:	setDatabaseErrors
	 *
	 * 	todo: write documentation
	 *
	 *	NOTE: Be careful with this method, you could be pushing secret data
	 */
	public function setDatabaseErrors($plugin,$errors)
	{
		if(!empty($errors)) $this->data[self::pluginToName($plugin)][self::DB] = $errors;
	}

	/**
	 * 	method:	setData
	 *
	 * 	todo: write documentation
	 */
	public function setData($plugin,$name,$value)
	{
		$plugin = self::pluginToName($plugin);

		if($name == NULL){
			if(is_array($value)){
				$this->data[$plugin][self::SD] = $value;
			}else{
				Amslib_Debug::log("value was invalid array",$value);

				return NULL;
			}
		}else if(is_numeric($name) || is_string($name)){
			$this->data[$plugin][self::SD][$name] = $value;
		}else{
			Amslib_Debug::log("name was invalid",$name);

			return NULL;
		}

		return $value;
	}

	/**
	 * 	method:	getData
	 *
	 * 	todo: write documentation
	 */
	public function getData($plugin,$name=NULL,$default=NULL)
	{
		$plugin = self::pluginToName($plugin);

		if(!isset($this->data[$plugin])) return $default;

		$plugin = $this->data[$plugin];

		if($name == NULL) return $plugin[self::SD];

		return isset($plugin[self::SD][$name]) ? $plugin[self::SD][$name] : $default;
	}

	/**
	 * 	method:	deleteData
	 *
	 * 	todo: write documentation
	 */
	public function deleteData($plugin,$name=NULL)
	{
		$plugin	=	self::pluginToName($plugin);
		$copy	=	NULL;

		if(isset($this->data[$plugin])){
			if($name && isset($this->data[$plugin][self::SD][$name])){
				$copy = $this->data[$plugin][self::SD][$name];

				unset($this->data[$plugin][self::SD][$name]);
			}else if(!$name){
				$copy = $this->data[$plugin][self::SD];

				unset($this->data[$plugin][self::SD]);
			}

			//	clean up empty arrays in the return structure
			if(empty($this->data[$plugin])) unset($this->data[$plugin]);
		}

		return $copy;
	}

	/**
	 * 	method:	moveData
	 *
	 * 	todo: write documentation
	 */
	public function moveData($dst,$src,$name=NULL)
	{
		$this->setData(
			$dst,
			$name,
			$this->deleteData($src,$name)
		);
	}

	/**
	 * 	method:	setError
	 *
	 * 	todo: write documentation
	 */
	public function setError($plugin,$name,$value)
	{
		$plugin = self::pluginToName($plugin);

		if($name == NULL && is_array($value)){
			$this->data[$plugin][self::SE] = $value;
		}else{
			$this->data[$plugin][self::SE][$name] = $value;
		}
	}

	/**
	 * 	method:	getError
	 *
	 * 	todo: write documentation
	 */
	public function getError($plugin=NULL,$name=NULL)
	{
		if($plugin === NULL){
			$errors = array();

			foreach(array_keys($this->data) as $plugin){
				if(!isset($this->data[$plugin]) || !isset($this->data[$plugin][self::SE])){
					continue;
				}

				$errors[$plugin] = $name !== NULL && isset($this->data[$plugin][self::SE][$name])
					? $this->data[$plugin][self::SE][$name]
					: $this->data[$plugin][self::SE];
			}

			return $errors;
		}

		if($name === NULL && isset($this->data[$plugin])){
			return $this->data[$plugin][self::SE];
		}

		return $this->data[$plugin][self::SE][$name];
	}

	/**
	 * 	method:	setServiceStatus
	 *
	 * 	Set the global success status of the current service being executed
	 *
	 * 	parameters:
	 * 		$status	-	The status to set, will be forced into being boolean true or false
	 */
	public function setServiceStatus($status)
	{
		$this->session[self::SC] = $status ? true : false;
	}

	/**
	 * 	method:	getServiceStatus
	 *
	 * 	Retrieve the current global success status of the current service being executed
	 *
	 * 	returns:
	 * 		Boolean true or false, depending on the current status of the service
	 */
	public function getServiceStatus()
	{
		return isset($this->session[self::SC]) ? $this->session[self::SC] : NULL;
	}

	/**
	 * 	method:	cloneResponse
	 *
	 * 	This method will accept an array of data, normally acquired from the Amslib_Plugin_Service
	 * 	object and import various keys into a new plugin key, ready to use, this is useful when
	 * 	copying and deleting old data in order to reformat it based on the requirements
	 */
	public function cloneResponse($plugin,$data,$store=false)
	{
		if(isset($data[self::SD]))	$this->setData($plugin,NULL,$data[self::SD]);
		if(isset($data[self::SE]))	$this->setError($plugin,NULL,$data[self::SE]);
		if(isset($data[self::VD]))	$this->setValidationData($plugin,$data[self::VD]);
		if(isset($data[self::VE]))	$this->setValidationErrors($plugin,$data[self::VE]);

		//	Store the result of the service and make ready to start a new service
		if($store) $this->storeData($this->getServiceStatus(),true);
	}

	/*
	 * idea to solve this in a general way
	 * however the side effect is that it'll domainate these paramaters and overwrite whatever was there
	 * e.g: $service->setPaginatedData($this,$this->api->getTypeList(),$this->api->getTypeCount());
	 *
	public function setPaginatedData($name,$list,$length)
	{
		$this->setData($name,"item_count",$length);
		$this->setData($name,"page_count",Amslib_Keystore::getPagerCount($length));
		$this->setData($name,"list",$list);
	}
	*********/

	public function serviceWebserviceCatchall($service,$source)
	{
		//	NOTE: perhaps this should detect if any, the output method and set the output type accordingly
		$service->setOutputFormat("json");

		$service->setData($this,"webservice_not_found",false);

		return true;
	}

	/*****************************************************************************
	 * 	STATIC API TO RETRIEVE SESSION DATA
	*****************************************************************************/
	/**
	 * 	method:	hasData
	 *
	 * 	todo: write documentation
	 */
	static public function hasData($remove=true)
	{
		if(self::$serviceData === NULL || $remove == false){
			self::$serviceData = Amslib_SESSION::get(self::SR,false,$remove);
		}

		return self::$serviceData ? true : false;
	}

	/**
	 * 	method:	getRawData
	 *
	 * 	todo: write documentation
	 */
	static public function getRawData()
	{
		return self::$serviceData;
	}

	/**
	 * 	method:	countHandler
	 *
	 * 	todo: write documentation
	 */
	static public function countHandler()
	{
		return count(self::$serviceData[self::HD]);
	}

	/**
	 * 	method:	processHandler
	 *
	 * 	todo: write documentation
	 */
	static public function processHandler($id=0)
	{
		self::$handler = isset(self::$serviceData[self::HD][$id]) ? self::$serviceData[self::HD][$id] : array();

		return array_keys(Amslib_Array::valid(self::$handler));
	}

	/**
	 * 	method:	getStatus
	 *
	 * 	todo: write documentation
	 */
	static public function getStatus()
	{
		$success = isset(self::$serviceData[self::SC]) ? self::$serviceData[self::SC] : false;

		//	WTF: why do I remove this? commenting it out for now
		//unset(self::$serviceData[self::SC]);

		return $success;
	}

	/**
	 * 	method:	getValidationData
	 *
	 * 	todo: write documentation
	 */
	static public function getValidationData($plugin,$default=array())
	{
		return Amslib_Array::valid(self::getHandlerData($plugin,$default,self::VD));
	}

	/**
	 * 	method:	getValidationErrors
	 *
	 * 	todo: write documentation
	 */
	static public function getValidationErrors($plugin,$default=false)
	{
		return self::getHandlerData($plugin,$default,self::VE);
	}

	/**
	 * 	method:	getServiceData
	 *
	 * 	todo: write documentation
	 */
	static public function getServiceErrors($plugin,$default=false)
	{
		return self::getHandlerData($plugin,$default,self::SE);
	}

	/**
	 * 	method:	getServiceData
	 *
	 * 	todo: write documentation
	 */
	static public function getServiceData($plugin,$default=false,$key=false)
	{
		$data = self::getHandlerData($plugin,$default,self::SD);

		return $key && $data && isset($data[$key]) ? $data[$key] : $data;
	}

	//	NOTE: Be careful with this method, it could leak secret data if you didnt sanitise it properly of sensitive data
	/**
	 * 	method:	getDatabaseErrors
	 *
	 * 	todo: write documentation
	 */
	static public function getDatabaseErrors($plugin,$default=false)
	{
		return self::getHandlerData($plugin,$default,self::DB);
	}

	/**
	 * 	method:	getDatabaseMessage
	 *
	 * 	todo: write documentation
	 */
	static public function getDatabaseMessage($plugin,$default=false)
	{
		return Amslib_Array::filterKey(self::getDatabaseErrors($plugin,$default),array("db_error","db_location"));
	}

	//////////////////////////////////////////////////////////////////
	//	DEPRECATED METHODS
	//////////////////////////////////////////////////////////////////
	//	NOTE: use setVar instead
	public function setTemp($key,$value){	$this->setVar($key,$value);	}
	//	NOTE: use getVar instead
	public function getTemp($key){			return $this->getVar($key); }
	//	NOTE: use setOutputFormat instead
	public function setAjax($status){		$this->setOutputFormat($status ? "json" : "session");	}
	//	NOTE: use setOutputFormat instead
	public function setFormat($format){		$this->setOutputFormat($format);	}
	//	NOTE: use getOutputFormat instead
	public function getFormat(){			return $this->getOutputFormat();	}
}
