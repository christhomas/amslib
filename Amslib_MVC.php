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
	
	//	MVC Configuration
	protected $controllerDir	=	"controllers";
	protected $controllerPrefix	=	"Ct_";
	protected $layoutDir		=	"layouts";
	protected $layoutPrefix		=	"La_";	
	protected $viewDir			=	"views";
	protected $viewPrefix		=	"Vi_";
	protected $themeDir			=	"themes";
	protected $themePrefix		=	"Th_";
	protected $objectDir		=	"objects";
	protected $objectPrefix		=	"";
	protected $serviceDir		=	"services";
	protected $servicePrefix	=	"Sv_";
	
	protected $widgetManager;
	protected $widgetName;
	protected $widgetPath;
	
	protected function initialise(){}
	
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
		$this->widgetManager	=	NULL;
		$this->widgetPath		=	NULL;
		$this->widgetName		=	NULL;
	}
	
	public function setupMVC($type,$dir,$prefix)
	{
		switch($type){
			case "controllers":{
				$this->controllerDir	=	$dir;
				$this->controllerPrefix	=	$prefix;
			}break;
			
			case "layouts":{
				$this->layoutDir		=	$dir;
				$this->layoutPrefix		=	$prefix;
			}break;
			
			case "views":{
				$this->viewDir			=	$dir;
				$this->viewPrefix		=	$prefix;
			}break;
			
			case "themes":{
				$this->themeDir			=	$dir;
				$this->themePrefix		=	$prefix;
			}
			
			case "objects":{
				$this->objectDir		=	$dir;
				$this->objectPrefix		=	$prefix;
			}break;
			
			case "services":{
				$this->serviceDir		=	$dir;
				$this->servicePrefix	=	$prefix;
			}break;
		}
	}
	
	public function setDatabase($database)
	{
		$this->database = $database;
	}
	
	public function setWidgetManager($widgetManager)
	{
		$this->widgetManager	=	$widgetManager;
		$this->widgetPath		=	$widgetManager->getWidgetPath();
		
		$this->initialise();
	}
	
	public function getWidgetManager()
	{
		return $this->widgetManager;
	}
	
	public function setWidgetName($name)
	{
		$this->widgetName	=	$name;
		$this->widgetPath	=	"{$this->widgetPath}/{$this->widgetName}";
	}
	
	public function setPath($path)
	{
		//	NOTE: What is this for?
		$this->path = $path;
	}
	
	public function getPath()
	{
		//	NOTE: What is this for?
		return $this->path;
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
		$file = "{$this->widgetPath}/{$this->controllerDir}/{$this->controllerPrefix}{$name}.php";
		
		$this->controllers[$id] = $file;
	}
	
	public function getController($id)
	{
		return $this->controllers[$id];
	}
	
	public function setLayout($id,$name)
	{
		$file = "{$this->widgetPath}/{$this->layoutDir}/{$this->layoutPrefix}{$name}.php";

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
		$file = "{$this->widgetPath}/{$this->objectDir}/{$this->objectPrefix}{$name}.php";
				
		$this->object[$id] = $file;
	}
	
	public function getObject($id,$singleton=false)
	{
		if(isset($this->object[$id]) && Amslib::requireFile($this->object[$id]))
		{
			if($singleton){
				return call_user_func(array($id,"getInstance"));
			}
			
			return new $id;
		}
		
		return false;
	}
	
	public function setService($id,$file)
	{
		$this->service[$id] = $file;
		
		//	Set this as a service url for the javascript to acquire
		$this->setValue("service:$id", $file);
	}
	
	public function getService($id)
	{
		return (isset($this->service[$id])) ? $this->service[$id] : false;
	}
	
	public function getHiddenParameters()
	{
		 $list = "";
		
		foreach($this->value as $k=>$v){
			if(is_bool($v)) $v = ($v) ? "true" : "false";
			
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
	}
	
	public function getImage($id)
	{
		return (isset($this->images[$id])) ? $this->images[$id] : false;
	}
	
	public function setView($id,$name)
	{
		$file = "{$this->widgetPath}/{$this->viewDir}/{$this->viewPrefix}{$name}.php";
		
		$id = (strlen($id)) ? $id : $name;
		
		$this->view[$id] = $file;
	}
	
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
	
	public function setTheme($id,$name)
	{
		$file = "{$this->widgetPath}/{$this->themeDir}/{$this->themePrefix}{$name}.php";
				
		$this->theme[$id] = $file;
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
	
	public function replyJSON($response)
	{
		header("Content-Type: application/json");
		$response = json_encode($response);
		die($response);
	}
}