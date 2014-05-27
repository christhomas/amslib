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

	/**
	 * 	method:	documentRoot
	 *
	 * 	todo: write documentation
	 * 	SERIOUS TODO:	I need a testing strategy for this code, it's rock solid on
	 * 					linux hosts and falls to pieces on anything "weird"
	 */
	static public function documentRoot($docroot=NULL)
	{
		//	Manually override the document root
		if($docroot && is_dir($docroot)) self::$docroot = $docroot;
		//	If the document root was already calculated, return it's cached value
		if(self::$docroot) return self::$docroot;

		$dr = self::removeWindowsDrive($_SERVER["DOCUMENT_ROOT"]);

		//	If the document root index exists, ues it to calculate the docroot
		if(isset($dr))
		{
			//	FIXME: If the docroot and dirname(__FILE__) have a different base path, this code will break
			/*	NOTE:	this situation happened with dinahosting, although without an example of
						how to get around it, I dont think I can do it now, but this code is causing
						problems, so I am going to delete it, it will always be in the GIT repo if I
						want to look at it again and I will just return to doing everything the normal
						way until the point in time where I need to do this again */

			$docroot	=	self::reduceSlashes($dr);
		}else{
			//	on IIS, there is no parameter DOCUMENT_ROOT, have to construct it yourself.

			//	Switch the document separators to match windows dumbass separators
			$phpself	=	str_replace("/","\\",$_SERVER["PHP_SELF"]);
			//	delete from script filename, the php self, which should reveal the base directory
			$root		=	str_replace($phpself,"",$_SERVER["SCRIPT_FILENAME"]);

			$docroot	=	self::removeWindowsDrive($root);
		}

		self::$docroot = realpath($docroot);

		return self::$docroot;
	}

	/**
	 * 	method:	removeWindowsDrive
	 *
	 * 	todo: write documentation
	 */
	static public function removeWindowsDrive($location)
	{
		if(strpos($location,":") !== false && isset($_SERVER["WINDIR"])){
			$location = array_slice(explode(":",str_replace("\\","/",$location)),1);

			return implode("/",$location);
		}

		return $location;
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
	 * 	method:	absolute
	 *
	 * 	todo: write documentation
	 */
	static public function absolute($path)
	{
		$root	=	self::documentRoot();
		$path	=	self::removeWindowsDrive($path);
		$rel	=	Amslib::lchop($path,$root);

		return self::reduceSlashes("$root/$rel");
	}

	/**
	 * 	method:	relative
	 *
	 * 	todo: write documentation
	 */
	static public function relative($path)
	{
		$root	=	self::documentRoot();
		$rel	=	Amslib::lchop($path,$root);

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
			Amslib::errorLog("Failed to create directory requested",$directory);
			$status = false;
		}
		$error_text = ob_get_clean();

		return $status;
	}

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
		$part	=	explode(".",$filename);
		$last	=	end($part);

		return strtolower($last);
	}

	/**
	 * 	method:	find
	 *
	 * 	todo: write documentation
	 */
	static public function find($filename,$includeFilename=false)
	{
		if(@file_exists($filename)){
			return ($includeFilename) ? $filename : Amslib::rchop($filename,"/");
		}

		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		foreach($includePath as $path){
			$test = (strpos($filename,"/") !== 0) ? "$path/$filename" : "{$path}{$filename}";
			if(@file_exists($test)){
				return ($includeFilename) ? $test : $path;
			}
		}

		return false;
	}

	/**
	 * 	method:	removeTrailingSlash
	 *
	 * 	todo: write documentation
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
	 * 	method:	reduceSlashes
	 *
	 * 	todo: write documentation
	 */
	static public function reduceSlashes($string)
	{
		return preg_replace('#//+#','/',$string);
	}

	/**
	 * 	method:	getList
	 *
	 * 	todo: write documentation
	 *
	 * 	NOTE: This looks very similar to listFiles method just below...
	 */
	static public function getList($dir,$recurse=false)
	{
		$list = array();

		if(is_dir($dir)){
			$list = glob("$dir/*");

			if($recurse){
				foreach($list as $l){
					$subdir = self::getList($l,$recurse);

					$list = array_merge($list,$subdir);
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
	 * 		$exit		-	Whether or not this is the outside call, therefore we are now "exiting" the method
	 *
	 * returns:
	 * 		An array of files which were found, or an empty array
	 */
	static public function listFiles($dir,$recurse=false,$exit=true)
	{
		$list = array();

		if(is_dir($dir)){
			$list = glob(self::reduceSlashes("$dir/*"));

			if($recurse){
				foreach($list as $l){
					$list = array_merge($list,self::listFiles($l,$recurse,false));
				}
			}
		}

		//	Remove all the directories from the list
		if($exit) foreach($list as $k=>$v){
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

	static public function copy($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		//	TODO: implement similar code to move() but copying not moving the file
	}

	static public function move($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
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
			Amslib::errorLog("There was an error with the directory, either permissions, or creating it was not possible",$directory,error_get_last());
			$error = true;
		}

		//	It REALLY REALLY should exist now, but lets check just in case
		if($error == false && !is_dir($directory)){
			Amslib::errorLog("The directory does not exist, so cannot write to the location",$directory,error_get_last());
			$error = true;
		}

		//	If there was not dst_filename given, use the original filename from the src
		if(!strlen($dst_filename)) $dst_filename = basename($src_filename);

		$dst_filename	=	Amslib::slugify2(basename($dst_filename),"_",".");
		$destination	=	self::reduceSlashes("$directory/$dst_filename");

		//	Try to move the file into the correct destination
		if($error == false && !rename($src_filename,$destination)){
			Amslib::errorLog("It was not possible to save to the requested filename",error_get_last());
			$error = true;
		}

		//	If there are no errors, you have uploaded the file ok, however, you could still fail here
		if($error == false && !chmod($destination,0777)){
			Amslib::errorLog("file uploaded ok (apparently), but chmod failed",$destination,error_get_last());
		}

		//	if the output was not empty, something bad happened, like a warning or error
		//	when this happens, sometimes with file operations, it can break json, or website output
		//	so I use an output buffer to grab all the output without it going to the user in some way
		//	then once I have it, I can do something with it, like output it only to the error log, etc, etc.
		$output = ob_get_clean();
		if(strlen($output)){
			Amslib::errorLog("Error or warning executing file operations",$output);
		}

		//	Set the full path of the file, it's final destination in the filesystem
		$fullpath = $destination;

		return !$error;
	}

	/**
	 * 	DEPRECATED: use move($src_filename,$directory,&$dst_filename,&$fullpath) instead
	 */
	static public function saveUploadedFile($src_filename,$directory,&$dst_filename,&$fullpath=NULL)
	{
		return self::move($src_filename,$directory,$dst_filename,$fullpath);
	}
}