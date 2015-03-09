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
 * 	class:	Amslib_File
 *
 *	group:	file
 *
 *	file:	Amslib_File.php
 *
 *	title:	A general object to deal with filenames, paths and downloads
 *
 *	description:
 *		todo, write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_File
{
	static protected $docroot = false;

	static public function swapSeparator($string,$from="\\",$to="/")
	{
		return str_replace($from,$to,$string);
	}

	/**
	 * 	method:	documentRoot
	 *
	 * 	todo: write documentation
	 * 	SERIOUS TODO:	I need a testing strategy for this code, it's rock solid on
	 * 					linux hosts and falls to pieces on anything "weird"
	 */
	static public function documentRoot($docroot=NULL)
	{
		if($docroot && is_string($docroot) && strlen($docroot)){
			$docroot = Amslib_String::reduceSlashes($docroot);
		}else if(isset($_SERVER["CONTEXT_DOCUMENT_ROOT"])){
			$docroot = $_SERVER["CONTEXT_DOCUMENT_ROOT"];

			if(isset($_SERVER["CONTEXT_PREFIX"])){
				$docroot = Amslib_String::rchop($docroot,$_SERVER["CONTEXT_PREFIX"]);
			}
		}else if(isset($_SERVER["DOCUMENT_ROOT"])){
			$docroot = $_SERVER["DOCUMENT_ROOT"];
		}else{
			Amslib_Debug::log(__METHOD__,"Not sure how to obtain the docroot on this platform",$docroot,$_SERVER);
			// NOTE: ADD DEBUGGING CODE HERE AND UPDATE WHEN YOU FIND A SCENARIO WHICH WORKS
			//die(__METHOD__.Amslib_Debug::vdump(array("docroot"=>$docroot,"server"=>$_SERVER)));
			return false;
		}

		die(__METHOD__.Amslib_Debug::vdump(array("docroot"=>$docroot,"server"=>$_SERVER)));

 		self::$docroot = $docroot;
 		self::$docroot = realpath(self::$docroot);
 		self::$docroot = self::win2unix(self::$docroot);

		return self::$docroot;
	}

	static public function win2unix($path)
	{
		return self::removeWindowsDrive(
			self::swapSeparator($path)
		);
	}

	/**
	 * 	method:	removeWindowsDrive
	 *
	 * 	todo: write documentation
	 */
	static public function removeWindowsDrive($location)
	{
		//	If we detect this token, it's a protocol marker which is not safe to modify
		if(strpos($location,"://") !== false) return $location;

		$location = explode(":",$location);

		//	if there are two parts, return the last, otherwise return the first
		return count($location) > 1 ? end($location) : current($location);
	}

	/**
	 * 	method:	dirname
	 *
	 * 	todo: write documentation
	 */
	static public function dirname($location)
	{
		$dirname = dirname($location);

		return (strpos($dirname,":") !== false) ? self::removeWindowsDrive($dirname) : $dirname;
	}

	/**
	 * 	note:
	 * 		-	I got the basics of this method from: http://stackoverflow.com/a/14883803/279147
	 */
	static public function resolvePath($path)
	{
		if(strpos($path,"..") === false) return $path;

		list($protocol,$url) = explode("://",$path) + array(NULL,NULL);

		if($url){
			//	protocol was valid, so domain is the first element
			$array = explode("/",$url);
			$domain = array_shift($array);
			$prefix = "{$protocol}://{$domain}/";
		}else{
			$array = explode("/",$path);
			//	If the path begins with a / then the final result path has to have one too
			$prefix = $path[0] == "/" ? "/" : "";
		}

		$parents = array();
		foreach($array as $dir) {
			switch($dir) {
				case '.':
					// Don't need to do anything here
				break;

				case '..':
					if(count($parents)){
						array_pop($parents);
					}
				break;

				default:
					$parents[] = $dir;
				break;
			}
		}

		$url = $prefix.implode("/",$parents);

		return Amslib_Website::reduceSlashes($url);
	}

	/**
	 * 	method:	absolute
	 *
	 * 	todo: write documentation
	 */
	static public function absolute($path)
	{
		if(strpos($path,"://") !== false) return $path;

		$root	=	self::documentRoot();
		$path 	=	self::win2unix($path);
		$rel	=	Amslib_String::lchop($path,$root);

		$final = self::reduceSlashes("$root/$rel");

		if(!is_dir($final) && !file_exists($final)){
			//ssprint(__METHOD__.Amslib_Debug::vdump($path,$root,$rel,$final));
		}

		return $final;
	}

	/**
	 * 	method:	relative
	 *
	 * 	todo: write documentation
	 */
	static public function relative($path)
	{
		if(strpos($path,"http://") === 0) return $path;

		$root	=	self::documentRoot();
		$path 	=	self::win2unix($path);
		$rel	=	Amslib_String::lchop($path,$root);

		return self::reduceSlashes("/$rel");
	}

	/**
	 * method: mkdir
	 *
	 * A function to make directories but can handle creating all the parent directories
	 * this avoids problems with mkdir+recursive which lots of times just fails without reason
	 *
	 * parameters:
	 * 	$directory	-	The directory to create
	 *
	 * returns:
	 * 	boolean true or false, depending on the whether creation was successful
	 */
	static public function mkdir($directory,$mode=0777,$recursive=false,&$error_text=NULL)
	{
		$status = true;

		ob_start();
		$exists = is_dir($directory);
		if(!$exists && !mkdir($directory,$mode,$recursive)){
			Amslib_Debug::log("Failed to create directory requested",$directory);
			$status = false;
		}
		$error_text = ob_get_clean();

		return $status;
	}

	//	NOTE:	what does this method do now? it's old and deprecated?
	//			or has a specific purpose? but it's not documented
	static public function mkdir_manual($directory,$mode=0777)
	{
		$parent = dirname($directory);

		if(!is_dir($parent) && !file_exists($parent)){
			self::mkdir($parent);
		}

		return !is_dir($directory) && mkdir($directory) && chmod($directory,$mode);
	}

	/**
	 * 	method:	rdelete
	 *
	 * 	todo: write documentation
	 */
	static public function rdelete($location)
	{
		//	NOTE:	not implemented yet because I changed my mind on it's creation, it seems useful, but SOOOOOOOO dangerous..
		//			I'm not sure I should do this....it could nuke your filesystem and make your system unbootable...

		/**
		 * Recursively delete files or directories
		 * WARNING: You better make double triple quadruple sure you wanna do this, cause it'll nuke a LOT OF FILES
		 */
	}

	/**
	 * 	method:	getFileExtension
	 *
	 * 	todo: write documentation
	 */
	static public function getFileExtension($filename)
	{
		$part = explode(".",$filename);

		if(count($part) > 1){
			$last = end($part);

			return strtolower($last);
		}

		return false;
	}

	/**
	 * 	method:	find
	 *
	 * 	todo: write documentation
	 */
	static public function find($filename,$includeFilename=false)
	{
		if(@file_exists($filename)){
			return ($includeFilename) ? $filename : Amslib_String::rchop($filename,"/");
		}

		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		foreach($includePath as $path){
			$test = (strpos($filename,"/") !== 0) ? "$path/$filename" : "{$path}{$filename}";
			if(@file_exists($test)){
				return realpath($includeFilename ? $test : $path);
			}
		}

		return false;
	}

	/**
	 * 	method:	removeTrailingSlash
	 *
	 * 	todo: write documentation
	 *
	 * 	note: WTF?? why didnt I just rtrim($path,"/") ??? there is any good reason for this??
	 */
	static public function removeTrailingSlash($path)
	{
		//	Make sure the path doesnt end with a trailing slash
		$path = str_replace("/__END__","",$path."__END__");
		//	Cleanup after the attempt to detect trailing slash
		$path = str_replace("__END__","",$path);

		return $path;
	}

	/**
	 * 	method:	getList
	 *
	 * 	todo: write documentation
	 *
	 * 	NOTE: This looks very similar to listFiles method just below...
	 * 	NOTE: (23/06/2014): I refactored listFiles to use getList since in fact, it's the same identical code
	 */
	static public function getList($dir,$recurse=false)
	{
		$list = array();

		if(is_dir($dir)){
			$list = glob(self::reduceSlashes("$dir/*"));

			if($recurse){
				foreach($list as $l){
					$list = array_merge($list,self::getList($l,$recurse));
				}
			}
		}

		return $list;
	}

	/**
	 * method: listFiles
	 *
	 * List all the files, not directories in the path given.
	 *
	 * parameters:
	 * 		$dir		-	The directory to scan through
	 * 		$recurse	-	Whether to recurse into subdirectories
	 *
	 * returns:
	 * 		An array of files which were found, or an empty array
	 */
	static public function listFiles($dir,$recurse=false)
	{
		$list = self::getList($dir,$recurse);

		//	Remove all the directories from the list
		foreach($list as $k=>$v){
			if(is_dir($v)) unset($list[$k]);
		}

		return $list;
	}

	/**
	 * 	method:	glob
	 *
	 * 	todo: write documentation
	 *
	 * 	NOTE:	this looks very similar to the listFiles method above, am I going to standardise on one of them?
	 * 			or formalise multiple similar, but different (perhaps optimal) codepaths
	 */
	static public function glob($path,$relative=false)
	{
		$list = glob(self::absolute($path));

		if($relative && !empty($list)) foreach($list as &$l){
			$l = self::relative($l);
		}

		return $list;
	}

	static public function writeFile($data,$dst_filename)
	{
		return $dst_filename;
	}

	static public function copyFile($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		//	TODO: implement similar code to move() but copying not moving the file
		return false;
	}

	static public function moveFile($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		//	You need a valid src filename and directory, otherwise you can't really move files around
		if(!strlen($src_filename) || !strlen($directory)) return false;

		$error = false;

		$directory = self::absolute($directory);

		//	grab warning/error output to stop it breaking the output
		ob_start();

		//	NOTE: Perhaps all this checking and copying or directories etc, should be formalised into the api??
		//	If the destination directory doesnt exist, attempt to create it
		if($error == false && !is_dir($directory) && !mkdir($directory,0777,true)){
			Amslib_Debug::log("There was an error with the directory, either permissions, or creating it was not possible",$directory,error_get_last());
			$error = true;
		}

		//	It REALLY REALLY should exist now, but lets check just in case
		if($error == false && !is_dir($directory)){
			Amslib_Debug::log("The directory does not exist, so cannot write to the location",$directory,error_get_last());
			$error = true;
		}

		//	If there was not dst_filename given, use the original filename from the src
		if(!strlen($dst_filename)) $dst_filename = basename($src_filename);

		$dst_filename	=	Amslib_String::slugify2(basename($dst_filename),"_",".");
		$destination	=	self::reduceSlashes("$directory/$dst_filename");

		//	Try to move the file into the correct destination
		if($error == false && !rename($src_filename,$destination)){
			Amslib_Debug::log("It was not possible to save to the requested filename",error_get_last());
			$error = true;
		}

		//	If there are no errors, you have uploaded the file ok, however, you could still fail here
		if($error == false && !chmod($destination,0777)){
			Amslib_Debug::log("file uploaded ok (apparently), but chmod failed",$destination,error_get_last());
		}

		//	if the output was not empty, something bad happened, like a warning or error
		//	when this happens, sometimes with file operations, it can break json, or website output
		//	so I use an output buffer to grab all the output without it going to the user in some way
		//	then once I have it, I can do something with it, like output it only to the error log, etc, etc.
		$output = ob_get_clean();
		if(strlen($output)){
			Amslib_Debug::log("Error or warning executing file operations",$output);
		}

		//	Set the full path of the file, it's final destination in the filesystem
		$fullpath = $destination;

		return !$error;
	}

	//	NOTE: I had to call this deleteFile because "delete" is a reserved keyword :(
	static public function deleteFile($filename)
	{
		if(!$filename || !is_string($filename) || !file_exists($filename)){
			die("filename invalid".Amslib_Debug::pdump(true,$filename));
			return false;
		}

		$status = false;

		ob_start();
			$status = unlink($filename);
		$output = ob_get_clean();
		//	if the output was not empty, something bad happened, like a warning or error
		//	when this happens, sometimes with file operations, it can break json, or website output
		//	so I use an output buffer to grab all the output without it going to the user in some way
		//	then once I have it, I can do something with it, like output it only to the error log, etc, etc.
		if(strlen($output)){
			$status = false;
			Amslib_Debug::log("Error or warning executing file operations",$output);
		}

		return $status;
	}

	static public function getContents($filename,&$error=NULL)
	{
		ob_start();
		$data = file_get_contents($filename);
		$error = ob_get_clean();

		//	TODO: make this logging optional
		Amslib_Debug::log(__METHOD__,$filename);

		return $data;
	}

	static private function ____DEPRECATED_METHODS_BELOW(){}

	static public function saveUploadedFile($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		return self::moveFile($src_filename,$directory,$dst_filename,$fullpath);
	}

	static public function copy($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		return self::copyFile($src_filename,$directory,$dst_filename,$fullpath);
	}

	static public function move($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		return self::moveFile($src_filename,$directory,$dst_filename,$fullpath);
	}

	static public function reduceSlashes($string)
	{
		return Amslib_String::reduceSlashes($string);
	}
}
