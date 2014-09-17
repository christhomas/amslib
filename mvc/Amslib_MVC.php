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

	/**
	 * variable: $globalViewParams
	 *
	 * The view parameters which will be given to every view that renders through this API object
	 */
	protected $globalViewParams;

	/**
	 * variable: $tempViewParams
	 *
	 * The temporary view parameters which will be overwritten each time a new view is rendered
	 */
	protected $tempViewParams;

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
	 *
	 * 	probably this should move to the service object and we should
	 * 	wrap it up here instead of implementing it here
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
		$this->object			=	array();
		$this->view				=	array();
		$this->stylesheet		=	array();
		$this->javascript		=	array();
		$this->translator		=	array();

		$this->value			=	array();
		$this->globalViewParams	=	array();
		$this->tempViewParams	=	array();
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
	 * 	note: I'm fairly sure this shouldn't need to return this, it's useless
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
	public function getPlugin($name=NULL)
	{
		return $name ? Amslib_Plugin_Manager::getPlugin($name) : $this->plugin;
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
	 * 	method:	getValueIfEmpty
	 *
	 * 	A small addition that will check the string is not/has (NULL,false) or
	 * 	has a string length before looking to obtain a configuration of default value
	 */
	public function getValueIfEmpty($value,$name=NULL,$default=NULL)
	{
		return $value !== NULL || $value !== false || strlen($value) ? $value : $this->getValue($name,$value);
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

	/**
	 * 	method:	setViewParam
	 *
	 * 	This method will accept the parameter array which was set when a view was rendered.
	 * 	The reason this method is useful, is because inside the view, you can use methods
	 * 	To obtain the view parameters instead of using the array interface.
	 *
	 * 	This might seem redundant, but sometimes it's useful to have a second way to access the data
	 * 	Also, another side effect is you can acquire the entire view parameter array as it was originally set
	 * 	Meaning you can daisy-chain the parameters to child views you render from the original parent view
	 * 	Which is sometimes important.
	 *
	 * 	params:
	 * 		$params	-	The array of parameters passed to renderView
	 * 		$global	-	Boolean true/false, whether to set the global or temporary view params
	 */
	public function setViewParam($params,$global=false)
	{
		if($global){
			$this->globalViewParams	= $params;
		}else{
			$this->tempViewParams	= $params;
		}
	}

	/**
	 * 	method:	addViewParam
	 *
	 * 	This method will add either an array or a single name/value pair to the existing array
	 * 	and it will overwrite whatever is already found there, if there is any overlap
	 *
	 * 	If you pass true/array as the parameters, it'll merge array into the existing set, meaning you
	 * 	can pass an entire array if you want to add them all
	 *
	 * 	parameters
	 * 		$name	-	The name of the parameter to add, or true to indicate $value holds an array
	 * 		$value	-	The value of the parameter to add, or an array of values if $name was true
	 * 		$global	-	Whether the information should be added to the global or temporal view variables
	 */
	public function addViewParam($name,$value,$global=false)
	{
		if($name === true && is_array($value)){
			if($global){
				$this->globalViewParams	=	array_merge($this->globalViewParams,$value);
			}else{
				$this->tempViewParams	=	array_merge($this->tempViewParams,$value);
			}
		}else if(is_string($name) && strlen(trim($name))){
			$name = trim($name);

			if($global){
				$this->globalViewParams[$name]	=	$value;
			}else{
				$this->tempViewParams[$name]	=	$value;
			}
		}
	}

	/**
	 * 	method:	getViewParam
	 *
	 * 	This method will return you either a named parameter from that view, or it was missing
	 * 	a default value, or if no name was given, it'll pass you back the entire parameter array
	 *
	 * 	This is useful when wanting to render child views from a parent view, but dont want to manually
	 * 	request or rebuild the original array
	 *
	 * 	params:
	 * 		$name		-	The name of the parameter to return, or NULL to return everything
	 * 		$default	-	The value to return, if the named parameter was not found, default: NULL
	 * 		$global		-	Boolean true/false, whether to return the value from the global view params or not
	 *
	 * 	returns:
	 * 		If name is null, will return the entire array, or if name was found, that
	 * 		value, or if not found, the default value
	 */
	public function getViewParam($name=NULL,$default=NULL,$global=false)
	{
		$vp = $global ? $this->globalViewParams : $this->tempViewParams;

		return $name ? ((array_key_exists($name,$vp)) ? $vp[$name] : $default) : $vp;
	}

	/**
	 * 	method:	setObject
	 *
	 * 	todo: write documentation
	 */
	public function setObject($id,$name)
	{
		$args = func_get_args();
		if(count($args) != 2){
			Amslib::errorLog("stack_trace",$args);
		}
		if(!$id || strlen($id) == 0) $id = $name;

		$this->object[$id] = $name;
	}

	/**
	 * 	method:	getObject
	 *
	 * 	todo: write documentation
	 * 	todo: why is singleton=true here and in Amslib_Plugin_Manager::getObject, singleton=false?
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

		foreach($rules as $k=>$v){
			if(isset($v[2]) && isset($v[2]["form_default"])){
				$values[$k] = $v[2]["form_default"];
			}
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
	public function renderView($view,$params=NULL)
	{
		if(!is_string($view) || !isset($this->view[$view]))
		{
			Amslib::errorLog("stack_trace","Unable to find view in structure",$this->getName(),$view,$this->view);

			return "";
		}

		if($params == "inherit") $params = $this->getViewParam();
		if(!is_array($params)) $params = array();

		$global = $this->getViewParam(NULL,NULL,true);
		if(!empty($global)) $params = array_merge($global,$params);

		//	Set the parameters that were used for rendering this view,
		//	useful when wanting to pass along to various child views.
		if(!empty($params)) $this->setViewParam($params);

		if(Amslib_Array::hasKeys($params,array("api","wt","ct"))){
			Amslib::errorLog(
					"ERROR: A reserved key was detected in this array,
					this might result in subtle errors, please remove 'api', 'wt' or 'ct',
					they are used by the framework to provide access to the api object, and the translators"
			);
		}

		$params["api"]	=	$this;
		//	FIXME: what if multiple translators of the same type are defined? they would start to clash
		$params["wt"]	=	$this->getTranslator("website");
		$params["ct"]	=	$this->getTranslator("content");

		return Amslib::requireFile($this->view[$view],$params,true);
	}

	/**
	 * 	method:	listViews
	 *
	 * 	todo: write documentation
	 */
	public function listViews($keys=true)
	{
		return $keys ? array_keys($this->view) : $this->view;
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
	public function setStylesheet($id,$file,$conditional=NULL,$autoload=NULL,$media=NULL,$position=NULL)
	{
		if(!is_string($id) && $file) return;

		$this->stylesheet[$id] = array(
			"file"			=>	$file,
			"conditional"	=>	$conditional,
			"media"			=>	$media,
			"autoload"		=>	$autoload,
			"position"		=>	$position
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
			$s = $this->stylesheet[$id];

			Amslib_Resource::addStylesheet($id,$s["file"],$s["conditional"],$s["media"],$s["position"]);
		}else{
			Amslib::errorLog(__METHOD__,"stylesheet not found",$id);
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
	public function setJavascript($id,$file,$conditional=NULL,$autoload=NULL,$position=NULL)
	{
		if(!is_string($id) && $file) return;

		$this->javascript[$id] = array(
			"file"			=>	$file,
			"conditional"	=>	$conditional,
			"autoload"		=>	$autoload,
			"position"		=>	$position
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

			Amslib_Resource::addJavascript($id,$j["file"],$j["conditional"],$j["position"]);
		}else{
			Amslib::errorLog(__METHOD__,"javascript not found",$id);
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

		//	Step 1: Is the image absolute, beginning with http?
		if(strpos($id,"http") === 0) return $id;

		//	Step 2: find the image inside the plugin
		if(isset($this->images[$id])) return $this->images[$id];

		//	Step 3: find the image relative to the website base (perhaps it's a path)
		$path = Amslib_Website::abs($this->location."/".$id);

		if(file_exists($path)){
			return $relative ? Amslib_Website::rel($path) : $path;
		}

		Amslib::errorLog("stack_trace","failed to find image",$id,$relative,$path,$this->location);

		//	Step 4: return false, image was not found
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
	public function getAPI($name=NULL)
	{
		return $name ? Amslib_Plugin_Manager::getAPI($name) : $this;
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
		return Amslib_Router_URL::getRouteParam($name,$default);
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
		return Amslib_Router_URL::getFullURL();
	}

	/**
	 * 	method:	getURLParam
	 *
	 * 	todo: write documentation
	 */
	public function getURLParam($index=NULL,$default="")
	{
		return Amslib_Router_URL::getURLParam($index,$default);
	}

	/**
	 * 	method:	getURL
	 *
	 * 	todo: write documentation
	 */
	public function getURL($name=NULL,$group=NULL,$lang="default",$domain=NULL)
	{
		return Amslib_Router_URL::getURL($name,$group?$group:$this->getName(),$lang,$domain);
	}

	/**
	 * 	method:	getService
	 *
	 * 	todo: write documentation
	 */
	public function getService($name,$group=NULL,$domain=NULL)
	{
		return Amslib_Router_URL::getService($name,$group?$group:$this->getName(),$domain);
	}

	/**
	 * 	method:	getServiceURL
	 *
	 * 	todo: write documentation
	 */
	public function getServiceURL($name,$group=NULL,$lang="default",$domain=NULL)
	{
		return Amslib_Router_URL::getServiceURL($name,$group?$group:$this->getName(),$lang,$domain);
	}

	/**
	 * 	method:	changeLanguage
	 *
	 * 	todo: write documentation
	 */
	public function changeLanguage($lang,$fullRoute=NULL,$key=NULL)
	{
		return Amslib_Router::changeLanguage($lang,$fullRoute,$key);
	}

	/**
	 * 	method:	getLanguage
	 *
	 * 	todo: write documentation
	 */
	public function getLanguage($test=NULL,$success=true,$failure=false)
	{
		$lang = Amslib_Router::getLanguage();

		if($test === NULL) return $lang;

		return $lang === $test ? $success : $failure;
	}

	/**
	 * 	method: externalURL
	 *
	 * todo: write documentation
	 */
	public function externalURL($url="")
	{
		return Amslib_Router_URL::externalURL($url);
	}

	public function redirectTo($name,$group=NULL,$is_service=false,$url=NULL)
	{
		if(!$url){
			$url = $is_service ? $this->getServiceURL($name,$group) : $this->getURL($name,$group);
		}

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
