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
 * 	class:	Amslib
 *
 *	group:	core
 *
 *	file:	Amslib.php
 *
 *	description:
 *		This is a core file within the Amslib framework, it's responsible for the
 *		autoloading of PHP classes and handling the including of PHP scripts with extra
 *		functionality on top of the base PHP functionality.
 *
 *		Using Amslib::autoloader() will create an autoloader which can search more intelligently
 *		your include path for matching PHP classes and load them
 *
 *		Using Amslib::requireFile() will search intelligently your path in a better way
 *		than basic the PHP functionality, because it will allow relative file name
 *		searches as PHP does not
 */
class Amslib
{
	static protected $autoload_silent = array(
		"standard"	=> false,
		"exception"	=> false
	);

	/**
	 * 	method:	importFile
	 *
	 * 	params:
	 * 		$__r - Whether the file is required/included
	 * 		$__f - The file to process
	 * 		$__p - The parameter array to expand
	 * 		$__b - To grab the output into a buffer or include directly
	 *
	 * 	returns:
	 * 		boolean false if the function has failed, the return value of require/include, or output buffer if requested
	 *
	 * 	NOTE:
	 * 		-	This method has weird parameter names to make it harder
	 * 			to clash with extract()'d parameters from the $__p parameter
	 */
	static protected function importFile($__r,$__f,$__p=false,$__b=false)
	{
		if(!is_array($__p)) $__p = array();

		if(is_array($__f)){
			Amslib_Debug::log("array was passed, string is required",$__f);
			return false;
		}

		$path = "";

		if(!file_exists($__f)){
			$path = self::findPath($__f);

			if($path !== false && strlen($path)) $path = "$path/";
		}

		//	NOTE:	Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
		//	NOTE:	Actually, I think it's much more than that, you can't use anything autoloaded
		//			here because this method is used by the autoloader
		$__f = preg_replace('#//+#','/',"{$path}$__f");

		if(is_file($__f) && file_exists($__f)){
			if(is_array($__p) && count($__p)) extract($__p, EXTR_SKIP);

			//	Optional output buffering
			if($__b) ob_start();

			$__v = $__r
				? (isset($require_once) ? require_once($__f) : require($__f))
				: (isset($include_once) ? include_once($__f) : include($__f));

			if($__b) $__v = ob_get_clean();

			//	Return the result of the require/include, or if output buffering is enabled, return whatever was captured
			return $__v;
		}

		return false;
	}

	/**
	 * 	method:	findPath
	 *
	 * 	Find a file include the include path, allowing for relative path names, which is
	 * 	something that PHP does not provide by default.
	 *
	 *	parameters:
	 *		$filename - The file to search for
	 *
	 *	returns:
	 *		Boolean false if not found, or a string containing the path the file was found with
	 *
	 *	notes:
	 *		-	The return does not include the filename requested, it only contains the
	 *			path that was used to find the file.
	 */
	static protected function findPath($filename)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		foreach($includePath as $path){
			//	NOTE: Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
			$test = preg_replace('#//+#','/',"$path/$filename");
			if(@file_exists($test)) return $path;
		}

		return false;
	}

	/**
	 * 	method:	locate
	 *
	 * 	A method to "locate" the Amslib.php script within the website automatically, so it
	 * 	can be used as an anchor to automatically know where amslib is installed
	 *
	 * 	returns:
	 * 		A string containing the directory name of the Amslib.php script within the website
	 */
	static public function locate()
	{
		return dirname(__FILE__);
	}

	/**
	 * 	method: safeCall
	 *
	 * 	A method to call a function with parameters, in a way which won't output anything to the browser
	 * 	but will drop any error or feedback output to the error log, this is useful when you absolutely have no
	 * 	guarantee that no output will reach the browser causeing annoying issues
	 *
	 * 	params:
	 * 		$function - The function to execute by passing it to call_user_func_arra
	 * 		$params - The parameters to pass to the function
	 *
	 * 	returns:
	 * 		Whatever the function you've executed returns
	 */
	static public function safeCall($function,$params=array())
	{
		ob_start();
		$return = call_user_func_array($function,$params);
		$output = ob_get_clean();

		if(strlen($output)){
			Amslib_Debug::log($output);
		}

		return $return;
	}

	/**
	 * 	method:	getRandomCode
	 *
	 * 	todo: write documentation
	 *	I wonder if this is true, or it's bullshit? could ask someone to verify whether they think it's safe or not
	 *	TODO: I was told to replace this with a call to crypt()
	 *	TODO: maybe this should move to the Amslib_String object, since it's a string function?
	 *	NOTE: actually it's more a utility function, so I'm not sure where to put it, or just leave it here
	 */
	static public function getRandomCode($input=NULL)
	{
		$args	=	func_get_args();
		$salt	=	"34v87tetnseoyrtq".
					"p3498534978qnxp3".
					"895vbpq34985ox4r".
					"gwefijoiwy4cbo9t";

		$input = $input !== NULL ? Amslib_Debug::pdump(true,$args) : "";

		return sha1(implode("__",array($salt,$input,microtime(true),mt_rand(0,21387132987),$salt)));
	}

	/**
	 *	function:	addIncludePath
	 *
	 *	Add an include path to the PHP include path
	 *
	 *	parameters:
	 *		$path	-	The path to add to the PHP include path
	 */
	static public function addIncludePath($path)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		$valid = true;
		foreach($includePath as $p){
			if(strcmp($path,$p) == 0) $valid = false;
		}

		if($valid) array_unshift($includePath,$path);
		ini_set("include_path",implode(PATH_SEPARATOR,$includePath));
	}

	/**
	 * 	method:	getIncludeContents
	 *
	 *	Obtain a files contents when it was included as a string instead of just importing it into the
	 *	current output, which is useful if errors occur, it'll grab those instead of dumping them to the page
	 *	and breaking the layout
	 *
	 *	parameters:
	 *		$filename - the filename to obtain the contents of
	 *
	 *	returns:
	 *		The output from including that file, as a string
	 */
	static public function getIncludeContents($filename)
	{
		ob_start();
		self::includeFile($filename);
		return ob_get_clean();
	}

	/**
	 * 	method:	requireFile
	 *
	 * 	todo: write documentation
	 */
	static public function includeFile($file,$params=false,$buffer=false)
	{
		return self::importFile(false,$file,$params,$buffer);
	}

	/**
	 * 	method:	requireFile
	 *
	 * 	todo: write documentation
	 */
	static public function requireFile($file,$params=false,$buffer=false)
	{
		return self::importFile(true,$file,$params,$buffer);
	}

	/**
	 * 	method:	autoloader
	 *
	 * 	todo: write documentation
	 */
	static public function autoloader($standard=true,$exception=true)
	{
		self::autoloaderStandard(!!$standard);
		self::autoloaderException(!!$exception);
	}

	static public function autoloaderExists($function,$state)
	{
		//	Don't register multiple times and optionally unregister when requested if state is set to false
		$exists		=	function_exists($function);
		if($exists || $state == false){
			if($exists) spl_autoload_unregister($function);

			return true;
		}

		return false;
	}

	static public function autoloaderStatus($name)
	{
		return isset(self::$autoload_silent[$name]) ? self::$autoload_silent[$name] : false;
	}

	static public function autoloaderStandard($state=true,$silent=NULL)
	{
		if($silent !== NULL) self::$autoload_silent["standard"] = $silent;

		if(self::autoloaderExists("amslib_autoload_standard",$state)){
			return false;
		}

		//	add the amslib directory and it's parent to the include path
		//	99.9999% of times, you want this to happen, so I just make it default
		self::addIncludePath(dirname(__FILE__));
		self::addIncludePath(dirname(dirname(__FILE__)));

		function amslib_autoload_standard($c)
		{
			if($c == __CLASS__) return false;

			if(in_array($c,array(
					"Amslib_COOKIE",
					"Amslib_FILES",
					"Amslib_SERVER",
					"Amslib_GET",
					"Amslib_POST",
					"Amslib_REQUEST",
					"Amslib_SESSION",
					"Amslib_GLOBAL"
			)))
			{
				$c = "global/$c";
			}

			if(strpos($c,"Amslib_Exception")	=== 0)	$c	=	"exception/$c";
			if(strpos($c,"Amslib_Translator")	=== 0)	$c	=	"translator/$c";
			if(strpos($c,"Amslib_Router")		=== 0) 	$c	=	"router/$c";
			if(strpos($c,"Amslib_Database")		=== 0)	$c	=	"database/$c";
			if(strpos($c,"Amslib_XML")			=== 0)	$c	=	"xml/$c";
			if(strpos($c,"Amslib_Plugin")		=== 0)	$c	=	"plugin/$c";
			if(strpos($c,"Amslib_MVC")			=== 0)	$c	=	"mvc/$c";
			if(strpos($c,"Amslib_Mixin")		=== 0)	$c	=	"mvc/$c";
			if(strpos($c,"Amslib_File")			=== 0)	$c	=	"file/$c";
			if(strpos($c,"Amslib_QueryPath")	=== 0)	$c	=	"util/$c";
			if(strpos($c,"Amslib_Webservice")	===	0)	$c	=	"webservice/$c";
			if(strpos($c,"PiwikTracker")		=== 0)	$c	=	"util/$c";
			if(strpos($c,"phpQuery")			=== 0)	$c	=	"util/$c/$c";
			if(strpos($c,"QRcode")				=== 0)	$c	=	"util/phpqrcode/phpqrcode";
			if(strpos($c,"AesCtr")				=== 0)	$c	=	"util/$c";
			if(strpos($c,"Logger")				=== 0)	$c	=	"util/apache-log4php/src/main/php/$c";

			if($c == "Facebook")						$c	=	"util/facebook-php-sdk/src/facebook";
			if($c == "FirePHP") 						$c	=	"util/FirePHPCore/$c.class";
			if($c == "HTMLPurifier")					$c	=	"util/HTMLPurifier.standalone";
			if($c == "mPDF")							$c	=	"util/mpdf-5.4/mpdf";

			$f = str_replace("//","/","$c.php");

			//	NOTE: we should use the silent flag here to output any errors I suppose

			return Amslib::requireFile($f);
		}

		//	register a special autoloader that will include correctly all of the amslib classes
		spl_autoload_register("amslib_autoload_standard");

		return true;
	}

	/**
	 *	method: autoloaderException
	 *
	 *	This method registers an autoloader which will attempt to load classes via
	 *	exception handlers as a last "please don't kill me billy!!!" resort.
	 *
	 *	it's probably not a good idea to use this if you can avoid it, however I have to admit
	 *	for the plugin functionality, it's quite useful since it allows auto-discovery of non-include-path
	 *	classes without further programmer intervention.
	 *
	 *	however if this situation could be avoided in the future using a more direct approach, it probab;y
	 *	would be a lot more optimal.
	 *
	 *	note:		If you could not include the file for some reason, attempt to use an
	 *				exception to backtrace into the directory and look there, this will
	 *				fix __SOME__ problems with classes which are not in the include path,
	 *				but do exist in the same directory as the parent class that it was
	 *				inherited from, inheriting from or being used from
	 *
	 *	warning:	I don't know the performance impact of this code, but better to work slowly, than not at all.
	 *
	 *	note:		I purposefully block any class name containing a namespace
	 *				(I detect the \ character in the class name), since amslib
	 *				doesn't use them, I can be sure this will fail, why incur
	 *				the cost of creating an exception when you know it will never load a file
	 */
	static public function autoloaderException($state=true,$silent=NULL)
	{
		if($silent !== NULL) self::$autoload_silent["exception"] = $silent;

		if(self::autoloaderExists("amslib_autoload_exception",$state)){
			return false;
		}

		function amslib_autoload_exception($c)
		{
			if($c == __CLASS__) return false;

			$result = false;

			if(strpos($c,'\\') === false){
				$e = new Exception();
				$t = $e->getTrace();
				if(isset($t[1]) && isset($t[1]["file"])){
					$result = Amslib::requireFile(dirname($t[1]["file"])."/$c.php");

					if($result && !Amslib::autoloaderStatus("exception")){
						Amslib_Debug::log("EXCEPTION AUTOLOADER: we loaded the class '$c' this is not efficient");
						Amslib_Debug::log("EXCEPTION AUTOLOADER: call Amslib::autoloader_exception(true,true) to remove this warning");
					}
				}
			}

			return $result;
		}

		spl_autoload_register("amslib_autoload_exception");

		return true;
	}
}

//////////////////////////////////////////////////////////////
//	Automatically install the amslib autoloader
//		============================================
//	Amslib is going to be difficult to use without it,
//	it would be required to manually include all sorts
//	of files and cause unnecessary complexity
//
//		============================================
//	You can turn off the autoloaders by using
//		Amslib::autoloader(false,false);
//////////////////////////////////////////////////////////////
Amslib::autoloader(true,true);