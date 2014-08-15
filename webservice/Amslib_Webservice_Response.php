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

class Amslib_Webservice_Response
{
	const SR = "/amslib/service";
	const VD = "validation/data";
	const VE = "validation/errors";
	const SD = "service/data";
	const SE = "service/errors";
	const DB = "database/errors";
	const HD = "handlers";
	const FB = "feedback";
	const SC = "success";

	protected $state;
	protected $response;
	protected $handler;

	/**
	 * 	method:	getHandlerData
	 *
	 * 	todo: write documentation
	 */
	protected function getHandlerData($plugin,$default,$key)
	{
		$plugin = self::pluginToName($plugin);

		if(!$this->handler && $this->countHandler()){
			$this->processHandler();
		}

		if(!$this->handler){
			//	TODO: move into the logging system instead of here
			error_log("** ".__METHOD__." ** ".Amslib::var_dump($this->handler,true)." was invalid");
			return NULL;
		}

		return isset($this->handler[$plugin]) && isset($this->handler[$plugin][$key])
			? Amslib_Array::valid($this->handler[$plugin][$key])
			: $default;
	}

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

	public function __construct($response=NULL)
	{
		$this->setState("raw",false);
		$this->setState("amslib",true);
		$this->setResponse($response);
	}

	public function setState($name,$state)
	{
		if(is_string($name) && strlen($name)){
			$this->state[$name] = !!$state;
		}

		if($name == "amslib") $this->setState("json",true);
	}

	public function getState($name)
	{
		return isset($this->state[$name]) ? $this->state[$name] : NULL;
	}

	public function getResponse($name=NULL)
	{
		return strlen($name) && isset($this->response[$name])
			? $this->response[$name]
			: ($name === NULL ? $this->response : NULL);
	}

	public function setResponse($response)
	{
		$this->response = array_fill_keys(array("amslib","json","raw"),false);

		if(!strlen($response)){
			if($response !== NULL){
				Amslib::errorLog(__METHOD__,"response was empty string");
			}

			return false;
		}

		if($this->getState("raw")){
			$this->response["raw"] = $response;

			return $this->response["raw"];
		}

		if($this->getState("json")){
			if(is_array($response)){
				$this->response["json"] = $response;
			}else{
				try{
					$this->response["json"] = json_decode($response,true);
				}catch(Exception $e){
					Amslib::errorLog(__METHOD__,"response was not a valid json string or had a problem to decode");

					return false;
				}
			}
		}

		if($this->getState("amslib")){
			$j = $this->response["json"];

			if($j && is_array($j) && isset($j["success"]) && isset($j["handlers"])){
				$this->response["amslib"] = $j;
			}

			//	If amslib response is enabled, reply that not the json response
			//	We set the second array here because this is what is used in the API to extract data from the array
			return $this->response["amslib"];
		}

		//	If amslib is not enabled, just reply the json response
		return $this->response["json"];
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
	 * 	method:	hasData
	 *
	 * 	todo: write documentation
	 * 	note: we have a parameter "remove" and yet nothing uses it?
	 */
	public function hasData($remove=true)
	{
		return $this->response["amslib"] && !!$this->response["amslib"];
	}

	/**
	 * 	method:	getRawData
	 *
	 * 	todo: write documentation
	 */
	public function getRawData()
	{
		return $this->response["amslib"];
	}

	/**
	 * 	method:	countHandler
	 *
	 * 	todo: write documentation
	 */
	public function countHandler()
	{
		return $this->response["amslib"] ? count($this->response["amslib"][self::HD]) : false;
	}

	/**
	 * 	method:	processHandler
	 *
	 * 	todo: write documentation
	 */
	public function processHandler($id=0)
	{
		$this->handler = $this->response["amslib"] && isset($this->response["amslib"][self::HD][$id])
			? Amslib_Array::valid($this->response["amslib"][self::HD][$id])
			: array();

		return array_keys($this->handler);
	}

	/**
	 * 	method:	getStatus
	 *
	 * 	todo: write documentation
	 */
	public function getStatus()
	{
		return $this->response["amslib"] && isset($this->response["amslib"][self::SC])
			? $this->response["amslib"][self::SC]
			: false;
	}

	/**
	 * 	method:	getValidationData
	 *
	 * 	todo: write documentation
	 */
	public function getValidationData($plugin,$default=array())
	{
		return Amslib_Array::valid($this->getHandlerData($plugin,$default,self::VD));
	}

	/**
	 * 	method:	getValidationErrors
	 *
	 * 	todo: write documentation
	 */
	public function getValidationErrors($plugin,$default=false)
	{
		return $this->getHandlerData($plugin,$default,self::VE);
	}

	/**
	 * 	method:	getServiceData
	 *
	 * 	todo: write documentation
	 */
	public function getServiceErrors($plugin,$default=false)
	{
		return $this->getHandlerData($plugin,$default,self::SE);
	}

	/**
	 * 	method:	getServiceData
	 *
	 * 	todo: write documentation
	 */
	public function getServiceData($plugin,$default=false,$key=false)
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
	public function getDatabaseErrors($plugin,$default=false)
	{
		return $this->getHandlerData($plugin,$default,self::DB);
	}

	/**
	 * 	method:	getDatabaseMessage
	 *
	 * 	todo: write documentation
	 */
	public function getDatabaseMessage($plugin,$default=false)
	{
		return Amslib_Array::filterKey($this->getDatabaseErrors($plugin,$default),array("db_error","db_location"));
	}
}