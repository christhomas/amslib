<?php 
class Amslib_Plugin_Service3
{
	const S3 = "Amslib_Plugin_Service3";
	const VD = "validation/data";
	const VE = "validation/errors";
	const SD = "service/data";
	const SE = "service/errors";
	const DB = "database/errors";
	const PL = "plugins";
	
	protected $successURL;
	protected $failureURL;
	protected $successCB;
	protected $failureCB;
	protected $isAJAX;
	protected $data;
	
	//	Used in the website to retrieve the session data after processing
	static protected $serviceData = NULL;
	
	protected function successPOST()
	{
		$this->data["success"] = true;
		$this->setServiceData($this->data);

		Amslib_Website::redirect($this->getSuccessURL(),true);
	}
	
	protected function failurePOST()
	{
		$this->data["success"] = false;
		$this->setServiceData($this->data);
		
		Amslib_Website::redirect($this->getFailureURL(),true);
	}
	
	protected function successAJAX()
	{
		$this->data["success"] = true;
		Amslib_Website::outputJSON($this->data,true);
	}
		
	protected function failureAJAX()
	{
		$this->data["success"] = false;
		Amslib_Website::outputJSON($this->data,true);
	}
	
	public function __construct()
	{
		//	FIXME: we are hardcoding a route "home" which might not exist, this could be a bad idea
		$default_url		=	Amslib_Router3::getURL("home");	
		$return_url			=	Amslib::rchop(Amslib::postParam("return_url",$default_url),"?");
		
		$this->setSuccessURL(Amslib::rchop(Amslib::postParam("success_url",$return_url),"?"));
		$this->setFailureURL(Amslib::rchop(Amslib::postParam("failure_url",$return_url),"?"));
		$this->isAJAX		=	Amslib::postParam("return_ajax");
		
		$this->successCB	=	$this->isAJAX ? "successAJAX" : "successPOST";
		$this->failureCB	=	$this->isAJAX ? "failureAJAX" : "failurePOST";
		
		//	Reset the service data and session structures
		$this->data			=	array();
		$this->setServiceData(false);
	}
	
	public function setSuccessURL($url)
	{
		$this->successURL = Amslib_File::reduceSlashes("$url/");
	}
	
	public function getSuccessURL()
	{
		return $this->successURL;
	}
	
	public function setFailureURL($url)
	{
		$this->failureURL = Amslib_File::reduceSlashes("$url/");;	
	}
	
	public function getFailureURL()
	{
		return $this->failureURL;
	}
	
	public function setServiceData($data)
	{
		$_SESSION[self::S3] = $data;
	}
	
	public function execute($plugin,$method)
	{
		if(method_exists($plugin,$method)){
			$cb = call_user_func(array($plugin,$method),$this,$_POST)
				? $this->successCB 
				: $this->failureCB;
			
			call_user_func(array($this,$cb));
			
			die("FAILURE[p:$plugin][m:$method]-> All services should terminate with redirect or json");
		}else{
			die("FAILURE[p:$plugin][m:$method]-> method did not exist, so could not be called");
		}
	}
	
	public function setValidationData($plugin,$data)
	{
		$this->data[$plugin][self::VD] = $data;
		$this->data[self::PL][$plugin] = true;
	}
	
	public function setValidationErrors($plugin,$errors)
	{
		$this->data[$plugin][self::VE] = $errors;
		$this->data[self::PL][$plugin] = true;
	}
	
	//	NOTE: Be careful with this method, you could be pushing secret data
	public function setDatabaseErrors($plugin,$errors)
	{
		$this->data[$plugin][self::DB] = $errors;
		$this->data[self::PL][$plugin] = true;
	}
	
	public function setData($plugin,$name,$value)
	{
		$this->data[$plugin][self::SD][$name] = $value;
		$this->data[self::PL][$plugin] = true;
	}
	
	public function setError($plugin,$name,$value)
	{
		$this->data[$plugin][self::SE][$name] = $value;
		$this->data[self::PL][$plugin] = true;
	}
	
	/*****************************************************************************
	 * 	STATIC API TO RETRIEVE SESSION DATA
	*****************************************************************************/
	static public function hasData()
	{
		if(self::$serviceData === NULL) self::$serviceData = Amslib::sessionParam(self::S3,false,true);
		
		return self::$serviceData ? true : false;
	}
	
	static public function getStatus()
	{
		$success = isset(self::$serviceData["success"]) ? self::$serviceData["success"] : false;
		
		unset(self::$serviceData["success"]);
		
		return $success;
	}
	
	static public function listPlugins()
	{
		return array_keys(self::$serviceData[self::PL]);
	}	
	
	static public function getValidationData($plugin)
	{
		return isset(self::$serviceData[$plugin][self::VD]) 
			? Amslib_Array::valid(self::$serviceData[$plugin][self::VD]) 
			: false;
	}
	
	static public function getValidationErrors($plugin)
	{
		return isset(self::$serviceData[$plugin][self::VE]) 
			? Amslib_Array::valid(self::$serviceData[$plugin][self::VE]) 
			: false;
	}
	
	static public function getServiceErrors($plugin)
	{
		return isset(self::$serviceData[$plugin][self::SE]) 
			? Amslib_Array::valid(self::$serviceData[$plugin][self::SE]) 
			: false;
	}
	
	//	NOTE: Be careful with this method, it could leak secret data if you didnt sanitise it properly of sensitive data
	static public function getDatabaseErrors($plugin)
	{
		return isset(self::$serviceData[$plugin][self::DB]) 
			? Amslib_Array::valid(self::$serviceData[$plugin][self::DB]) 
			: false;
	}
	
	static public function getDatabaseMessage($plugin)
	{
		return isset(self::$serviceData[$plugin][self::DB])
			? self::$serviceData[$plugin][self::DB]["db_error"]
			: false;
	}
}