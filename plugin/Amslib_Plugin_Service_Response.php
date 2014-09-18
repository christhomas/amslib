<?php
class Amslib_Plugin_Service_Response
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

	protected $source;
	protected $handler;

	protected function isValid($source)
	{
		return $source && isset($source["success"]) && isset($source["handlers"]);
	}

	/**
	 * 	method:	getHandlerData
	 *
	 * 	todo: write documentation
	 */
	protected function getHandlerData($plugin,$default,$key)
	{
		$plugin = self::pluginToName($plugin);

		if(!$this->handler && count($this->source[self::HD])){
			$this->processHandler();
		}

		if(!$this->handler){
			//	TODO: move into the logging system intead of here
			error_log("** ".__METHOD__." ** ".Amslib_Debug::var_dump($this->handler,true)." was invalid");
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

	public function __construct($source=NULL)
	{
		$this->source	=	false;
		$this->handler	=	false;

		if($this->isValid($source)){
			$this->source = $source;
		}else{
			$session = Amslib_SESSION::get(self::SR,false,$remove);

			if($this->isValid($session)) $this->source = $session;
		}
	}

	/**
	 * 	method:	hasData
	 *
	 * 	todo: write documentation
	 */
	public function hasData($remove=true)
	{
		return !!$this->source;
	}

	/**
	 * 	method:	getRawData
	 *
	 * 	todo: write documentation
	 */
	public function getRawData()
	{
		return $this->source;
	}

	/**
	 * 	method:	countHandler
	 *
	 * 	todo: write documentation
	 */
	public function countHandler()
	{
		return count($this->source[self::HD]);
	}

	/**
	 * 	method:	processHandler
	 *
	 * 	todo: write documentation
	 */
	public function processHandler($id=0)
	{
		$this->handler = isset($this->source[self::HD][$id])
			? Amslib_Array::valid($this->source[self::HD][$id])
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
		return isset($this->source[self::SC]) ? $this->source[self::SC] : false;
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