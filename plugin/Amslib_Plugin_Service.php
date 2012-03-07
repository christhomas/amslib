<?php
class Amslib_Plugin_Service
{
	const S3 = "amslib/service/validate";
	const VD = "validation/data";
	const VE = "validation/errors";
	const SD = "service/data";
	const SE = "service/errors";
	const FB = "service/feedback";
	const DB = "database/errors";
	const PL = "plugins";
	const SC = "success";

	protected $successURL;
	protected $failureURL;
	protected $successCB;
	protected $failureCB;
	protected $isAJAX;
	protected $data;

	//	Used in the website to retrieve the session data after processing
	static protected $serviceData = NULL;

	static protected function getData($plugin,$default,$key)
	{
		return isset(self::$serviceData[$plugin]) && isset(self::$serviceData[$plugin][$key])
			? Amslib_Array::valid(self::$serviceData[$plugin][$key])
			: $default;
	}

	protected function successPOST()
	{
		$this->data[self::SC] = true;
		$this->setServiceData($this->data);

		Amslib_Website::redirect($this->getSuccessURL(),true);
	}

	protected function failurePOST()
	{
		$this->data[self::SC] = false;
		$this->setServiceData($this->data);

		Amslib_Website::redirect($this->getFailureURL(),true);
	}

	protected function successAJAX()
	{
		$this->data[self::SC] = true;
		Amslib_Website::outputJSON($this->data,true);
	}

	protected function failureAJAX()
	{
		$this->data[self::SC] = false;
		Amslib_Website::outputJSON($this->data,true);
	}

	public function __construct()
	{
		//	FIXME: we are hardcoding a route "home" which might not exist, this could be a bad idea
		$default_url		=	Amslib_Router::getURL("home");
		$return_url			=	Amslib::rchop(Amslib::postParam("return_url",$default_url),"?");

		$this->setSuccessURL(Amslib::rchop(Amslib::postParam("success_url",$return_url),"?"));
		$this->setFailureURL(Amslib::rchop(Amslib::postParam("failure_url",$return_url),"?"));
		
		//	Reset the service data and session structures
		$this->data			=	array();
		$this->showFeedback();
		$this->setServiceData(false);
		$this->setAjax(Amslib::postParam("return_ajax",false));
	}
	
	public function setAjax($status)
	{
		$this->isAJAX		=	$status;
		$this->successCB	=	$this->isAJAX ? "successAJAX" : "successPOST";
		$this->failureCB	=	$this->isAJAX ? "failureAJAX" : "failurePOST";
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
		$this->failureURL = Amslib_File::reduceSlashes("$url/");
	}

	public function getFailureURL()
	{
		return $this->failureURL;
	}
	
	public function setReturnURL($url)
	{
		$this->successURL = $this->failureURL = Amslib_File::reduceSlashes("$url/");
	}

	public function setServiceData($data)
	{
		$_SESSION[self::S3] = $data;
	}

	public function showFeedback()
	{
		$this->data[self::FB] = true;
	}

	public function hideFeedback()
	{
		$this->data[self::FB] = false;
	}

	public function execute($plugin,$method)
	{
		if(method_exists($plugin,$method)){
			$cb = call_user_func(array($plugin,$method),$this,$_POST)
				? $this->successCB
				: $this->failureCB;

			call_user_func(array($this,$cb));

			die("FAILURE[p:".get_class($plugin)."][m:$method]-> All services should terminate with redirect or json");
		}else{
			die("FAILURE[p:".get_class($plugin)."][m:$method]-> method did not exist, so could not be called");
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
		if(!empty($errors)){
			$this->data[$plugin][self::DB] = $errors;
			$this->data[self::PL][$plugin] = true;
		}
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
	static public function displayFeedback()
	{
		return self::$serviceData[self::FB];
	}

	static public function hasData($remove=true)
	{
		if(self::$serviceData === NULL) self::$serviceData = Amslib::sessionParam(self::S3,false,$remove);

		return self::$serviceData ? true : false;
	}

	static public function getRawData()
	{
		return self::$serviceData;
	}

	static public function getStatus()
	{
		$success = isset(self::$serviceData[self::SC]) ? self::$serviceData[self::SC] : false;

		unset(self::$serviceData[self::SC]);

		return $success;
	}

	static public function listPlugins()
	{
		return isset(self::$serviceData[self::PL]) ? array_keys(self::$serviceData[self::PL]) : array();
	}

	static public function getValidationData($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::VD);
	}

	static public function getValidationErrors($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::VE);
	}

	static public function getServiceErrors($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::SE);
	}

	static public function getServiceData($plugin,$default=false,$key=false)
	{
		$data = self::getData($plugin,$default,self::SD);

		return $key && $data && isset($data[$key]) ? $data[$key] : $data;
	}

	//	NOTE: Be careful with this method, it could leak secret data if you didnt sanitise it properly of sensitive data
	static public function getDatabaseErrors($plugin,$default=false)
	{
		return self::getData($plugin,$default,self::DB);
	}

	static public function getDatabaseMessage($plugin,$default=false)
	{
		return Amslib_Array::filterKey(self::getDatabaseErrors($plugin,$default),array("db_error","db_location"));
	}
}