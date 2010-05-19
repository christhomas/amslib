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
 * Version: 2.0
 * Project: amslib
 * 
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_WidgetManager
{
	protected $key_document_root;	//	Webserver path
	protected $key_website_path;	//	Website path (relative to webserver path)
	protected $key_widget_path;		//	Widget path (relative to website path)
	protected $key_amslib_path;		//	Amslib path (relative to webserver path)
	
	protected $xdoc;
	protected $xpath;
	
	protected $api;
	protected $stylesheet;
	protected $javascript;
		
	protected function setAPI($name)
	{
		$list = $this->xpath->query("//package/object/api");
		
		$api = false;

		if($list->length > 0){
			$node = $list->item(0);
			if($node){
				Amslib::requireFile($this->getWidgetPath()."/$name/objects/{$node->nodeValue}.php");
				
				$api = call_user_func(array($node->nodeValue,"getInstance"));
			}
		}
		
		//	Create a default MVC object			
		if($api == false) $api = new Amslib_MVC();
		
		$this->api[$name] = $api;
		
		//	Set the widget manager parent
		$api->setWidgetManager($this);
		
		return $this->api[$name];
	}
	
	public function __construct()
	{
		$this->key_document_root	=	"widget_document_root";
		$this->key_website_path		=	"widget_website_path";
		$this->key_widget_path		=	"widget_path";
		$this->key_amslib_path		=	"widget_amslib_path";
		
		$this->stylesheet			=	array();
		$this->javascript			=	array();
	}
	
	public function &getInstance()
	{
		static $instance = NULL;
		
		if($instance === NULL) $instance = new Amslib_WidgetManager();	
		
		return $instance;
	}
	
	public function setupSystem($path,$websitePath="")
	{
		@session_start();

		Amslib::insertSessionParam($this->key_widget_path,		$path);
		Amslib::insertSessionParam($this->key_document_root,	$_SERVER["DOCUMENT_ROOT"]);
		Amslib::insertSessionParam($this->key_website_path,		$websitePath);
		Amslib::insertSessionParam($this->key_amslib_path,		Amslib::locate());
		
		$this->setupWidget();
	}
	
	public function setupWidget()
	{
		//	TODO: determine whether this method is required now with the API in place
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
	
	public function getRelativePath($path="")
	{	
		//	Path is already relative
		if(strpos($path,".") === 0) return $path;
		
		$root = $this->getWebsitePath();
		$path = str_replace($root,"",$root.$path);

		return str_replace("//","/",$path);
	}
	
	public function DEBUG_getRelativePath($path="")
	{	
		//	Path is already relative
		if(strpos($path,".") === 0) return $path;
		
		$root = $this->getWebsitePath();
		$path = str_replace($root,"",$root.$path);

		return str_replace("//","/",$path);
	}
	
	public function getWidgetPath($relative=false)
	{
		$root = $this->getWebsitePath();
		$path = Amslib::sessionParam($this->key_widget_path);
		
		//	Make sure widget path is relative to the website path
		$path = str_replace($root,"",$path);
		
		if($relative == false){
			$path = $root.$path;
		}
		
		return str_replace("//","/",$path);
	}
	
	public function getWebsitePath($relative=false)
	{
		$root = Amslib::sessionParam($this->key_document_root);
		$path = Amslib::sessionParam($this->key_website_path);
		
		//	Make sure the website path is relative to the document root
		$path = str_replace($root,"",$path);
		
		if($relative == false){
			$path = $root.$path;
		}
		
		return str_replace("//","/",$path);
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
	
	public function findResource($widget,$node)
	{
		if($node->getAttribute("remote")) return $node->nodeValue;
		
		$file = $node->nodeValue;
		$path = false;
		
		//	First try to find the file inside the widget itself
		$wpath = str_replace("//","/",$this->getWidgetPath()."/$widget/$file");
		//	if you found it inside the widget, use that version over any other
		if(file_exists($wpath)) $path = $wpath;

		//	If you didn't find it, search the path
		if($path == false){
			//	can't find the widget, search the path instead
			$fpath = Amslib::findPath($file);
			
			//	if you found it, assign it
			if($fpath) $path = "$fpath/$file";
		}
		
		//	relativise the path and reduce the double slashes to single ones.
		return str_replace("//","/",$this->getRelativePath($path));
	}
	
	public function loadConfiguration($path,$widget)
	{
		$api = $this->setAPI($widget);
		$api->setPath($path);
		
		$controllers = $this->xpath->query("//package/controllers/name");
		for($a=0;$a<$controllers->length;$a++){
			$name	=	$controllers->item($a)->nodeValue;
			$file	=	$this->getWidgetPath()."/$widget/controllers/Ct_{$name}.php";
			$api->setController($name,$file);
		}
		
		$layouts = $this->xpath->query("//package/layout/name");
		for($a=0;$a<$layouts->length;$a++){
			$name	=	$layouts->item($a)->nodeValue;
			$file	=	$this->getWidgetPath()."/$widget/layouts/La_{$name}.php";
			$api->setLayout($name,$file);
		}
		
		$views = $this->xpath->query("//package/view/name");
		for($a=0;$a<$views->length;$a++){
			$name	=	$views->item($a)->nodeValue;
			$file	=	$this->getWidgetPath()."/$widget/views/Vi_{$name}.php";
			$api->setView($name,$file);
		}
		
		$objects = $this->xpath->query("//package/object/name");
		for($a=0;$a<$objects->length;$a++){
			$name	=	$objects->item($a)->nodeValue;
			$file	=	$this->getWidgetPath()."/$widget/objects/$name.php";
			$api->setObject($name,$file);
		}
		
		$services = $this->xpath->query("//package/service/file");
		for($a=0;$a<$services->length;$a++){
			$name	=	$services->item($a)->getAttribute("name");
			$file	=	$services->item($a)->nodeValue;
			$file	=	$this->getWidgetPath(true)."/$widget/services/$file";
			$api->setService($name,$file);
		}
		
		$images = $this->xpath->query("//package/image/file");
		for($a=0;$a<$images->length;$a++){
			$name	=	$images->item($a)->getAttribute("name");
			$file	=	$this->findResource($widget,$images->item($a));
			$api->setImage($name,$file);
		}
		
		$javascript = $this->xpath->query("//package/javascript/file");
		for($a=0;$a<$javascript->length;$a++){
			$name	=	$javascript->item($a)->getAttribute("name");
			$file	=	$this->findResource($widget,$javascript->item($a));
			$this->setJavascript($name,$file);
		}
		
		$stylesheet = $this->xpath->query("//package/stylesheet/file");
		for($a=0;$a<$stylesheet->length;$a++){
			$name	=	$stylesheet->item($a)->getAttribute("name");
			$file	=	$this->findResource($widget,$stylesheet->item($a));
			$this->setStylesheet($name,$file);
		}
	}
	
	public function getAPI($name)
	{
		return $this->api[$name];
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
	
	public function setStylesheet($name,$file)
	{
		if($name && $file){
			$this->stylesheet[$name] = "<link rel='stylesheet' type='text/css' href='$file' />";
		}
	}
	
	public function getStylesheet()
	{
		return implode("\n",$this->stylesheet);
	}
	
	public function setJavascript($name,$file)
	{
		if($name && $file){
			$this->javascript[$name] = "<script type='text/javascript' src='$file'></script>";
		}	
	}
	
	public function getJavascript()
	{
		return implode("\n",$this->javascript);
	}
	
	public function render($name,$parameters=array())
	{
		$api = $this->getAPI($name);
		
		return ($api) ? $api->render($parameters) : false;
	}
}