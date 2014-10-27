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

class Amslib_Webservice_Response_Amslib extends Amslib_Webservice_Response_JSON
{
	//	response indicies
	const HD = "handlers";
	const SC = "success";
	//	handler indicies
	const VD = "validation.data";
	const VE = "validation.errors";
	const SD = "service.data";
	const SE = "service.errors";
	const DB = "database.errors";

	protected $handler;

	/**
	 * 	method:	pluginToName
	 *
	 * 	todo: write documentation
	 */
	protected function pluginToName($plugin)
	{
		if(is_object($plugin)) $plugin = get_class($plugin);
		if(!is_string($plugin) && !is_numeric($plugin)) $plugin = "__ERROR_PLUGIN_UNKNOWN__";

		return $plugin;
	}

	public function __construct($data=array())
	{
		parent::__construct($data);

		$this->handler = false;
	}

	/**
	 * 	method:	hasData
	 *
	 * 	todo: write documentation
	 *
	 * 	note: do not use getKey here, will result in an infinite loop
	 */
	public function hasResponse()
	{
		return
			parent::hasResponse() &&
			array_key_exists(self::SC,$this->response) &&
			array_key_exists(self::HD,$this->response);
	}

	/**
	 * 	method:	setStatus
	 *
	 * 	todo: write documentation
	 *
	 * 	note: do not use setKey in here, if your response is invalid, it'll fail to set the key because hasResponse will fail
	 */
	public function resetResponse()
	{
		$this->response = array(
			self::HD	=>	array(),
			self::SC	=>	false
		);
	}

	/**
	 * 	method:	setStatus
	 *
	 * 	todo: write documentation
	 */
	public function setStatus($status)
	{
		$this->setKey(self::SC,!!$status);
	}

	/**
	 * 	method:	getStatus
	 *
	 * 	todo: write documentation
	 */
	public function getStatus()
	{
		return $this->getKey(self::SC,false);
	}

	public function isHandler($id)
	{
		return is_numeric($id) && $this->getKey(array(self::HD,$id));
	}

	/**
	 * 	method:	countHandler
	 *
	 * 	todo: write documentation
	 */
	public function countHandler()
	{
		return ($data = $this->getKey(self::HD,NULL))
			? count($data)
			: false;
	}

	public function createHandler()
	{
		$this->handler = intval($this->countHandler());

		if(!$this->handler){
			$this->setKey(self::HD,array());
		}

		$this->setKey(array(self::HD,$this->handler),array());

		return $this->handler;
	}

	public function setHandler($id,$data)
	{
		if(!$this->isHandler($id)){
			return false;
		}

		$this->setKey(array(self::HD,intval($id)),$data);
	}

	/**
	 * 	method:	processHandler
	 *
	 * 	todo: write documentation
	 */
	public function processHandler($id=0)
	{
		$keys = array();

		if($data = $this->getKey(array(self::HD,$id))){
			$this->handler = $id;

			$keys = array_keys($data);
		}

		return $keys;
	}

	public function setHandlerData($plugin,$name,$value)
	{
		if($this->handler === false && $this->countHandler()){
			$this->processHandler();
		}

		if($this->handler === false){
			//	TODO: move into the logging system instead of here
			Amslib_Debug::log(__METHOD__,"INVALID HANDLER",Amslib_Debug::pdump(true,$this->handler));
			return NULL;
		}

		$plugin = self::pluginToName($plugin);

		$keys = array(self::HD,$this->handler,$plugin,self::SD);

		if($name == NULL){
			if(is_array($value)){
				$this->setKey($keys,$value);
			}else{
				Amslib_Debug::log(__METHOD__,"value was invalid array",$value);
			}
		}else if(is_numeric($name) || is_string($name)){
			$keys[] = $name;

			$this->setKey($keys,$value);
		}else{
			Amslib_Debug::log(__METHOD__,"name was invalid",$name);
		}
	}

	/**
	 * 	method:	getHandlerData
	 *
	 * 	todo: write documentation
	 */
	public function getHandlerData($plugin,$default,$name=NULL)
	{
		if(!$this->handler && $this->countHandler()){
			$this->processHandler();
		}

		if(!$this->handler){
			//	TODO: move into the logging system instead of here
			Amslib_Debug::log(__METHOD__,"INVALID HANDLER",Amslib_Debug::pdump(true,$this->handler));
			return NULL;
		}

		$keys = array(self::HD,$this->handler);

		if($plugin !== NULL)	$keys[] = self::pluginToName($plugin);
		if($name !== NULL)		$keys[] = $name;

		return $this->getKey($keys,$default);
	}

	/**
	 * 	method:	setValidationData
	 *
	 * 	todo: write documentation
	 */
	public function setValidationData($plugin,$data)
	{
		/*$this->data[self::pluginToName($plugin)][self::VD] = $data;
		 */
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
		/*
		$this->data[self::pluginToName($plugin)][self::VE] = $errors;
		*/
	}

	/**
	 * 	method:	getValidationData
	 *
	 * 	todo: write documentation
	 */
	public function getValidationData($plugin=NULL,$default=array())
	{
		return Amslib_Array::valid($this->getHandlerData($plugin,$default,self::VD));
	}

	/**
	 * 	method:	getValidationErrors
	 *
	 * 	todo: write documentation
	 */
	public function getValidationErrors($plugin=NULL,$default=false)
	{
		return $this->getHandlerData($plugin,$default,self::VE);
	}

	/**
	 * 	method:	getServiceData
	 *
	 * 	todo: write documentation
	 */
	public function getServiceErrors($plugin=NULL,$default=false)
	{
		return $this->getHandlerData($plugin,$default,self::SE);
	}

	public function setServiceData($plugin,$name,$value)
	{
		return $this->setHandlerData($plugin,$name,$value);
	}

	/**
	 * 	method:	getServiceData
	 *
	 * 	todo: write documentation
	 */
	public function getServiceData($plugin=NULL,$default=false,$key=false)
	{
		$data = $this->getHandlerData($plugin,$default,self::SD);

		return $key && $data && isset($data[$key]) ? $data[$key] : $data;
	}

	public function deleteServiceData($plugin,$name=NULL)
	{
		$keys = array(self::HD,$this->handler,$this->pluginToName($plugin),self::SD);

		if(is_string($name) && strlen($name)) $keys[] = $name;

		return $this->deleteKey($keys);
	}

	/**
	 * 	method:	moveData
	 *
	 * 	todo: write documentation
	 */
	public function renameServiceData($dst,$src,$name=NULL)
	{
		$this->setServiceData(
			$dst,
			$name,
			$this->deleteServiceData($src,$name)
		);
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
		/*if(!empty($errors)) $this->data[self::pluginToName($plugin)][self::DB] = $errors;
		 */
	}

	/**
	 * 	method:	getDatabaseErrors
	 *
	 * 	todo: write documentation
	 *
	 * 	NOTE: Be careful with this method, it could leak secret data if you didnt sanitise it properly of sensitive data
	 */
	public function getDatabaseErrors($plugin=NULL,$default=false)
	{
		return $this->getHandlerData($plugin,$default,self::DB);
	}

	/**
	 * 	method:	getDatabaseMessage
	 *
	 * 	todo: write documentation
	 */
	public function getDatabaseMessage($plugin=NULL,$default=false)
	{
		return Amslib_Array::filterKey(
			$this->getDatabaseErrors($plugin,$default),
			array("db_error","db_location")
		);
	}

	private function ____DEPRECATED_METHODS_BELOW(){}

	public function addHandler($data)
	{
		Amslib_Debug::log(__METHOD__,"DEPRECATED METHOD");

		$index = $this->createHandler();

		$this->setHandler($index,$data);
	}
}