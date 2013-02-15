<?php
class Amslib_File
{
	static protected $docroot = false;

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

		self::$docroot = $docroot;

		return self::$docroot;
	}

	static public function removeWindowsDrive($location)
	{
		if(strpos($location,":") !== false && isset($_SERVER["WINDIR"])){
			$location = array_slice(explode(":",str_replace("\\","/",$location)),1);

			return implode("/",$location);
		}

		return $location;
	}

	static public function dirname($location)
	{
		$dirname = dirname($location);

		return (strpos($dirname,":") !== false) ? self::removeWindowsDrive($dirname) : $dirname;
	}

	static public function absolute($path)
	{
		$root	=	self::documentRoot();
		$path	=	self::removeWindowsDrive($path);
		$rel	=	Amslib::lchop($path,$root);

		return self::reduceSlashes("$root/$rel");
	}

	static public function relative($path)
	{
		$root	=	self::documentRoot();
		$rel	=	Amslib::lchop($path,$root);

		return self::reduceSlashes("/$rel");
	}

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

	static public function removeTrailingSlash($path)
	{
		//	Make sure the path doesnt end with a trailing slash
		$path = str_replace("/__END__","",$path."__END__");
		//	Cleanup after the attempt to detect trailing slash
		$path = str_replace("__END__","",$path);

		return $path;
	}

	static public function reduceSlashes($string)
	{
		return preg_replace('#//+#','/',$string);
	}

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

	static public function glob($path,$relative=false)
	{
		$list = glob(self::absolute($path));

		if($relative && !empty($list)) foreach($list as &$l){
			$l = self::relative($l);
		}

		return $list;
	}
	
	static public function saveUploadedFile($src_filename,$directory,$dst_filename)
	{
		$error = false;

		//	NOTE: Perhaps all this checking and copying or directories etc, should be formalised into the api??
		//	If the destination directory doesnt exist, attempt to create it
		if($error == false && !is_dir($directory) && !@mkdir($directory,0755,true)){
			$error = true;
		}

		//	It REALLY REALLY should exist now, but lets check just in case
		if($error == false && !is_dir($directory)){
			$error = true;
		}

		$destination = self::reduceSlashes("$directory/$dst_filename");

		//	Try to move the file into the correct destination
		if($error == false && !rename($src_filename,$destination)){
			$error = true;
		}

		//	If there are no errors, you have uploaded the file ok.
		if($error == false){
			chmod($destination,0755);
		}
		
		return !$error;
	}
}