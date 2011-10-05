<?php 
class Amslib_Plugin_Service3
{
	protected $successURL;
	protected $failureURL;
	protected $successCB;
	protected $failureCB;
	protected $isAJAX;
	protected $data;
	
	protected function successPOST()
	{
		$this->data["success"] = true;
		$this->setServiceData($this->data);

		Amslib_Website::redirect($this->successURL,true);
	}
	
	protected function failurePOST()
	{
		$this->data["success"] = false;
		$this->setServiceData($this->data);
		
		Amslib_Website::redirect($this->failureURL,true);
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
		$default			=	Amslib_Router3::getURL("home");	
		$return				=	Amslib::rchop(Amslib::postParam("return_url",$default),"?");
		
		$this->successURL	=	Amslib::rchop(Amslib::postParam("success_url",$return),"?");
		$this->failureURL	=	Amslib::rchop(Amslib::postParam("failure_url",$return),"?");
		$this->isAJAX		=	Amslib::postParam("return_ajax");
		
		$this->successCB	=	$this->isAJAX ? "successAJAX" : "successPOST";
		$this->failureCB	=	$this->isAJAX ? "failureAJAX" : "failurePOST";
		
		//	Reset the service data and session structures
		$this->data			=	array();
		$this->setServiceData(false);
	}
	
	public function setServiceData($data)
	{
		$_SESSION["service"] = $data;
	}
	
	public function execute($plugin,$method)
	{
		$cb = call_user_func(array($plugin,$method),$this,$_POST)
			? $this->successCB 
			: $this->failureCB;
		
		call_user_func(array($this,$cb));
		
		die("FAILURE[p:$plugin][m:$method]-> All services should terminate with redirect or json");
	}
	
	public function setValidationData($plugin,$data)
	{
		$this->data[$plugin]["validation/data"] = $data;
	}
	
	public function setValidationErrors($plugin,$errors)
	{
		$this->data[$plugin]["validation/errors"] = $data;
	}
	
	public function setData($plugin,$name,$value)
	{
		$this->data[$plugin]["service/data"][$name] = $value;
	}
	
	public function setError($plugin,$name,$value)
	{
		$this->data[$plugin]["service/errors"][$name] = $value;
	}	
}