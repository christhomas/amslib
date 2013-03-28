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
 * File: Amslib_MVC.php
 * Title: Model/View/Controller implementation for use with Amslib projects
 * Version: 6.0
 * Project: amslib
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

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

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

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

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setLocation($location)
	{
		$this->location = $location;
	}

	public function initialise()
	{
		return $this;
	}

	public function setModel($model)
	{
		$this->model = $model;
	}

	public function getModel()
	{
		return $this->model;
	}

	public function setPlugin($plugin)
	{
		$this->plugin = $plugin;
	}

	public function getPlugin()
	{
		return $this->plugin;
	}

	public function setValue($name,$value)
	{
		if(is_string($name) && strlen($name)){
			$this->value[$name] = $value;
		}
	}

	public function getValue($name=NULL,$default=NULL)
	{
		if(is_string($name) && strlen($name)){
			return (isset($this->value[$name])) ? $this->value[$name] : $this->getViewParam($name,$default);
		}

		//	if no value was requested and default is null, return ALL the values
		return $name == NULL && $default == NULL ? $this->value : $default;
	}

	public function setFields($name,$value)
	{
		$name = "validate/$name";

		$f = $this->getValue($name,array());

		if(!is_array($value)) $value = array();

		$this->setValue($name,array_merge($f,$value));
	}

	public function getFields($name)
	{
		return $this->getValue("validate/$name",array());
	}

	public function listValues()
	{
		return array_keys($this->value);
	}

	//	TODO: need to explain the difference between a value and a view param
	public function setViewParam($parameters)
	{
		$this->viewParams = $parameters;
	}

	public function getViewParam($name,$default=NULL)
	{
		return (isset($this->viewParams[$name])) ? $this->viewParams[$name] : $default;
	}

	public function setObject($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->object[$id] = $name;
	}

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

	public function listObjects()
	{
		return array_keys($this->object);
	}

	public function setView($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;

		$this->view[$id] = $name;

		if(!isset($this->view["default"])) $this->view["default"] = $name;
	}

	public function getView($id)
	{
		if($id && isset($this->view[$id])) return $this->view[$id];

		return $this->view;
	}

	public function hasView($id)
	{
		return ($id && isset($this->view[$id]));
	}

	public function render($id="default",$parameters=array())
	{
		return $this->renderView($id,$parameters);
	}

	public function renderView($id,$parameters=array())
	{
		if(is_string($id) && isset($this->view[$id]))
		{
			//	What are view parameters for again??
			if(!empty($parameters)) $this->setViewParam($parameters);

			//	TODO: what happens if api, _w and _c are already defined and you just overwrote them?
			//	NOTE: this shouldn't happen, they are special so nobody should use them
			//	NOTE: perhaps we can warn people when they do this? or perhaps move our keys to a more unique "namespace"
			//	FIXME: what if multiple translators of the same type are defined? they would start to clash
			$parameters["api"]	=	$this;
			$parameters["_w"]	=	$this->getTranslator("website");
			$parameters["_c"]	=	$this->getTranslator("content");

			return Amslib::requireFile($this->view[$id],$parameters,true);
		}

		return "";
	}

	public function listViews()
	{
		return array_keys($this->view);
	}

	public function setTranslator($name,$translator)
	{
		$this->translator[$name] = $translator;
	}

	public function getTranslator($name)
	{
		return (isset($this->translator[$name])) ? $this->translator[$name] : reset($this->translator);
	}

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

	public function listStylesheet($key="file")
	{
		return $key !== false ? Amslib_Array::pluck($this->stylesheet,$key) : $this->stylesheet;
	}

	public function getStylesheet($id,$file=true)
	{
		return isset($this->stylesheet[$id])
			? $file ? $this->stylesheet[$id]["file"] : $this->stylesheet[$id]
			: "";
	}

	public function removeStylesheet($id)
	{
		Amslib_Resource::removeStylesheet($id);
	}

	public function setJavascript($id,$file,$conditional=NULL,$autoload=NULL)
	{
		if(!is_string($id) && $file) return;

		$this->javascript[$id] = array(
			"file"			=>	$file,
			"conditional"	=>	$conditional,
			"autoload"		=>	$autoload
		);
	}

	public function addJavascript($id)
	{
		if(isset($this->javascript[$id])){
			$j = $this->javascript[$id];
			Amslib_Resource::addJavascript($id,$j["file"],$j["conditional"]);
		}
	}

	public function listJavascript($key="file")
	{
		return $key !== false ? Amslib_Array::pluck($this->javascript,$key) : $this->javascript;
	}

	public function getJavascript($id,$file=true)
	{
		return isset($this->javascript[$id])
			? $file ? $this->javascript[$id]["file"] : $this->javascript[$id]
			: "";
	}

	public function removeJavascript($id)
	{
		Amslib_Resource::removeJavascript($id);
	}

	public function autoloadResources()
	{
		foreach($this->stylesheet as $k=>$s) if($s["autoload"]){
			$this->addStylesheet($k);
		}

		foreach($this->javascript as $k=>$j) if($j["autoload"]){
			$this->addJavascript($k);
		}
	}

	public function setFont($type,$id,$file,$autoload)
	{
		//	FIXME: implement the $type field somehow, but atm we only support google webfont
		if(!is_string($id) && $file) return;

		$this->font[$id] = array("file"=>$file);

		if($autoload) $this->addFont($id);
	}

	public function addFont($id)
	{
		if(!isset($this->font[$id])) return;

		Amslib_Resource::addFont($id,$this->font[$id]["file"]);
	}

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

	public function setImage($id,$file)
	{
		$this->images[$id] = $file;

		$this->setValue("image:$id", $file);
	}

	//	NOTE: this function is being abused to be a generic "make relative url for a file" method for pretty much everything
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

		//	Step 3: return false, image was not found
		return false;
	}

	public function getAPI($name)
	{
		return Amslib_Plugin_Manager::getAPI($name);
	}

	public function getRoute($name=NULL)
	{
		return Amslib_Router::getRoute($name,$this->getName());
	}

	public function getFullURL()
	{
		return Amslib_Router::getPath();
	}

	public function getURL($name=NULL,$group=NULL)
	{
		return Amslib_Router::getURL($name,$group?$group:$this->getName());
	}

	public function getService($name,$group=NULL)
	{
		return Amslib_Router::getService($name,$group?$group:$this->getName());
	}

	public function getServiceURL($name,$group=NULL)
	{
		return Amslib_Router::getServiceURL($name,$group?$group:$this->getName());
	}

	//	FIXME: we have to formalise what this slot code is supposed to do, opposed to what the view system already does.
	//	NOTE: 10/03/2013, still have no idea what this code is really supposed to do
	public function setSlot($name,$content,$index=NULL)
	{
		if($index){
			$this->slots[$name][$index] = $content;
		}else{
			$this->slots[$name] = $content;
		}
	}

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
