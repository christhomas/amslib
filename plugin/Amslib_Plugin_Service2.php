<?php 
class Amslib_Plugin_Service2
{
	const FORM_SUCCESS			=	"form/success";
	const FORM_FAILURE			=	"form/failure";
	const FORM_AJAX				=	"form/ajax";
	
	const VALIDATION_SUCCESS	=	"validation/success";
	const VALIDATION_ERRORS		=	"validation/errors";
	const VALIDATION_DATA		=	"validation/data";
	const VALIDATION_COMPLETE	=	"validation/complete";
	
	const SERVICE_DATA			=	"service/data";
	const SERVICE_ERRORS		=	"service/errors";
	const SERVICE_DEPTH			=	"service/depth";
	
	protected $validator;
	protected $errors;
	protected $data;
		
	//	Internally-Chained service callbacks
	protected function returnTrue()
	{	
		return true;
	}
	
	protected function returnFalse()
	{
		//	Here we should also be making available all the failure data for the parent service to pickup
		//	I don't have any idea actually how to make that happen
		//	how can I accumulate data from various services which might be chained together?
		//	wouldn't a similar error in an "outer" service overwrite the error from the inner class?
		//	perhaps this wouldn't be an issue, because the outer service error needs fixing, resubmitting would just
		//		cause the inner error to be exposed next time	
		return false;
	}
	
	protected function successPOST()
	{
		Amslib::insertSessionParam(self::VALIDATION_SUCCESS,true);
		Amslib::insertSessionParam(self::SERVICE_DATA,$this->data);
		
		Amslib_Website::redirect(self::getSuccessURL()."?".http_build_query($this->errors));
	}
	
	protected function successAJAX()
	{
		Amslib_Website::outputJSON(array(
			self::VALIDATION_SUCCESS	=>	true,
			self::VALIDATION_DATA		=>	$this->data
		),true);
	}
	
	protected function failurePOST()
	{
		Amslib::insertSessionParam(self::VALIDATION_SUCCESS,false);
		Amslib::insertSessionParam(self::VALIDATION_DATA,$this->data);
		Amslib::insertSessionParam(self::VALIDATION_ERRORS,$this->validator->getErrors());
		Amslib::insertSessionParam(self::SERVICE_ERRORS,$this->errors);
		
		Amslib_Website::redirect(self::getFailureURL()."?".http_build_query($this->errors));
	}
	
	protected function failureAJAX()
	{
		Amslib_Website::outputJSON(array(
			self::VALIDATION_SUCCESS	=>	false,
			self::VALIDATION_DATA		=>	$this->data,
			self::VALIDATION_ERRORS		=>	$this->validator->getErrors(),
			self::SERVICE_ERRORS		=>	$this->errors
		),true);
	}
	
	public function __construct()
	{
		$this->validator	=	new Amslib_Validator3($_POST);
		$this->errors		=	array();
		$this->data			=	array();
		
		$this->setServiceCallbacks();
		
		//	NOTE: This might not work with supporting child service calls
		//	Reset certain session data structures which are not supposed to exist more than once
		Amslib::insertSessionParam(self::VALIDATION_ERRORS,false);
		Amslib::insertSessionParam(self::VALIDATION_DATA,false);
		Amslib::insertSessionParam(self::VALIDATION_SUCCESS,false);
		Amslib::insertSessionParam(self::SERVICE_ERRORS,false);
	}
	
	public function setServiceCallbacks($state)
	{
		if(self::isExternal()){
			$state = self::getAJAX();
			$this->setCallback("success",array($this,$state?"successAJAX":"successPOST"));
			$this->setCallback("failure",array($this,$state?"failureAJAX":"failurePOST"));
		}else{
			$this->setCallback("success",array($this,"returnTrue"));
			$this->setCallback("failure",array($this,"returnFalse"));
		}
	}
	
	public function setCallback($type,$callback)
	{
		if(	in_array($type,array("process","success","failure")) &&
			is_string($type) && strlen($type) && 
			is_string($callback) && strlen($callback))
		{
			$this->callback[$type] = $callback;
		}
	}
	
	public function validate($name,$type,$required=false,$options=array())
	{ 
		$this->validator->add($name,$type,$required,$options);
	}
	
	public function setData($key,$value)
	{
		$this->data[$key] = $value;
	}
	
	public function setError($key,$value)
	{
		//	We slugify this information because it goes into the url
		$this->errors[Amslib::slugify($key)] = Amslib::slugify($value);
	}
	
	public function execute()
	{
		Amslib::insertSessionParam(self::VALIDATION_COMPLETE,true);
		
		if($this->validator->execute()){
			$this->data["valid"] = $this->validator->getValid();
			
			if(isset($this->callback["process"]) && call_user_func($this->callback["process"],$this,$this->data["valid"]) === true){
				return $this->callback["success"];
			}
			
			$this->setError("error","service_failed");
		}else{
			$this->setError("error","validation_failed");
		}
		
		return $this->callback["failure"];
	}
	
	/**********************************************************************
	 * 	STATIC CONFIGURATION API METHODS
	**********************************************************************/
	static public function getSuccessURL()
	{
		return Amslib::sessionParam(self::FORM_SUCCESS);
	}
	
	static public function setSuccessURL($url)
	{
		if(strlen($url)) Amslib::insertSessionParam(self::FORM_SUCCESS, Amslib::rchop($url,"?"));
	}
	
	static public function getFailureURL()
	{
		return Amslib::sessionParam(self::FORM_FAILURE);
	}
	
	static public function setFailureURL($url)
	{
		if(strlen($url)) Amslib::insertSessionParam(self::FORM_FAILURE, Amslib::rchop($url,"?"));
	}
	
	static public function setAJAX($state)
	{
		Amslib::insertSessionParam(self::FORM_AJAX, $state?true:false);
	}
	
	static public function getAJAX()
	{
		return Amslib::sessionParam(self::FORM_AJAX,false);
	}
	
	static public function setServiceDepth($offset)
	{
		Amslib::insertSessionParam(self::SERVICE_DEPTH,Amslib::sessionParam(self::SERVICE_DEPTH,0)+$offset);
	}
	
	static public function isExternal()
	{
		Amslib::sessionParam(self::SERVICE_DEPTH,0) === 0 ? true : false;
	}
	
	static public function configureService($success,$failure=false,$ajax=false)
	{
		if(!$failure) $failure = $success;
		
		self::setSuccessURL($success);
		self::setFailureURL($failure);
		self::setAJAX($ajax);
		
		/**
		 * what if I have multiple forms on the same page, they'll start to interfere with each other....
		 * it's true, all values are global, not specific to the form, there is a single set of values for everybody
		 * 
		 * in this situation, I think I should prepend the service data and perhaps can set this when the Amslib_Plugin_Service2
		 * class starts from the api object perhaps a parameter can be used to trigger it
		 * 
		 * omg, it's starting to get a whole integrated system where you need data everywhere setup in order to send a form
		 * I think it should be more automatic...this whole, configuration system, plan sounds awful.
		 */
	}
}