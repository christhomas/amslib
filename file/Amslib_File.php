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
		
		//	If the document root index exists, ues it to calculate the docroot
		if(isset($_SERVER["DOCUMENT_ROOT"]))
		{
			//	Sometimes idiotic webhosts like dinahosting.com have shit setups and we need to deal with them :)
			//	the base root dir for dirname(__FILE__) and docroot are different, here we fix that situation
			//	FIXME: this breaks code on "sane" hosts where you are using amslib from a different "root" than the website trying to use it
			//	EXAMPLE www.website.com and content.website.com, the content website will be broken by this "fix" for those dinahosting bullshits
			$missing	=	Amslib::rchop(dirname(__FILE__),$_SERVER["DOCUMENT_ROOT"]);
			//	good hosting will have empty string missing, bad hosting will have a prepend string
			$docroot	=	self::reduceSlashes("$missing/{$_SERVER["DOCUMENT_ROOT"]}");
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
		//	exlode on the windows directory separator, then slice off the first parameter
		$location = array_slice(explode(DIRECTORY_SEPARATOR,$location),1);

		//	we should now have the root (add the / to the beginning to make the path absolute);
		return "/".implode("/",$location);
	}

	static public function dirname($location)
	{
		$dirname = dirname($location);

		return (strpos($dirname,":") !== false) ? self::removeWindowsDrive($dirname) : $dirname;
	}

	static public function absolute($path)
	{
		$root	=	self::documentRoot();
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
}