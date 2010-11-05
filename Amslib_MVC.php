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
 * Version: 2.0
 * Project: amslib
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_MVC
{
	/*********************************
	 * string: $path
	 *
	 * The path to the MVC base path within the website filesystem
	 */
	protected $path;

	protected $database;
	protected $controller;
	protected $layout;
	protected $object;
	protected $view;
	protected $theme;
	protected $images;
	protected $service;
	protected $value;

	protected $translation;
	
	//	MVC Configuration
	protected $prefix			=	array();
	protected $dir				=	array();
	
	protected $widgetManager;
	protected $widgetName;
	protected $widgetPath;
	
	protected function getComponentPath($component,$name)
	{
		return "{$this->widgetPath}/{$this->dir[$component]}/{$this->prefix[$component]}{$name}.php";
	}
	
	public function __construct()
	{
		$this->path				=	"";
		$this->controller		=	array();
		$this->layout			=	array("default"=>false);
		$this->object			=	array();
		$this->view				=	array();
		$this->service			=	array();
		$this->value			=	array();
		$this->theme			=	array();
		$this->translation		=	array();
		$this->widgetManager	=	NULL;
		$this->widgetPath		=	NULL;
		$this->widgetName		=	NULL;
		
		$this->setupMVC("controller",	"controllers",	"Ct_");
		$this->setupMVC("layout",		"layouts",		"La_");
		$this->setupMVC("view",			"views",		"Vi_");
		$this->setupMVC("object",		"objects",		"");
		$this->setupMVC("service",		"services",		"Sv_");
		$this->setupMVC("theme",		"themes",		"Th_");
	}
	
	public function initialise()
	{
		//	You can implement this method in your API and it'll be called after the system has opened the plugin correctly
	}

	public function setupMVC($type,$dir,$prefix)
	{
		$this->prefix[$type]	=	$prefix;
		$this->dir[$type]		=	$dir;
	}

	public function setDatabase($database)
	{
		$this->database = $database;
	}
	
	public function getDatabase()
	{
		return $this->database;
	}

	public function setWidgetManager($widgetManager)
	{
		$this->widgetManager	=	$widgetManager;
		$this->widgetPath		=	$widgetManager->getWidgetPath();
	}

	public function getWidgetManager()
	{
		return $this->widgetManager;
	}

	public function setWidgetName($name)
	{
		$this->widgetName = $name;
		$this->setWidgetPath("{$this->widgetPath}/{$this->widgetName}");
	}

	public function getWidgetName()
	{
		return $this->widgetName;
	}

	public function setWidgetPath($path)
	{
		$this->widgetPath = $path;
	}

	public function getWidgetPath()
	{
		return $this->widgetPath;
	}

	public function setValue($name,$value)
	{
		$this->value[$name] = $value;
	}

	public function getValue($name)
	{
		return (isset($this->value[$name])) ? $this->value[$name] : NULL;
	}

	public function setController($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;
		
		$file = $this->getComponentPath("controller",$name);
		
		$this->controllers[$id] = $file;
	}

	public function getController($id)
	{
		return $this->controllers[$id];
	}

	public function setLayout($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;
		
		$file = $this->getComponentPath("layout",$name);

		$this->layout[$id] = $file;

		if($this->layout["default"] == false) $this->layout["default"] = $this->layout[$id];
	}

	public function getLayout($id=NULL)
	{
		if($id && isset($this->layout[$id])) return $this->layout[$id];

		return $this->layout;
	}

	public function setObject($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;
		
		$file = $this->getComponentPath("object",$name);

		$this->object[$id] = $file;
	}

	public function getObject($id,$singleton=false)
	{
		if(!isset($this->object[$id])) return false;	
		
		$status = Amslib::requireFile($this->object[$id],array("require_once"=>true));

		if(class_exists($id)){
			if($singleton) return call_user_func(array($id,"getInstance"));
				
			return new $id;
		}

		return false;
	}

	public function setView($id,$name)
	{
		if(!$id || strlen($id) == 0) $id = $name;
		
		$file = $this->getComponentPath("view",$name);
		
		$this->view[$id] = $file;
	}
	
	//	TODO: investigate: this method is very similar to render, can refactor??
	public function getView($id,$parameters=array())
	{
		if(isset($this->view[$id])){
			$view = $this->view[$id];
			
			$parameters["widget_manager"]	=	$this->widgetManager;
			$parameters["api"]				=	$this;
			
			ob_start();
			Amslib::requireFile($view,$parameters);
			return ob_get_clean();
		}
		
		return "";
	}
	
	public function setService($id,$file)
	{
		if(!$id || strlen($id) == 0) $id = $name;
		
		$this->service[$id] = $file;

		//	Set this as a service url for the javascript to acquire
		$this->setValue("service:$id", $file);
	}

	public function getService($id)
	{
		return (isset($this->service[$id])) ? $this->service[$id] : NULL;
	}
	
	public function callService($id)
	{
		$service = $this->getService($id);
		$service = Amslib_Filesystem::absolute($service);
		
		$parameters["widget_manager"]	=	$this->widgetManager;
		$parameters["api"]				=	$this;
		
		return Amslib::requireFile($service,$parameters);
	}
	
	public function setTranslation($name,$value)
	{
		$this->translation[$name] = $value;
		
		$this->setValue("translation:$name",$value);
	}
	
	public function getTranslation($name)
	{
		return (isset($this->translation[$name])) ? $this->translation[$name] : NULL;
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
		 $list = "";

		foreach($this->value as $k=>$v){
			if(is_bool($v)) $v = ($v) ? "true" : "false";

			//	WARNING:	do not change \" for single quote ' or similar, it's done like this to prevent
			//				certain types of bugs I found with certain combinations of code, it's important
			//				to prevent future problems to keep \" because it was the only way to prevent strings
			//				from becoming broken
			$list.="<input type=\"hidden\" name=\"$k\" value=\"$v\" />";
		}

		return "<div class='widget_parameters'>$list</div>";
	}

	public function copyService($src,$id,$copyAs=NULL)
	{
		if($copyAs === NULL) $copyAs = $id;

		$api = $this->widgetManager->getAPI($src);
		$this->setService($copyAs,$api->getService($id));
	}

	public function setImage($id,$file)
	{
		$this->images[$id] = $file;
		
		$this->setValue("image:$id", $file);
	}

	public function getImage($id)
	{
		return (isset($this->images[$id])) ? $this->images[$id] : false;
	}

	public function decorate($theme,$parameters)
	{
		if(isset($this->theme[$theme])){
			$theme = $this->theme[$theme];

			$parameters["widget_manager"]	=	$this->widgetManager;
			$parameters["api"]				=	$this;

			ob_start();
			Amslib::requireFile($theme,$parameters);
			return ob_get_clean();
		}

		return "";
	}

	/**************************************************************************
	 * method: render
	 *
	 * render the output from this MVC, the basic version just renders the
	 * first layout as defined in the XML, without a controller.
	 *
	 * returns:
	 * 	A string of HTML or empty string which represents the first layout
	 *
	 * notes:
	 * 	we only render the first layout in the widget, what happens if there are 10 layouts?
	 */
	public function render($layout="default",$parameters=array())
	{
		$layout							=	$this->layout[$layout];
		$parameters["widget_manager"]	=	$this->widgetManager;
		$parameters["api"]				=	$this;

		ob_start();
		Amslib::requireFile($layout,$parameters);
		return ob_get_clean();
	}

	static public function replyJSON($response)
	{
		header("Content-Type: application/json");
		//	MAYBE TODO:Can't use die anymore, because I might run child scripts
		die(json_encode($response));
	}

	static public function safeRedirect($location)
	{
		if(strlen($location)){
			header("Location: $location");
			die("waiting to redirect");
		}else{
			die("The 'return_url' parameter was empty, you cannot redirect to an empty location");
		}
	}
}