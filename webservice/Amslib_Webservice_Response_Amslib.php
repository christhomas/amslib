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
	const VD = "validation/data";
	const VE = "validation/errors";
	const SD = "service/data";
	const SE = "service/errors";
	const DB = "database/errors";
	const HD = "handlers";
	const FB = "feedback";
	const SC = "success";

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

	/**
	 * 	method:	getHandlerData
	 *
	 * 	todo: write documentation
	 */
	protected function getHandlerData($plugin,$default,$key)
	{
		if(!$this->handler && $this->countHandler()){
			$this->processHandler();
		}

		if(!$this->handler){
			//	TODO: move into the logging system instead of here
			Amslib_Debug::log(__METHOD__,"INVALID HANDLER",Amslib_Debug::pdump(true,$this->handler));
			return NULL;
		}

		if($plugin === NULL) return $this->handler;

		$plugin = self::pluginToName($plugin);

		return isset($this->handler[$plugin]) && isset($this->handler[$plugin][$key])
			? Amslib_Array::valid($this->handler[$plugin][$key])
			: $default;
	}

	public function __construct($data)
	{
		parent::__construct($data);

		$this->handler = false;
	}

	/**
	 * 	method:	hasData
	 *
	 * 	todo: write documentation
	 */
	public function hasData()
	{
		return parent::hasData() && isset($this->response["success"]) && isset($this->response["handlers"]);
	}

	public function deleteData($plugin,$name=NULL)
	{
		/*$plugin	=	self::pluginToName($plugin);
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
		*/
	}

	/**
	 * 	method:	countHandler
	 *
	 * 	todo: write documentation
	 */
	public function countHandler()
	{
		if(!isset($this->response[self::HD])) return false;

		return count($this->response[self::HD]);
	}

	public function setHandler($id,$data)
	{
		if(!is_array($data)) return false;

		$this->setKey(self::HD,array());

		$this->response[self::HD][intval($id)] = $data;
	}

	public function addHandler($data)
	{
		$this->setHandler($this->countHandler(),$data);
	}

	/**
	 * 	method:	processHandler
	 *
	 * 	todo: write documentation
	 */
	public function processHandler($id=0)
	{
		$this->handler = Amslib_Array::valid($this->getKey(array(self::HD,$id),array()));

		return array_keys($this->handler);
	}

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
}