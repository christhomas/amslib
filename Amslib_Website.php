<?php
class Amslib_Website
{
	static protected $location = NULL;

	static public function set($path=NULL)
	{
		if(self::$location !== NULL) return self::$location;

		$router_dir = NULL;

		if($path == NULL){
			self::$location = Amslib_Router::getBase();
		}else{
			//	Make sure the location has a slash at both front+back (ex: /location/, not /location or location/)
			self::$location = Amslib_File::reduceSlashes("/".Amslib_File::relative($path)."/");
		}

		//	NOTE:	Special case having a single slash as the location to being a blank string
		//			the single slash causes lots of bugs and means you have to check everywhere
		//			for it's presence, whilst it doesnt really do anything, so better if you
		//			just eliminate it and put a blank string
		//	NOTE:	The reason is, if you str_replace($location,"",$something) and $location is /
		//			then you will nuke every path separator in your url, which is useless....
		if(self::$location == "/") self::$location = "";

		return self::$location;
	}

	//	Return a relative url for the file to the document root
	static public function rel($file)
	{
		return Amslib_File::relative(self::$location.$file);
	}

	//	Return an absolute url for the file to the root directory
	//	FIXME: if you pass an absolute filename into this method, it won't return the correct filename back
	static public function abs($file)
	{
		return Amslib_File::absolute(self::$location.$file);
	}

	static public function redirect($location,$block=true)
	{
		$message = "waiting to redirect";

		if(is_string($location) && strlen($location)){
			$location = rtrim($location,"/");
			if($location == "") $location = "/";

			header("Location: $location");
		}else{
			$message = __METHOD__."-> The \$location parameter was an invalid string: '$location'";
			trigger_error($message);
		}

		if($block) die($message);
	}

	static public function outputJSON($array,$block=true)
	{
		header("Cache-Control: no-cache");
		header("Content-Type: application/json");

		$json = json_encode($array);
		//	if there is a callback specified, wrap up the json into a jsonp format
		$jsonp = Amslib::getGET("callback");
		if($jsonp) $json = "$jsonp($json)";

		if($block) die($json);

		print($json);
	}
}