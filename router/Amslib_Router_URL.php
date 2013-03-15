<?php
class Amslib_Router_URL
{
	static public function getFullURL()
	{
		return Amslib_Router::getPath();
	}
	
	static public function getURL($route,$group=NULL)
	{
		if(is_array($route)){
			$r = array_shift($route);
			$p = implode("/",array_filter($route));
		}else{
			$r = $route;
			$p = "";
		}
	
		return is_string($r) ? Amslib_Router::getURL($r).$p : "";
	}

	/*	NOTE:	getURL was deactivated because it's defining URL policy for a website to 
	 * 			automatically include the language inside a URL, thats the applications job
	 * 
	 * static public function getURL($route=NULL,$group=NULL)
	{
		$lang = Amslib_Plugin_Application::getLanguage("website");

		$r = Amslib_Router::getURL($route,$group,$lang);

		if($r == "/" && $lang == "es_ES") $r = "/es/";

		return $r;
	}*/

	static public function getServiceURL($route,$group=NULL)
	{
		return Amslib_Router::getServiceURL($route,$group);
	}

	static public function redirect($url,$permenant=0)
	{
		$type = $permenant ? 301 : 0;

		return Amslib_Website::redirect($url,true,$type);
	}

	static public function getRouteParam($name=NULL,$default="")
	{
		return Amslib_Router::getRouteParam($name,$default);
	}

	static public function getURLParam($index=NULL,$default="")
	{
		return Amslib_Router::getURLParam($index,$default);
	}
	
	static public function decodeURLPairs($offset=0)
	{
		return Amslib_Router::decodeURLPairs($offset);
	}
	
	static public function externalURL($url="")
	{
		return (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].$url;
	}
	
	//	DEPRECATED METHODS, DO NOT USE THEM
	static public function getDomain($url=""){
		return self::externalURL($url);	
	}
}