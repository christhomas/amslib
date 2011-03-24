<?php
class Amslib_Website
{
	static protected $location = NULL;

	static public function set($path=NULL)
	{
		if(self::$location !== NULL) return self::$location;

		$router_dir = NULL;

		//	Auto-discover the path
		if($path == NULL){
			//	First try and obtain the router dir from the PHP_SELF parameter
			if(!$router_dir && strpos($_SERVER["PHP_SELF"],"amslib_router.php") !== false) $router_dir = dirname($_SERVER["PHP_SELF"]);
			//	Second try and obtain the router dir from the sessionParam "router_dir" which can be set by the user/configuration
			if(!$router_dir) $router_dir = Amslib::sessionParam("router_dir",false);
			//	Third try and obtain the router dir from the Amslib_Keystore
			if(!$router_dir) $router_dir = Amslib_Keystore::get("router_dir");
		}else{
			$router_dir = Amslib_Filesystem::relative($path);
		}

		//	If the router dir is not false (means it was set) and it was a string (means it's valid[potentially])
		if($router_dir && is_string($router_dir)){
			self::$location = Amslib_Filesystem::relative($router_dir);

			//	Make sure the location has a slash at both front+back (ex: /location/, not /location or location/)
			self::$location = Amslib_Filesystem::reduceSlashes("/".self::$location."/");
		}

		//	NOTE:	Special case having a single slash as the location to being a blank string
		//			the single slash causes lots of bugs and means you have to check everywhere
		//			for it's presence, whilst it doesnt really do anything, so better if you
		//			just eliminate it and put a blank string
		if(self::$location == "/") self::$location = "";

		return self::$location;
	}

	static public function get()
	{
		return self::$location;
	}

	//	Return a relative url for the file to the document root
	static public function rel($file)
	{
		return Amslib_Filesystem::relative(self::$location.$file);
	}

	//	Return an absolute url for the file to the root directory
	static public function abs($file)
	{
		return Amslib_Filesystem::absolute(self::$location.$file);
	}
	
	//	Return a relative url for the file to the website location
	static public function web($file)
	{
		return Amslib_Filesystem::reduceSlashes("/".str_replace(self::$location,"",self::abs($file))."/");
	}

	//	NOTE: I don't like this method anymore.
	//	NOTE: we should delete this method
	static public function url($file,$relative=false)
	{
		//	If the website path is not set, return the path based on the docroot
		//	NOTE: This will be incorrect if the website path is not the same as the document root
		if(self::$location === false) return Amslib_Filesystem::absolute($file);

		$file	=	Amslib::lchop($file,self::$location);
		$file	=	str_replace("//","/",self::$location."/$file");

		return ($relative) ? Amslib_Filesystem::relative($file) : $file;
	}

	static public function redirect($location,$block=true)
	{
		$message = "waiting to redirect";

		if(is_string($location) && strlen($location)){
			header("Location: $location");
		}else{
			$message = "Amslib_Website::redirect-> The \$location parameter was an invalid string";
		}

		if($block) die($message);
	}

	static public function outputJSON($array,$block=true)
	{
		header("Content-Type: application/json");

		$json = json_encode($array);

		if($block) die($json);

		print($json);
	}
}