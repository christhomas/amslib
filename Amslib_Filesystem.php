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
		//	explode on the windows path separator,
		//	shift off the first element (the drive symbols)
		//	then re-implode it on the unix path separator
		$root		=	explode("\\",$root);
		array_shift($root);
		$root = "/".implode("/",$root);
		//	we should now have the root
		return $root;
	}
}