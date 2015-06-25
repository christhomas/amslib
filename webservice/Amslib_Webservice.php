<?php
class Amslib_Webservice
{
	protected $router_url_source;
	protected $url;
	protected $params;
	protected $username;
	protected $password;
	protected $reply;

	public function __construct($location,$params=array())
	{
		$this->setRouterURLSource("Amslib_Router_URL");
		
		$this->setLocation($location);
		$this->setParams($params);
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
		if(!$this->url) $this->setURL($location);
	}
	
	public function setURL($url)
	{
		$this->url = $url;
	}
	
	public function setRoute($route)
	{
		$callback = array($this->router_url_source,"getServiceName");
		
		if(is_callable($callback)){
			$this->url = call_user_func($callback,$route);
		}
	}
	
	public function setParams($params)
	{
		if(is_array($params) && !empty($params)){
			$this->params = $params;
		}
	}
	
	public function setParam($key,$value)
	{
		$this->params[$key] = $value;
	}
	
	public function setAuth($username=NULL,$password=NULL)
	{
		if(is_string($username) && strlen($username) && is_string($password) && strlen($password)){
			$this->username = $username;
			$this->password = $password;
		}else{
			$this->username = NULL;
			$this->password = NULL;
		}
	}

	public function execute()
	{
		try{
			if(strlen($this->url) == 0) throw new Exception("webservice url was invalid");
		
			$curl = curl_init();
			
			$params = http_build_query(Amslib_Array::valid($this->params));
			
			if($this->username && $this->password){
				curl_setopt($curl,CURLOPT_USERPWD,"$this->username:$this->password");
			}
			
			curl_setopt($curl,CURLOPT_URL,				$this->url);
			curl_setopt($curl,CURLOPT_POST,				true);
			curl_setopt($curl,CURLOPT_HTTP_VERSION,		1.0);
			curl_setopt($curl,CURLOPT_RETURNTRANSFER,	true);
			curl_setopt($curl,CURLOPT_HEADER,			false);
			curl_setopt($curl,CURLOPT_POSTFIELDS,		$params);
			
			$this->reply = curl_exec($curl);
			
			if(!$this->reply || !strlen($this->reply)){
				Amslib_Debug::log("CURL ERROR",curl_error($curl),Amslib_Debug::dump($this->reply));
				curl_close($curl);
			
				return false;
			}
			
			curl_close($curl);
			
			return true;
		}catch(Exception $e){
			$exception = $e->getMessage();
		}

		Amslib_Debug::log(
			"EXCEPTION: ",		$exception,
			"WEBSERVICE URL: ",	$this->url,
			"PARAMS: ",			$this->params,
			"DATA: ",			$reply
		);

		return false;
	}
	
	public function getResponse($type)
	{
		$type = strtolower($type);
		
		$map = array(
			"amslib"	=>	"Amslib_Webservice_Response_Amslib",
			"json"		=>	"Amslib_Webservice_Response_JSON",
			"raw"		=>	"Amslib_Webservice_Response_Raw"
		);
		
		$type = in_array($type,array("amslib","json","raw")) ? $map[$type] : $map["raw"];
		
		return new $type($this->reply);
	}
}