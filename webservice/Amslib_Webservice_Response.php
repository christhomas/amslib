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

	protected $mode;
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

	protected function setAmslibKey($type,$value=NULL)
	{
		if(!is_array($this->response)){
			$this->response = $this->getEmptyResponse();
		}

		if(!isset($this->response["amslib"])){
			$this->response["amslib"] = array();
		}

		if(!isset($this->response["amslib"][$type])){
			$this->response["amslib"][$type] = $value;
		}
	}

	protected function getAmslibKey($key,$default=NULL)
	{
		if(!$this->response || !is_array($this->response)){
			return $default;
		}

		if(!isset($this->response["amslib"]) || !is_array($this->response["amslib"])){
			return $default;
		}

		$a = $this->response["amslib"];
		if(is_string($key)) $key = array($key);
		foreach($key as $index){
			if(!isset($a[$index])) return $default;

			$a = $a[$index];
		}

		return $a;
	}

	public function __construct($response=NULL)
	{
		$this->setMode("raw",false);
		$this->setMode("amslib",true);
		$this->setData($response);
	}

	public function setMode($name,$mode)
	{
		if(is_string($name) && strlen($name)){
			$this->mode[$name] = !!$mode;
		}

		if($name == "amslib") $this->setMode("json",true);
	}

	public function getMode($name)
	{
		return isset($this->mode[$name]) ? $this->mode[$name] : NULL;
	}

	public function getEmptyResponse()
	{
		return array_fill_keys(array("amslib","json","raw"),false);
	}

	public function getData($name=NULL)
	{
		return strlen($name) && isset($this->response[$name])
			? $this->response[$name]
			: ($name === NULL ? $this->response : NULL);
	}

	public function setData($response)
	{
		$this->response = $this->getEmptyResponse();

		if(!strlen($response)){
			if($response !== NULL){
				Amslib::errorLog(__METHOD__,"response was empty string");
			}

			return false;
		}

		if($this->getMode("raw")){
			$this->response["raw"] = $response;

			return $this->response["raw"];
		}

		if($this->getMode("json")){
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

		if($this->getMode("amslib")){
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
	 */
	public function hasData($name="amslib")
	{
		return $name && $this->response && isset($this->response[$name]) && is_array($this->response[$name]);
	}

	/**
	 * 	method:	countHandler
	 *
	 * 	todo: write documentation
	 */
	public function countHandler()
	{
		if(!$this->response["amslib"] || !isset($this->response["amslib"][self::HD])){
			return false;
		}

		return count($this->response["amslib"][self::HD]);
	}

	public function setHandler($id,$data)
	{
		if(!is_array($data)) return false;

		$this->setAmslibKey(self::HD,array());

		$id = intval($id);

		$this->response["amslib"][self::HD][$id] = $data;
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
		$this->handler = Amslib_Array::valid($this->getAmslibKey(array(self::HD,$id),array()));

		return array_keys($this->handler);
	}

	public function setStatus($status)
	{
		$this->setAmslibKey(self::SC,!!$status);
	}

	/**
	 * 	method:	getStatus
	 *
	 * 	todo: write documentation
	 */
	public function getStatus()
	{
		return $this->getAmslibKey(self::SC,false);
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