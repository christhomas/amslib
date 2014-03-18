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
	static protected $location = NULL;

	const ERROR_FILE_NOT_FOUND	=	"The src filename was not found, could not be fixed automatically";

	static public function move($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		$s = $src_filename;
		$d = self::abs($directory);

		if(!file_exists($s) && $a=self::abs($s)){
			$s = file_exists($a) ? $a : false;
		}

		if(Amslib_File::move($s,$d,$dst_filename,$fullpath)) return true;

		if(!$s) Amslib::errorLog(self::ERROR_FILE_NOT_FOUND." (s,src_filename) = ",$s,$src_filename);

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
	/**
	 * 	method:	rel
	 *
	 * 	todo: write documentation
	 */
	static public function rel($file="")
	{
		return Amslib_File::relative(self::$location.$file);
	}

	//	Return an absolute url for the file to the root directory
	//	FIXME: if you pass an absolute filename into this method, it won't return the correct filename back
	/**
	 * 	method:	abs
	 *
	 * 	todo: write documentation
	 */
	static public function abs($file="")
	{
		return Amslib_File::absolute(self::$location.$file);
	}

	//	NOTE type=0 means no specific header is given, so it'll default to a 302 redirection
	/**
	 * 	method:	redirect
	 *
	 * 	todo: write documentation
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
			Amslib::errorLog($message);
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
		$jsonp = Amslib::getGET("callback");
		if($jsonp) $json = "$jsonp($json)";

		if($block === true)		die($json);
		if($block === false)	print($json);

		return $json;
	}

	//	DEPRECATED METHOD, use move() instead
	static public function saveUploadedFile($src_filename,$directory,&$dst_filename,&$fullpath=NULL){
		Amslib::errorLog("DEPRECATED METHOD","stack_trace");

		self::move($src_filename,$directory,$dst_filename,$fullpath);
	}
}