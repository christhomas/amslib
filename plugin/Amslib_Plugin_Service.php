<?php 
//	FIXME: This object doesn't yet support processing AJAX requests, everything is done by returnURL, etc
class Amslib_Plugin_Service
{
	protected $validator;
	protected $returnURL;
	protected $returnAJAX;
	protected $callback;
	protected $data;
	protected $errors;

	protected function success()
	{
		if($this->returnAJAX){
			Amslib_Website::outputJSON(array(
				"validation_success"	=>	true,
				"service_data"			=>	$this->data
			),true);
		}else{
			Amslib::insertSessionParam("validation_success",true);
			Amslib::insertSessionParam("service_data",$this->data);
			Amslib_Website::redirect($this->returnURL);
		}
	}
	
	protected function failure()
	{
		if($this->returnAJAX){
			Amslib_Website::outputJSON(array(
				"validation_errors"		=>	$this->validator->getErrors(),
				"validation_data"		=>	$this->data,
				"validation_success"	=>	false,
				"service_errors"		=>	$this->errors
			),true);
		}else{
			Amslib::insertSessionParam("validation_errors",$this->validator->getErrors());
			Amslib::insertSessionParam("validation_data",$this->data);
			Amslib::insertSessionParam("validation_success",false);
			Amslib::insertSessionParam("service_errors",$this->errors);
		
			Amslib_Website::redirect($this->returnURL);	
		}
	}
	
	public function __construct($callback)
	{
		//	TODO:	This might not be a great idea to ALWAYS ask for this route, 
		//			could not exist in some situations and probably will happen.
		$default			=	Amslib_Router3::getURL("home");
		
		$this->validator	=	new Amslib_Validator3($_POST);
		$this->returnURL	=	Amslib::rchop(Amslib::postParam("return_url",$default),"?");
		$this->returnAJAX	=	Amslib::postParam("return_ajax");
		$this->callback		=	$callback;
		$this->data			=	array();
		$this->errors		=	array();
	}
	
	public function validate($name,$type,$required=false,$options=array())
	{ 
		$this->validator->add($name,$type,$required,$options);
	}
	
	public function execute()
	{
		$this->data = array();
		
		Amslib::insertSessionParam("validation_complete",true);
		
		if($this->validator->execute()){
			$this->data["validator"] = $this->validator->getValidData();

			if(call_user_func($this->callback,$this,$this->validator->getValidData()) === true){
				$this->success();
			}
			
			$this->setError("error","service_failed");
		}else{
			$this->setError("error","validation_failed");
		}
		
		$this->failure();
	}
	
	public function setData($key,$value)
	{
		$this->data[$key] = $value;
	}
	
	public function setError($key,$value)
	{
		//	FIXME: could be possible set more than one error, then the url is invalid: ?error=something?error=another?error=whatever
		if($key == "error"){
			$this->returnURL .= "?error={$value}";	
		}
		
		$this->errors[$key] = $value;
	}
	
	static public function getError($name=NULL,$erase=true)
	{
		$errors = Amslib::sessionParam("service_errors",false,$erase);
		
		if($name === NULL) return $errors;
		
		return ($errors && isset($errors[$name])) ? $errors[$name] : false;
	}
}