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
 * file: Amslib_Plugin.php
 * title: Antimatter Plugin: Core plugin object
 * description: An object to manage how a plugin it loaded through to
 * 				how the MVC object is created and configured
 * version: 1.0
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/
class Amslib_Plugin
{
	protected $xpath;
	protected $name;
	protected $location;
	protected $packageXML;
	protected $api;
	protected $model;
	protected $routes;
	protected $dependencies;

	protected function createAPI()
	{
		$list = $this->xpath->query("//package/object/api");

		$api = false;

		if($list->length == 1){
			$node = $list->item(0);
			if($node){
				$object = $node->nodeValue;

				Amslib::requireFile("$this->location/objects/{$object}.php");

				$error = "FATAL ERROR(Amslib_Plugin::createAPI) Could not __ERROR__ for plugin '$this->name'<br/>";

				if(!class_exists($object)){
					//	class does not exist
					die(str_replace("__ERROR__","find class '{$object}'",$error));
				}

				if(!method_exists($object,"getInstance")){
					//	class exists, but method does not
					die(str_replace("__ERROR__","find the getInstance method in the API object '{$object}'",$error));
				}

				$api = call_user_func(array($object,"getInstance"));
			}else{
				//	ERROR: XML Node was invalid?
			}
		}else{
			//	ERROR: More than one API object is not allowed
		}

		//	An API Object was not created, so create a default Amslib_MVC3 object instead
		if($api == false) $api = new Amslib_MVC3();

		//	Setup the api with basic name and filesystem location
		$api->setLocation($this->getLocation());
		$api->setName($this->getName());

		//	Assign the model to the plugin
		$api->setModel($this->model);

		//	Load all the routes from the router system into the mvc layout
		foreach($this->routes as $name=>$route) $api->setRoute($name,$route);

		return $api;
	}

	protected function findResource($plugin,$node)
	{
		$resource = $node->nodeValue;

		//	TEST 1: If the resource has an attribute "absolute" don't process it, return it directly
		if($node->getAttribute("absolute")) return $resource;

		//	TEST 2:	look in the package directory for the file
		$path1 = str_replace("//","/","$this->location/$resource");
		if(file_exists($path1)) return Amslib_Website::rel($path1);

		//	TEST 3:	search the include path for the file
		$path2 = Amslib_Filesystem::find($resource,true);
		if(file_exists($path2)) return Amslib_Website::rel($path2);

		//	FAILED: you could not find the file
		return false;
	}

	protected function openPackage()
	{
		$document = new DOMDocument('1.0', 'UTF-8');
		if(@$document->load($this->packageXML)){
			$document->preserveWhiteSpace = false;
			$this->xpath = new DOMXPath($document);

			return true;
		}

		//	TODO: This needs to be better than "OMG WE ARE ALL GONNA DIE!!!"
		print("Amslib_Plugin::openPackage(): PACKAGE FAILED TO OPEN: file[$this->packageXML]<br/>");

		return false;
	}

	protected function loadDependencies()
	{
		$deps = $this->xpath->query("//package/requires/plugin");

		$this->dependencies = array();

		for($a=0;$a<$deps->length;$a++){
			$name = $deps->item($a)->nodeValue;

			$this->dependencies[$name] = Amslib_Plugin_Manager::add($name,$this->location);
		}
	}

	protected function processBlock($name,$value,$callback)
	{
		$nodes = $this->xpath->query("//package/$name/$value");
		for($a=0;$a<$nodes->length;$a++){
			$n		=	$nodes->item($a);
			$id		=	$n->getAttribute("id");

			if(in_array($name,array("controller","layout","view","object","service"))){
				$item	=	$n->nodeValue;
				$params	=	array($id,$item);
			}else{
				$cond	=	$n->getAttribute("condition");
				$auto	=	$n->getAttribute("autoload");
				$item	=	$this->findResource($this->name,$n);
				$params	=	array($id,$item,$cond,$auto);
			}

			call_user_func_array(array($this->api,$callback),$params);
		}
	}

	protected function loadConfiguration()
	{
		$this->api = $this->createAPI();

		//	If the API is not valid, return false to trigger an error.
		if(!$this->api) return false;

		$this->processBlock("controller",	"name",	"setController");
		$this->processBlock("layout",		"name",	"setLayout");
		$this->processBlock("view",			"name",	"setView");
		$this->processBlock("object",		"name",	"setObject");
		$this->processBlock("service",		"file",	"setService");
		$this->processBlock("image",		"file",	"setImage");
		$this->processBlock("javascript",	"file",	"setJavascript");
		$this->processBlock("stylesheet",	"file",	"setStylesheet");

		$this->api->initialise();
	}

	protected function loadRouter()
	{
		$source = Amslib_Router3::getObject("source:xml");
		$this->routes = $source->load($this->packageXML);
	}

	protected function initialiseModel()
	{
		$list = $this->xpath->query("//package/object/model");

		$this->model = false;

		if($list->length == 1){
			$node = $list->item(0);
			if($node){
				$object	=	$node->nodeValue;

				//	ATTEMPT 1: Is the object the model from the current plugin
				if($this->model == false){
					$file = "$this->location/objects/{$object}.php";

					if(file_exists($file)){
						Amslib::requireFile("$this->location/objects/{$object}.php");

						if(class_exists($object)){
							$this->model = call_user_func(array($object,"getInstance"));
						}
					}
				}

				//	ATTEMPT 2: Is the object already existing in the system
				if($this->model == false){
					try{
						$this->model = call_user_func(array($object,"getInstance"));
					}catch(Exception $e){}
				}
			}
		}

		if($this->model){
			Admin_Panel_Model::setConnection($this->model);
		}
	}

	protected function initialisePlugin(){	/*	By default, do nothing	*/	}
	protected function finalisePlugin(){	/*	By default, do nothing	*/	}

	public function __construct($name=NULL,$location=NULL)
	{
		//	If you passed the information to load the plugin
		//	automatically, then load the plugin automatically :)
		if($name && $location) $this->load($name,$location);
	}

	public function load($name,$location)
	{
		$this->name			=	$name;
		$this->location		=	$location;
		$this->packageXML	=	$location."/package.xml";

		if($this->openPackage()){
			$this->initialisePlugin();
			$this->initialiseModel();
			$this->loadDependencies();
			$this->loadRouter();
			$this->loadConfiguration();
			$this->finalisePlugin();

			return $this->api;
		}

		return false;
	}

	public function getLocation()
	{
		return $this->location;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getAPI()
	{
		return $this->api;
	}

	public function setAPI($api)
	{
		$this->api = $api;
	}

	public function getModel()
	{
		return $this->api->getModel();
	}

	public function setModel($model)
	{
		$this->api->setModel($model);
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}