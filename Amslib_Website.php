<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
* Contributors/Author:
*    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
*
*******************************************************************************/

/**
 * 	class:	Amslib_Website
 *
 *	group:	core
 *
 *	file:	Amslib_Website.php
 *
 *	description: todo, write description
 *
 * 	todo: write documentation
 */
class Amslib_Website
{
	static protected $path = array();

	static protected $location = NULL;

	const ERROR_FILE_NOT_FOUND	=	"The src filename was not found, could not be fixed automatically";

	static public function setPath($name,$path)
	{
		self::$path[$name] = $path;
	}

	static public function expandPath($path)
	{
		foreach(array_keys(self::$path) as $key){
			$path = str_replace("__".strtoupper($key)."__",self::$path[$key],$path);
		}

		return self::reduceSlashes($path);
	}

	//	NOTE:	why does an object called Amslib_Website have a function
	//			called move? Is moving files a website functionality?
	//	NOTE:	this function basically hides the details of where the
	//			real filename is, but I think it's the wrong place to
	//			put this functionality
	static public function move($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		$s = $src_filename;
		$d = self::abs($directory);

		if(!file_exists($s) && $a=self::abs($s)){
			$s = file_exists($a) ? $a : false;
		}

		if($s && Amslib_File::move($s,$d,$dst_filename,$fullpath)) return true;

		if(!$s){
			Amslib_Debug::log(self::ERROR_FILE_NOT_FOUND." (s,src_filename) = ",$s,$src_filename);
		}

		return false;
	}

	//	NOTE:	same problem as "move", why is this function here
	//	NOTE:	but why is this called deleteFile and that method is called move? not very consistent
	static public function deleteFile($src_filename)
	{
		$s = $src_filename;

		if(!file_exists($s) && $a=self::abs($s)){
			$s = file_exists($a) ? $a : false;
		}

		if($s && Amslib_File::deleteFile($s)){
			Amslib_Debug::log(__METHOD__,"success, file was deleted from the disk",$s);
			return true;
		}

		if(!$s){
			Amslib_Debug::log("stack_trace",self::ERROR_FILE_NOT_FOUND." (s,src_filename) = ",$s,$src_filename);
		}

		Amslib_Debug::log(__METHOD__,"failed to delete file");

		return false;
	}

	static public function listFiles($dir,$recurse=false,$exit=true)
	{
		return Amslib_File::listFiles(self::abs($dir),$recurse,$exit);
	}

	/**
	 * 	method:	set
	 *
	 * 	todo: write documentation
	 */
	static public function set($path=NULL)
	{
		if(self::$location !== NULL) return self::$location;

		$router_dir = NULL;

		if($path == NULL){
			self::$location = Amslib_Router::getBase();
		}else{
			//	Make sure the location has a slash at both front+back (ex: /location/, not /location or location/)
			self::$location = self::reduceSlashes("/".Amslib_File::relative($path)."/");
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
	/**
	 * 	method:	rel
	 *
	 * 	todo: write documentation
	 */
	static public function rel($url="",$resolve=false)
	{
		if(strpos($url,"://") !== false){
			$l = list($protocol,$domain,$url) = self::parseURL($url);

			//	Because we have a url with a protocol, it must mean the url parsed is already relative
			return $url;
		}

		$url = Amslib_File::relative(self::$location.$url);

		if($resolve) $url = Amslib_File::resolvePath($url);

		return $url;
	}

	/**
	 * 	method:	abs
	 *
	 *	Return an absolute url for the file to the root directory
	 *	FIXME: if you pass an absolute filename into this method, it won't return the correct filename back
	 */
	static public function abs($url="")
	{
		return Amslib_File::absolute(self::$location.$url);
	}

	/**
	 * 	method:	web
	 *
	 * 	Take a url and return a path relative to the website installation, NOT the document root
	 * 	Useful for knowing which url inside the website has been opened, so you can scan a
	 * 	database of urls for a match and other similar purposes.
	 *
	 * 	parameters:
	 * 		$url	=	The url to convert to a website relative path
	 *
	 * 	returns:
	 * 		A relative path to the website installation, without the leading part to the document root
	 *
	 * 	notes:
	 * 		-	This method will not process any url with protocol token (://) and will return the same url
	 */
	static public function web($url="")
	{
		$url = self::rel($url);
		$url = str_replace(self::$location,"","/$url/");

		return self::reduceSlashes("/$url/");
	}

	static public function parseURL($url)
	{
		list($protocol,$right) = explode("://",$url) + array(NULL,NULL);

		//	No protocol was detected, just return the string as a uri
		if(!$right) return array(NULL,NULL,$url);

		$parts = explode("/",$right);

		return array(
				$protocol,
				$parts[1],
				self::reduceSlashes("/".implode("/",array_slice($parts,1))."/")
		);
	}

	/**
	 * 	method:	reduceSlashes
	 *
	 * 	A method to reduceSlashes but take care of urls like http:// so they don't break
	 *
	 * 	params:
	 * 		$string	-	The string to reduce the slashes in
	 * 		$token	-	The token to split the string on, this should
	 * 					only exist once in the string and the right
	 * 					side will be reduced and the left side will not
	 * 					defaults: "://"
	 *
	 * 	returns:
	 * 		A string with any // or ///[n+] reduced to /
	 */
	static public function reduceSlashes($string,$token="://")
	{
		list($prefix,$postfix) = explode($token,$string) + array(NULL,NULL);

		if($postfix) $string = $postfix;

		return ($postfix ? "{$prefix}{$token}" : "").Amslib_File::reduceSlashes($string);
	}

	/**
	 * 	method:	redirect
	 *
	 * 	todo: write documentation
	 *
	 *	note: type=0 means no specific header is given, so it'll default to a 302 redirection
	 */
	static public function redirect($location,$block=true,$type=0)
	{
		$message = "waiting to redirect";

		if(is_string($location) && strlen($location)){
			$location = rtrim($location,"/");
			if($location == "") $location = "/";

			switch($type){
				case 301:{
					header("HTTP/1.1 301 Moved Permanently");
				}break;
			}

			header("Location: $location");
		}else{
			$message = __METHOD__."-> The \$location parameter was an invalid string: '$location'";
			Amslib_Debug::log($message);
		}

		if($block) die($message);
	}

	/**
	 * 	method:	outputJSON
	 *
	 * 	todo: write documentation
	 *
	 * 	note: I hate this function name, I think we should change it to something more elegant
	 */
	static public function outputJSON($array,$block=true)
	{
		header("Cache-Control: no-cache");
		header("Content-Type: application/json");

		//	NOTE: perhaps it would be nice to limit this CORS header in the future
		if(isset($_SERVER["HTTP_ORIGIN"])){
			$origin = $_SERVER["HTTP_ORIGIN"];
			header("Access-Control-Allow-Origin: $origin");
			header("Access-Control-Allow-Credentials: true");
		}

		$json = json_encode($array);
		//	if there is a callback specified, wrap up the json into a jsonp format
		$jsonp = Amslib_GET::get("callback");
		if($jsonp) $json = "$jsonp($json)";

		if($block === true)		die($json);
		if($block === false)	print($json);

		return $json;
	}

	//	DEPRECATED METHOD, use move() instead
	static public function saveUploadedFile($src_filename,$directory,&$dst_filename,&$fullpath=NULL){
		Amslib_Debug::log("DEPRECATED METHOD","stack_trace");

		self::move($src_filename,$directory,$dst_filename,$fullpath);
	}
}