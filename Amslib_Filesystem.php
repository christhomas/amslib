<?php
class Amslib_Filesystem
{
	//	The path of the website, this value is set by the website
	static protected $website = false;
	
	static public function documentRoot()
	{
		if(isset($_SERVER["DOCUMENT_ROOT"])) return $_SERVER["DOCUMENT_ROOT"];

		//	on IIS, there is no parameter DOCUMENT_ROOT, have to construct it yourself.

		//	Switch the document separators to match windows dumbass separators
		$phpself	=	str_replace("/","\\",$_SERVER["PHP_SELF"]);
		//	delete from script filename, the php self, which should reveal the base directory
		$root		=	str_replace($phpself,"",$_SERVER["SCRIPT_FILENAME"]);

		return self::removeWindowsDrive($root);
	}

	static public function removeWindowsDrive($location)
	{
		//	explode on the windows path separator,
		//	shift off the first element (the drive symbols)
		//	then re-implode it on the unix path separator
		$location		=	explode(DIRECTORY_SEPARATOR,$location);
		array_shift($location);
		$location = "/".implode("/",$location);
		//	we should now have the root
		return $location;
	}

	static public function dirname($location)
	{
		$dirname = dirname($location);

		return (strpos($dirname,":") !== false) ? self::removeWindowsDrive($dirname) : $dirname;
	}

	static public function absolute($filename)
	{
		$root		=	self::documentRoot();
		$filename	=	Amslib::lchop($filename,$root);

		return str_replace("//","/","$root/$filename");
	}
	
	static public function setWebsitePath($path)
	{
		self::$website = $path;
	}
	
	static public function website($filename,$relative=false)
	{
		//	If the website path is not set, return the path based on the docroot
		if(self::$website === false) return self::absolute($filename);
		
		$root		=	self::$website;
		$filename	=	Amslib::lchop($filename,$root);
		$filename	=	str_replace("//","/","$root/$filename");
		
		return ($relative) ? self::relative($filename) : $filename; 
	}

	static public function relative($filename)
	{
		$root		=	self::documentRoot();
		$filename	=	Amslib::lchop($filename,$root);

		return str_replace("//","/",$filename);
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
	
	static public function glob($path,$relative=false)
	{
		$list = glob(Amslib_Filesystem::absolute($path));
		
		if($relative && !empty($list)) foreach($list as &$l){
			$l = Amslib_Filesystem::relative($l);
		}
		
		return $list;
	}
}
