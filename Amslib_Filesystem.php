<?php
class Amslib_Filesystem
{
	static public function documentRoot()
	{
		if(isset($_SERVER["DOCUMENT_ROOT"]))
		{
			//	Sometimes idiotic webhosts like dinahosting.com have shit setups and we need to deal with them :)
			//	the base root dir for dirname(__FILE__) and docroot are different, here we fix that situation
			$missing = Amslib::rchop(dirname(__FILE__),$_SERVER["DOCUMENT_ROOT"]);
			//	good hosting will have empty string missing, bad hosting will have a prepend string
			return str_replace("//","/","$missing/{$_SERVER["DOCUMENT_ROOT"]}");
		}

		//	on IIS, there is no parameter DOCUMENT_ROOT, have to construct it yourself.

		//	Switch the document separators to match windows dumbass separators
		$phpself	=	str_replace("/","\\",$_SERVER["PHP_SELF"]);
		//	delete from script filename, the php self, which should reveal the base directory
		$root		=	str_replace($phpself,"",$_SERVER["SCRIPT_FILENAME"]);

		return self::removeWindowsDrive($root);
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

	static public function absolute($filename)
	{
		$root		=	self::documentRoot();
		$filename	=	Amslib::lchop($filename,$root);

		return str_replace("//","/","$root/$filename");
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
