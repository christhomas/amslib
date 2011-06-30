<?php 
//	FIXME: This object doesn't yet support processing AJAX requests, everything is done by returnURL, etc
class Amslib_Plugin_Service
{
	protected $validator;
	protected $returnURL;
	protected $callback;
	protected $data;
	protected $errors;

	protected function success()
	{
		Amslib::insertSessionParam("validation_success",true);
		
		Amslib_Website::redirect($this->returnURL);
	}
	
	protected function failure()
	{
		Amslib::insertSessionParam("validation_errors",$this->validator->getErrors());
		Amslib::insertSessionParam("validation_data",$this->data);
		Amslib::insertSessionParam("validation_success",false);
		Amslib::insertSessionParam("service_errors",$this->errors);
		
		Amslib_Website::redirect($this->returnURL);
	}
	
	public function __construct($callback)
	{
		$this->validator	=	new Amslib_Validator3($_POST);
		$this->returnURL	=	Amslib::rchop(Amslib::postParam("return_url"),"?");
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
}