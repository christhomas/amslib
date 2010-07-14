<?php
class Amslib_Filesystem
{
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
		$docroot	=	self::documentRoot();
		$filename	=	str_replace($docroot,"",$filename);
		
		return str_replace("//","/",$docroot.$filename);
	}
	
	static public function relative($filename)
	{
		$docroot	=	self::documentRoot();
		$filename	=	str_replace($docroot,"",$filename);

		return str_replace("//","/",$filename);
	}
}