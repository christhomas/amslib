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
 * File: Amslib_WidgetManager.php
 * Title: Widget manager for component based development
 * Version: 1.3
 * Project: amslib
 * 
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_WidgetManager
{
	protected $key_website_path;
	protected $key_widget_path;
	protected $key_amslib_path;
	
	protected $xdoc;
	protected $xpath;
	
	protected $paths;
	
	protected $layouts;
	protected $styles;
	protected $scripts;
	
	protected function getPath($name,$file)
	{
		$filename = $file->nodeValue;
		
		if(!$file->getAttribute("remote")){
			$path = "{$this->paths[$name]}/$name/$filename";

			if(!file_exists($path) && ($newpath = Amslib::findPath($filename))){
				$path = $newpath."/$filename";
			}
			
			return $this->getRelativePath($path);
		}
		
		return $filename;
	}
	
	public function __construct()
	{
		$this->key_website_path	=	"widget_website_path";
		$this->key_widget_path	=	"widget_path";
		$this->key_amslib_path	=	"widget_amslib_path";
	}
	
	public function &getInstance()
	{
		static $instance = NULL;
		
		if($instance === NULL) $instance = new self();	
		
		return $instance;
	}
	
	public function setupSystem($path)
	{
		@session_start();
		
		Amslib::insertSessionParam($this->key_widget_path,	$path);
		Amslib::insertSessionParam($this->key_website_path,	$_SERVER["DOCUMENT_ROOT"]);
		Amslib::insertSessionParam($this->key_amslib_path,	Amslib::locate());
		
		$this->setupWidget();
	}
	
	public function setupWidget()
	{
		@session_start();
		
		if(isset($_SESSION[$this->key_amslib_path])){
			require_once($_SESSION[$this->key_amslib_path]."/Amslib.php");
			
			Amslib::addIncludePath($this->getWebsitePath()."/".$this->getWidgetPath());
			Amslib::addIncludePath($this->getWebsitePath());
			Amslib::addIncludePath(Amslib::locate());
			
			return $this->getWebsitePath();
		}
		
		return "";
	}
	
	public function getRelativePath($path)
	{
		if(strpos($path,".") === 0) return $path;
		
		$root = $this->getWebsitePath();
		
		$path = str_replace($root,"",$root."/".$path);
		return str_replace("//","/",$path);
	}
	
	public function getWidgetPath()
	{
		return Amslib::sessionParam($this->key_widget_path);
	}
	
	public function getWebsitePath()
	{
		return Amslib::sessionParam($this->key_website_path);
	}
	
	public function getPackagePath($overridePath)
	{
		$path = $this->getWidgetPath();
		//	FIXME: is_dir might fail is the path is not relative, or findable without looking at the include path
		if(is_string($overridePath) && is_dir($overridePath)) $path = $overridePath;
		 
		return $path;
	}
	
	public function loadPackage($path,$name)
	{
		$xml = "$path/$name/package.xml";
		$xml = Amslib::findPath($xml)."/$xml";
		
		$this->xdoc = new DOMDocument('1.0', 'UTF-8');
		if(!$this->xdoc->load($xml)) die("XML FILE FAILED TO OPEN<br/>");
		$this->xdoc->preserveWhiteSpace = false;
		$this->xpath = new DOMXPath($this->xdoc);
	}
	
	public function loadConfiguration($path,$name)
	{
		$this->paths[$name]		=	$path;
		$this->layouts[$name]	=	$this->xpath->query("//package/layout/file");
		$this->scripts[$name]	=	$this->xpath->query("//package/javascript/file");
		$this->styles[$name]	=	$this->xpath->query("//package/stylesheet/file");
	}
	
	public function set($name,$data)
	{
		if(session_id() == "") @session_start();	
		Amslib::insertSessionParam("widgets/$name",$data);
	}
	
	public function get($name)
	{
		if(session_id() == "") @session_start();
		return Amslib::sessionParam("widgets/$name",NULL);
	}
	
	public function setAutoloader($file,$func)
	{
		$file = $this->getWebsitePath()."/$file";

		if(!file_exists($file))		return false;
		if(!function_exists($func))	return false;
		
		$this->set("autoloader/cwd",getcwd());
		$this->set("autoloader/file",$file);
		$this->set("autoloader/function",$func);
	}
	
	public function runAutoloader()
	{
		$cwd	=	$this->get("autoloader/cwd");
		$file	=	$this->get("autoloader/file");
		$func	=	$this->get("autoloader/function");
		
		//	Include the file for the autoloader
		if($file === NULL || !file_exists($file)) return false;
		chdir($cwd);
		Amslib::requireFile($file);
		
		//	Register the autoloader function
		if($func === NULL || !function_exists($func)) return false;
		return spl_autoload_register($func);
	}
	
	public function setDatabaseAccess($create,$arguments=array())
	{
		if(is_string($create) && !function_exists($create)) return false;
		
		if(is_array($create)){
			if(count($create) == 1 && !function_exists($create[0])) return false;
			if(count($create) == 2 && !method_exists($create[0],$create[1])) return false;
			if(count($create) == 2 && !method_exists($create[0],"locate")) return false;	
		}
		
		$database = call_user_func_array($create,$arguments);

		if($database){
			$this->set("database_access/create",json_encode($create));
			$this->set("database_access/arguments",json_encode($arguments));
			$this->set("database_access/location",$database->locate());
			
			return true;
		}
		
		return false;
	}
	
	public function getDatabaseAccess()
	{
		$create		=	$this->get("database_access/create");
		$arguments	=	$this->get("database_access/arguments");
		$location	=	$this->get("database_access/location");
		
		$create		=	json_decode($create,true);
		$arguments	=	json_decode($arguments,true);
		
		if($create === NULL || $location === NULL || $arguments === NULL) return false;

		require_once($location);
		
		if(is_string($create) && !function_exists($create)) return false;
		
		if(is_array($create)){
			if(count($create) == 1 && !function_exists($create[0])) return false;
			if(count($create) == 2 && !method_exists($create[0],$create[1])) return false;	
		}
	
		return call_user_func_array($create,$arguments);
	}
	
	public function load($name,$overridePath=NULL)
	{
		if(is_array($name)){
			foreach($name as $w) $this->load($w);
		}else{
			$path = $this->getPackagePath($overridePath);
			$this->loadPackage($path,$name);
			$this->loadConfiguration($path,$name);
		}
	}
	
	public function getStylesheets()
	{
		$styles = "";
		foreach($this->styles as $name=>$s)
		{
			for($a=0;$a<$s->length;$a++){
				$path = $this->getPath($name,$s->item($a));
				
				if($path != false){
					$styles .= "<link rel='stylesheet' type='text/css' href='$path' />";
				}
			}
		}
		
		return $styles;
	}
	
	public function getJavascripts()
	{
		$scripts = "";
		foreach($this->scripts as $name=>$s)
		{
			for($a=0;$a<$s->length;$a++){
				$path = $this->getPath($name,$s->item($a));
				
				if($path != false){
					$scripts .= "<script type='text/javascript' src='$path'></script>";	
				}
			}
		}
		
		return $scripts;
	}
	
	public function render($name,$parameters=array())
	{
		$output = false;
		
		if(isset($this->layouts[$name]))
		{
			$output = "";
			
			for($a=0;$a<$this->layouts[$name]->length;$a++){
				$l = $this->layouts[$name]->item($a);
				
				$parameters["widget_manager"] = $this;
				$path = "{$this->paths[$name]}/$name/$l->nodeValue";
				$path = Amslib::findPath($path)."/$path";
				
				ob_start();
				Amslib::requireFile($path,$parameters);
				$output .= ob_get_clean();
			}	
		}
		
		return $output;
	} 
}