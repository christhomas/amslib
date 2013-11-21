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
 * 	class:	Amslib_MVC
 *
 *	group:	mvc
 *
 *	file:	Amslib_MVC.php
 *
 *	description:
 *		Model/View/Controller implementation for use with Amslib projects
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_MVC extends Amslib_Mixin
{
	protected $object;
	protected $view;
	protected $images;
	protected $stylesheet;
	protected $javascript;
	protected $translator;

	//	The model object to allow access to the application logic
	//	NOTE: what if an api requires multiple models??
	protected $model;

	protected $value;
	protected $viewParams;
	protected $routes;

	//	To allow views/html segments to be slotted into an existing layout
	//	extending their capabilities with customised functionality
	//	PROBABLY DEPRECATED
	protected $slots;

	protected $name;
	protected $location;
	protected $plugin;

	/**
	 * 	method:	recoverServiceData
	 *
	 * 	todo: write documentation
	 */
	protected function recoverServiceData($name=NULL,$handler=0)
	{
		Amslib_Plugin_Service::hasData();
		Amslib_Plugin_Service::processHandler($handler);

		if(!$name) $name = $this;

		return Amslib_Plugin_Service::getValidationData($name);
	}

	/**
	 * 	method:	getInstance
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		$this->object		=	array();
		$this->view			=	array();
		$this->stylesheet	=	array();
		$this->javascript	=	array();
		$this->translator	=	array();

		$this->value		=	array();
		$this->viewParams	=	array();
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

	/**
	 * 	method:	addMixin
	 *
	 * 	This method allows you to use the getObject call to acquire an object by name instead of passing
	 * 	it by object, allowing the system to find and return the object for you instead of manually having
	 * 	to create it first.
	 *
	 * 	todo: write documentation
	 */
	public function addMixin($name,$reject=array(),$accept=array())
	{
		if(is_string($name)){
			$object = $this->getObject($name,true);

			if(!$object) $object = $this->getAPI($name);
		}elseif(is_object($name)){
			$object = $name;
		}else{
			$object = false;
		}

		return parent::addMixin($object,$reject,$accept);
	}

	/**
	 * 	method:	setName
	 *
	 * 	todo: write documentation
	 */
	public function setName($name)
	{
		$this->name = $name;
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
	 * 	method:	setLocation
	 *
	 * 	todo: write documentation
	 */
	public function setLocation($location)
	{
		$this->location = $location;
	}

	/**
	 * 	method:	initialise
	 *
	 * 	todo: write documentation
	 */
	public function initialise()
	{
		return $this;
	}

	/**
	 * 	method:	finalise
	 *
	 * 	todo: write documentation
	 */
	public function finalise()
	{
		//	do nothing
	}

	/**
	 * 	method:	setModel
	 *
	 * 	todo: write documentation
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

	/**
	 * 	method:	getModel
	 *
	 * 	todo: write documentation
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * 	method:	setPlugin
	 *
	 * 	todo: write documentation
	 */
	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}

	/**
	 * 	method:	getPlugin
	 *
	 * 	todo: write documentation
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}

	/**
	 * 	method:	setValue
	 *
	 * 	todo: write documentation
	 */
	public function setValue($name,$value)
	{
		if(is_string($name) && strlen($name)){
			$this->value[$name] = $value;

			return $value;
		}

		return NULL;
	}

	/**
	 * 	method:	getValue
	 *
	 * 	todo: write documentation
	 */
	public function getValue($name=NULL,$default=NULL)
	{
		if(is_string($name) && strlen($name)){
			return (isset($this->value[$name])) ? $this->value[$name] : $this->getViewParam($name,$default);
		}

		//	if no value was requested and default is null, return ALL the values
		return $name == NULL && $default == NULL ? $this->value : $default;
	}

	/**
	 * 	method:	setFields
	 *
	 * 	todo: write documentation
	 */
	public function setFields($name,$value)
	{
		$name = "validate/$name";

		$f = $this->getValue($name,array());

		if(!is_array($value)) $value = array();

		$this->setValue($name,array_merge($f,$value));
	}

	/**
	 * 	method:	getFields
	 *
	 * 	todo: write documentation
	 */
	public function getFields($name)
	{
		return $this->getValue("validate/$name",array());
	}

	/**
	 * 	method:	listValues
	 *
	 * 	todo: write documentation
	 */
	public function listValues()
	{
		return array_keys($this->value);
	}

	//	TODO: need to explain the difference between a value and a view param
	/**
	 * 	method:	setViewParam
	 *
	 * 	todo: write documentation
	 */
	public function setViewParam($parameters)
	{
		$this->viewParams = $parameters;
	}

	/**
	 * 	method:	getViewParam
	 *
	 * 	todo: write documentation
	 */
	public function getViewParam($name,$default=NULL)
	{
		return (isset($this->viewParams[$name])) ? $this->viewParams[$name] : $default;
	}

	/**
	 * 	method:	setObject
	 *
	 * 	todo: write documentation
	 */
	public function setObject($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->object[$id] = $name;
	}

	/**
	 * 	method:	getObject
	 *
	 * 	todo: write documentation
	 */
	public function getObject($id,$singleton=true)
	{
		//	If the object requested is this object, return it directly
		if(get_class($this) == $id) return $this;
		//	If the object requested doesnt exist as an id in the array, return false
		if(!isset($this->object[$id])) return false;

		//	otherwise, include that object php object and then check the class exists (so you can create it)
		Amslib::requireFile($this->object[$id],array("require_once"=>true));

		if(class_exists($id)){
			//	If it's a singleton, create it, otherwise create a new instance of the object
			if($singleton && method_exists($id,"getInstance")){
				$o = call_user_func(array($id,"getInstance"));
			}else{
				$o = new $id;
			}

			//	if this object was valid and has initialiseObject, call it, then return whatever was created
			if($o && method_exists($o,"initialiseObject") && !$o->isInitialised()){
				$o->initialiseObject($this);
			}

			return $o;
		}

		//	oopsie, you done goofed!!
		return false;
	}

	/**
	 * 	method:	listObjects
	 *
	 * 	todo: write documentation
	 */
	public function listObjects()
	{
		return array_keys($this->object);
	}

	/**
	 * 	method:	setView
	 *
	 * 	todo: write documentation
	 */
	public function setView($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->view[$id] = $name;

		if(!isset($this->view["default"])) $this->view["default"] = $name;
	}

	/**
	 * 	method:	getView
	 *
	 * 	todo: write documentation
	 */
	public function getView($id)
	{
		if($id && isset($this->view[$id])) return $this->view[$id];

		return $this->view;
	}

	/**
	 * 	method:	hasView
	 *
	 * 	todo: write documentation
	 */
	public function hasView($id)
	{
		return ($id && isset($this->view[$id]));
	}

	/**
	 * 	method:	getEmptyData
	 *
	 * 	todo: write documentation
	 */
	public function getEmptyData($group)
	{
		$rules	= Amslib_Array::valid($this->getValidatorRules($group));
		$values	= array_fill_keys(array_keys($rules),"");

		foreach($rules as $k=>$v) if(isset($v[2]) && isset($v[2]["form_default"])){
			$values[$k] = $v[2]["form_default"];
		}

		return $values;
	}

	/**
	 * 	method:	render
	 *
	 * 	todo: write documentation
	 */
	public function render($view="default",$params=array())
	{
		return $this->renderView($view,$params);
	}

	/**
	 * 	method:	renderView
	 *
	 * 	todo: write documentation
	 */
	public function renderView($id,$params=array())
	{
		if(is_string($id) && isset($this->view[$id]))
		{
			//	What are view parameters for again??
			if(!empty($params)) $this->setViewParam($params);

			//	TODO: what happens if api, _w and _c are already defined and you just overwrote them?
			//	NOTE: this shouldn't happen, they are special so nobody should use them
			//	NOTE: perhaps we can warn people when they do this? or perhaps move our keys to a more unique "namespace"
			//	FIXME: what if multiple translators of the same type are defined? they would start to clash
			$params["api"]	=	$this;
			$params["_w"]	=	$this->getTranslator("website");
			$params["_c"]	=	$this->getTranslator("content");

			return Amslib::requireFile($this->view[$id],$params,true);
		}

		return "";
	}

	/**
	 * 	method:	listViews
	 *
	 * 	todo: write documentation
	 */
	public function listViews()
	{
		return array_keys($this->view);
	}

	/**
	 * 	method:	setTranslator
	 *
	 * 	todo: write documentation
	 */
	public function setTranslator($name,$translator)
	{
		$this->translator[$name] = $translator;
	}

	/**
	 * 	method:	getTranslator
	 *
	 * 	todo: write documentation
	 */
	public function getTranslator($name)
	{
		return (isset($this->translator[$name])) ? $this->translator[$name] : reset($this->translator);
	}

	/**
	 * 	method:	listTranslators
	 *
	 * 	todo: write documentation
	 */
	public function listTranslators($pluckNames=true)
	{
		return $pluckNames ? array_keys($this->translator) : $this->translator;
	}

	/**
	 * 	method:	setStylesheet
	 *
	 * 	todo: write documentation
	 */
	public function setStylesheet($id,$file,$conditional=NULL,$autoload=NULL,$media=NULL)
	{
		if(!is_string($id) && $file) return;

		$this->stylesheet[$id] = array(
			"file"			=>	$file,
			"conditional"	=>	$conditional,
			"media"			=>	$media,
			"autoload"		=>	$autoload
		);
	}

	/**
	 * 	method:	addStylesheet
	 *
	 * 	todo: write documentation
	 */
	public function addStylesheet($id)
	{
		if(isset($this->stylesheet[$id])){
			Amslib_Resource::addStylesheet(
				$id,
				$this->stylesheet[$id]["file"],
				$this->stylesheet[$id]["conditional"],
				$this->stylesheet[$id]["media"]
			);
		}
	}

	/**
	 * 	method:	listStylesheet
	 *
	 * 	todo: write documentation
	 */
	public function listStylesheet($key="file")
	{
		return $key !== false ? Amslib_Array::pluck($this->stylesheet,$key) : $this->stylesheet;
	}

	/**
	 * 	method:	getStylesheet
	 *
	 * 	todo: write documentation
	 */
	public function getStylesheet($id,$file=true)
	{
		return isset($this->stylesheet[$id])
			? $file ? $this->stylesheet[$id]["file"] : $this->stylesheet[$id]
			: "";
	}

	/**
	 * 	method:	removeStylesheet
	 *
	 * 	todo: write documentation
	 */
	public function removeStylesheet($id)
	{
		Amslib_Resource::removeStylesheet($id);
	}

	/**
	 * 	method:	setJavascript
	 *
	 * 	todo: write documentation
	 */
	public function setJavascript($id,$file,$conditional=NULL,$autoload=NULL)
	{
		if(!is_string($id) && $file) return;

		$this->javascript[$id] = array(
			"file"			=>	$file,
			"conditional"	=>	$conditional,
			"autoload"		=>	$autoload
		);
	}

	/**
	 * 	method:	addJavascript
	 *
	 * 	todo: write documentation
	 */
	public function addJavascript($id)
	{
		if(isset($this->javascript[$id])){
			$j = $this->javascript[$id];
			Amslib_Resource::addJavascript($id,$j["file"],$j["conditional"]);
		}
	}

	/**
	 * 	method:	listJavascript
	 *
	 * 	todo: write documentation
	 */
	public function listJavascript($key="file")
	{
		return $key !== false ? Amslib_Array::pluck($this->javascript,$key) : $this->javascript;
	}

	/**
	 * 	method:	getJavascript
	 *
	 * 	todo: write documentation
	 */
	public function getJavascript($id,$file=true)
	{
		return isset($this->javascript[$id])
			? $file ? $this->javascript[$id]["file"] : $this->javascript[$id]
			: "";
	}

	/**
	 * 	method:	removeJavascript
	 *
	 * 	todo: write documentation
	 */
	public function removeJavascript($id)
	{
		Amslib_Resource::removeJavascript($id);
	}

	/**
	 * 	method:	autoloadResources
	 *
	 * 	todo: write documentation
	 */
	public function autoloadResources()
	{
		foreach($this->stylesheet as $k=>$s) if($s["autoload"]){
			$this->addStylesheet($k);
		}

		foreach($this->javascript as $k=>$j) if($j["autoload"]){
			$this->addJavascript($k);
		}
	}

	/**
	 * 	method:	setFont
	 *
	 * 	todo: write documentation
	 */
	public function setFont($type,$id,$file,$autoload)
	{
		//	FIXME: implement the $type field somehow, but atm we only support google webfont
		if(!is_string($id) && $file) return;

		$this->font[$id] = array("file"=>$file);

		if($autoload) $this->addFont($id);
	}

	/**
	 * 	method:	addFont
	 *
	 * 	todo: write documentation
	 */
	public function addFont($id)
	{
		if(!isset($this->font[$id])) return;

		Amslib_Resource::addFont($id,$this->font[$id]["file"]);
	}

	/**
	 * 	method:	removeFont
	 *
	 * 	todo: write documentation
	 */
	public function removeFont($id)
	{
		Amslib_Resource::removeFont($id);
	}

	/**
	 * method: getHiddenParameters
	 *
	 * This outputs a block of hidden inputs for javascript or a web form to use when posting
	 * or processing data.
	 *
	 * returns:	A string of HTML, containing all the parameters
	 *
	 * notes:
	 * 	-	This is perhaps not the best way, because any value will be output, perhaps even secret information
	 * 		that the user puts accidentally and doesnt realise it'll be output as plain text in the HTML
	 * 	-	I hate the fact that I'm outputting HTML here, but I dont really have any other alternative which is cheap
	 * 		and relatively easy and clean, it's simpler, but not the best way I am sure.
	 *
	 * warning:
	 * 	-	do not change \" for single quote ' or similar, it's done like this to prevent certain types
	 * 		of bugs I found with certain combinations of code, it's important to prevent future problems
	 * 		to keep \" because it was the only way to prevent strings from becoming broken
	 */
	public function getHiddenParameters()
	{
		return $this->getValueData("input");
	}

	/**
	 * 	method:	getValueData
	 *
	 * 	todo: write documentation
	 */
	public function getValueData($type,$filter=false)
	{
		if($filter == false) $filter = array_keys($this->value);

		$output = false;

		switch($type){
			case "json":{
				$v = array();

				foreach($filter as $k){
					if(isset($this->value[$k])) $v[$k] = $this->value[$k];
				}

				$output = json_encode($v);
			}break;

			case "input":{
				$html = array();
				foreach($filter as $k){
					if(isset($this->value[$k])){
						$v = $this->value[$k];
						if(is_bool($v)) $v = $v ? "true" : "false";

						//	WARNING:
						//	do not change \" for single quote ' or similar, it's done like this to prevent
						//	certain types of bugs I found with certain combinations of code, it's important
						//	to prevent future problems to keep \" because it was the only way to prevent strings
						//	from becoming broken
						$html[] ="<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
					}
				}

				$output = implode("",$html);
			}break;
		}

		return $output ? "<div class='__amslib_mvc_values'>$output</div>" : "";
	}

	/**
	 * 	method:	setImage
	 *
	 * 	todo: write documentation
	 */
	public function setImage($id,$file)
	{
		$this->images[$id] = $file;

		$this->setValue("image:$id", $file);
	}

	//	NOTE: this function is being abused to be a generic "make relative url for a file" method for pretty much everything
	/**
	 * 	method:	getImage
	 *
	 * 	todo: write documentation
	 */
	public function getImage($id,$relative=true)
	{
		if(!is_string($id)) return false;

		//	Step 1: find the image inside the plugin
		if(isset($this->images[$id])) return $this->images[$id];

		//	Step 2: find the image relative to the website base (perhaps it's a path)
		$path = Amslib_Website::abs($this->location.$id);

		if(file_exists($path)){
			return $relative ? Amslib_Website::rel($path) : $path;
		}

		Amslib::errorLog("failed to find image",$id,$relative,$path,$this->location);

		//	Step 3: return false, image was not found
		return false;
	}

	/**
	 * 	method:	listImage
	 *
	 * 	todo: write documentation
	 */
	public function listImage($return_data=false)
	{
		return $return_data ? $this->images : array_keys($this->images);
	}

	/**
	 * 	method:	getAPI
	 *
	 * 	todo: write documentation
	 */
	public function getAPI($name)
	{
		return Amslib_Plugin_Manager::getAPI($name);
	}

	/**
	 * 	method:	getRoute
	 *
	 * 	todo: write documentation
	 */
	public function getRoute($name=NULL)
	{
		return Amslib_Router::getRoute($name,$this->getName());
	}

	/**
	 * 	method:	getRouteParam
	 *
	 * 	todo: write documentation
	 */
	public function getRouteParam($name=NULL,$default="")
	{
		return Amslib_Router::getRouteParam($name,$default);
	}

	/**
	 * 	method:	getRouteName
	 *
	 * 	todo: write documentation
	 */
	public function getRouteName()
	{
		return Amslib_Router::getName();
	}

	/**
	 * 	method:	getRouteResource
	 *
	 * 	todo: write documentation
	 */
	public function getRouteResource()
	{
		return Amslib_Router::getResource();
	}

	/**
	 * 	method:	getFullURL
	 *
	 * 	todo: write documentation
	 */
	public function getFullURL()
	{
		return Amslib_Website::rel(Amslib_Router::getPath());
	}

	/**
	 * 	method:	getURLParam
	 *
	 * 	todo: write documentation
	 */
	public function getURLParam($index=NULL,$default="")
	{
		return Amslib_Router::getURLParam($index,$default);
	}

	/**
	 * 	method:	getURL
	 *
	 * 	todo: write documentation
	 */
	public function getURL($name=NULL,$group=NULL)
	{
		return Amslib_Router_URL::getURL($name,$group?$group:$this->getName());
	}

	/**
	 * 	method:	getService
	 *
	 * 	todo: write documentation
	 */
	public function getService($name,$group=NULL)
	{
		return Amslib_Router_URL::getService($name,$group?$group:$this->getName());
	}

	/**
	 * 	method:	getServiceURL
	 *
	 * 	todo: write documentation
	 */
	public function getServiceURL($name,$group=NULL)
	{
		return Amslib_Router_URL::getServiceURL($name,$group?$group:$this->getName());
	}

	public function redirectTo($name,$group=NULL,$is_service=false)
	{
		$url = $is_service
			? $this->getURL($name,$group)
			: $this->getServiceURL($name,$group);

		Amslib_Website::redirect($url);
	}

	//	FIXME: we have to formalise what this slot code is supposed to do, opposed to what the view system already does.
	//	NOTE: 10/03/2013, still have no idea what this code is really supposed to do
	/**
	 * 	method:	setSlot
	 *
	 * 	todo: write documentation
	 */
	public function setSlot($name,$content,$index=NULL)
	{
		if($index){
			$this->slots[$name][$index] = $content;
		}else{
			$this->slots[$name] = $content;
		}
	}

	/**
	 * 	method:	getSlot
	 *
	 * 	todo: write documentation
	 */
	public function getSlot($name,$index=NULL)
	{
		if(isset($this->slots[$name])){
			if(is_array($this->slots[$name])){
				return ($index) ? $this->slots[$name][$index] : current($this->slots[$name]);
			}

			return $this->slots[$name];
		}

		return "";
	}

	/**
	 * method: setupService
	 *
	 * A customisation method which can do "something" based on what service is being called, at the very
	 * last second before the actual service is run, this might be to setup some static and protected
	 * data which is only available here and is not convenient to setup elsewhere.
	 *
	 * parameters:
	 * 	$plugin		-	The plugin for the service
	 * 	$service	-	The service being run inside the plugin
	 *
	 * notes:
	 * 	-	The parameters are only really used to identify what service is being run
	 */
	public function setupService($plugin,$service)
	{
		//	NOTE: by default, we don't setup anything.
	}
}
