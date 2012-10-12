<?php
class Amslib_Router_URL
{
	static public function getFullURL()
	{
		return Amslib_Router::getPath();
	}

	static public function get($route=NULL,$group=NULL)
	{
		$lang = Amslib_Plugin_Application::getLanguage("website");

		$r = Amslib_Router::getURL($route,$group,$lang);

		if($r == "/" && $lang == "es_ES") $r = "/es/";

		return $r;
	}

	static public function getService($route,$group=NULL)
	{
		return Amslib_Router::getServiceURL($route,$group);
	}

	static public function redirect($url,$permenant=0)
	{
		$type = $permenant ? 301 : 0;

		return Amslib_Website::redirect($url,true,$type);
	}

	static public function getParam($name=NULL,$default="")
	{
		return Amslib_Router::getRouteParam($name,$default);
	}

	static public function getOption($index=NULL,$default="")
	{
		if(!is_numeric($index) && $index !== NULL) $index = 0;

		return Amslib_Router::getURLParam($index,$default);
	}

	static public function decodeURLPairs()
	{
		return Amslib_Router::decodeURLPairs();
	}

	static public function getDomain($url="")
	{
		return (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].$url;
	}
}