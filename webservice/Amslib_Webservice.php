<?php
class Amslib_Webservice
{
	protected $urlSource;

	public function __construct($urlSource=NULL)
	{
		$this->setURLSource($urlSource);
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function initialiseObject($urlSource)
	{
		$this->setURLSource($urlSource);
	}

	public function setURLSource($urlSource=NULL)
	{
		$this->urlSource = $urlSource ? $urlSource : "Amslib_Router_URL";
	}

	public function call($name,$params=array(),$paged=false)
	{
		if($paged && is_array($params)){
			if(!isset($params["pager_page"]))	$params["pager_page"] = 0;
			if(!isset($params["pager_length"]))	$params["pager_length"] = NULL;
		}

		$url = call_user_func(array($this->urlSource,"getServiceURL"),$name);

		$request = new Amslib_Webservice_Request($url,$params,true);
		$request->setBasicAuthorisation("clients","clients");

		return $request->execute();
	}

	public function getData($response,$plugin=NULL,$key=NULL,$default=false,$paged=false)
	{
		if(!$response) return $default;

		$amslib = $response->getData("amslib");

		if(!$amslib) return $default;

		$data = $amslib->getServiceData($plugin);

		if($paged){
			if($data && Amslib_Array::hasKeys($data,array("list","item_count","page_count"))){
				$data["list"] = Amslib_Array::valid($data["list"]);
			}else{
				$data = array(
						"list"			=>	array(),
						"item_count"	=>	0,
						"page_count"	=>	0
				);
			}
		}else{
			$data = $data && $key && isset($data[$key])
				? $data[$key]
				: ($key ? $default : $data);
		}

		return $data;
	}

	public function getError($response,$plugin=NULL,$key=NULL,$default=false)
	{
		if(!$response) return $default;

		$amslib = $response->getData("amslib");

		if(!$amslib) return $default;

		$data = array(
				"service"		=> $amslib->getServiceErrors($plugin),
				"validation"	=>	$amslib->getValidationErrors($plugin)
		);

		return $data && $key && isset($data[$key])
			? $data[$key]
			: ($key ? $default : $data);
	}
}