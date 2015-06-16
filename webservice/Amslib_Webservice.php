<?php
class Amslib_Webservice
{
	protected $router_url_source;
	protected $url;
	protected $params;

	public function __construct($location)
	{
		$this->setRouterURLSource("Amslib_Router_URL");
		
		$this->setLocation($location);
		$this->setParams(array());
	}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	public function setRouterURLSource($source=NULL)
	{
		//	There might be other conditions here which determine whether this can run or not
		
		if($source && is_callable($source,"getServiceURL")){
			$this->router_url_source = $source;
		}
	}
	
	public function setLocation($location)
	{
		$this->url = false;
		
		if(!$this->url) $this->setRoute($location);
		if(!$this->url) $this->setURL($url);
	}
	
	public function setURL($url)
	{
		$this->url = $url;
	}
	
	public function setRoute($route)
	{
		$this->url = call_user_func(array($this->router_url_source,"getServiceName"),$route);
	}
	
	public function setParams($params)
	{
		$this->params = $params;
	}
	
	public function setParam($key,$value)
	{
		$this->params[$key] = $value;
	}
	
	/*public function getData($response,$plugin=NULL,$key=NULL,$default=false,$paged=false)
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
	}*/
	
	/*public function call($name,$params=array(),$paged=false)
	 {
	 if($paged && is_array($params)){
	 if(!isset($params["pager_page"]))	$params["pager_page"] = 0;
	 if(!isset($params["pager_length"]))	$params["pager_length"] = NULL;
	 }
	
	 //	FIXME:	so the only way this system works is when in conjunction with a router?
	 //	NOTE:	I think the router should be optional somehow, dependency injection can solve this?
	 $url = $this->urlSource
	 ? call_user_func(array($this->urlSource,"getServiceURL"),$name)
	 : $name;
	
	 $request = new Amslib_Webservice_Request($url,$params,true);
	 //	FIXME: why did I put this here? I think it's code which should not have been committed
	 $request->setBasicAuthorisation("clients","clients");
	
	 return $request->execute();
	 }*/
	
	/*static public function createResponse($type,$data=NULL)
	 {
	 $map = array("amslib"=>"Amslib","json"=>"JSON","raw"=>"Raw");
	
	 if(!in_array(strtolower($type),array("amslib","json","raw"))){
	 $type = "Raw";
	 }
	
	 $type = "Amslib_Webservice_Response_{$map[$type]}";
	
	 return new $type($data);
	 }*/
}