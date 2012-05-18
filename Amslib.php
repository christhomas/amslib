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
 * File: Amslib.php
 * Title: Amslib core utility object
 * Project: Amslib (antimatter studios library)
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

//	Amslib helper class
class Amslib
{
	static protected $showErrorTrigger		=	false;
	static protected $originalErrorHandler	=	false;

	//	DEPRECATED: should use findPath instead, makes more sense
	static protected function findFile($filename){ return self::findPath($filename); }

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

	static public function locate()
	{
		return dirname(__FILE__);
	}

	static public function showErrors()
	{
		//	Enable all error reporting
		ini_set("display_errors", "On");
		error_reporting(E_ALL);
		self::$showErrorTrigger = true;
	}

	static public function setErrorHandler($handler)
	{
		self::$originalErrorHandler = set_error_handler($handler);
	}

	static public function restoreErrorHandler()
	{
		if(self::$originalErrorHandler){
			set_error_handler(self::$originalErrorHandler);
		}
	}

	static public function lchop($str,$search)
	{
		$p = (strlen($search)) ? strpos($str,$search) : false;

		return ($p) !== false ? substr($str,$p+strlen($search)) : $str;
	}

	static public function rchop($str,$search)
	{
		return ($p = strrpos($str,$search)) !== false ? substr($str,0,$p) : $str;
	}

	//	FIXME: I have two methods to cut a string, wtf....which do I use??
	//	NOTE: I think this one is the version I should use in the future??
	static public function truncateString($string,$length)
	{
		return CakePHP::truncate($string,$length);
	}

	//	DEPRECATED: doesn't work very well with non-ascii characters like ü
	//	NOTE: if this is deprecated does it mean I should internally call truncateString?
	static public function htmlCutString($string,$length)
	{
		$output = new HtmlCutString($string, $length);
  		return $output->cut();
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

	static public function getIncludeContents($filename)
	{
		ob_start();
		Amslib::includeFile($filename);
		$contents = ob_get_clean();

		return $contents;
	}

	static public function trimString($string,$maxlen,$postfix="...")
	{
		return (strlen($string) > $maxlen) ? substr($string,0,$maxlen).$postfix : $string;
	}

	//	blatently stolen code from: http://snipplr.com/view/22741/slugify-a-string-in-php/ :-) thank you!
	//	modified 01/08/2011: added ability to allow custom regex through so you can add terms if required
	//
	static public function slugify($text,$remove="",$replace="-")
	{
		// replace non letter or digits by -
		$text = preg_replace("~[^\\pL\d{$remove}]+~u", $replace, $text);

		// trim
		$text = trim($text, $replace);

		// transliterate
		if (function_exists('iconv')) $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace("~[^-\w{$remove}]+~", '', $text);

		return $text;
	}

	static public function var_dump($dump,$preformat=false,$hiddenOutput=false)
	{
		ob_start();
		var_dump($dump);
		$dump = ob_get_clean();

		$hiddenOutput = $hiddenOutput ? "style='display:none'" : "";

		return ($preformat) ? "<pre $hiddenOutput>$dump</pre>" : $dump;
	}

	//	Website: http://php.net
	//	User: Levofski
	//	Link: http://www.php.net/manual/en/function.print-r.php#97901
	//	Thanks, it works great!
	static public function var_dump_xml($mixed)
	{
	    // capture the output of print_r
	    $out = print_r($mixed, true);

	    // Replace the root item with a struct
	    // MATCH : '<start>element<newline> ('
	    $root_pattern = '/[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/i';
	    $root_replace_pattern = '<struct name="root" type="\\1">';
	    $out = preg_replace($root_pattern, $root_replace_pattern, $out, 1);

	    // Replace array and object items structs
	    // MATCH : '[element] => <newline> ('
	    $struct_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/miU';
	    $struct_replace_pattern = '<struct name="\\1" type="\\2">';
	    $out = preg_replace($struct_pattern, $struct_replace_pattern, $out);
	    // replace ')' on its own on a new line (surrounded by whitespace is ok) with '</var>
	    $out = preg_replace('/^\s*\)\s*$/m', '</struct>', $out);

	    // Replace simple key=>values with vars
	    // MATCH : '[element] => value<newline>'
	    $var_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_\S]+)/i';
	    $var_replace_pattern = '<var name="\\1">\\2</var>';
	    $out = preg_replace($var_pattern, $var_replace_pattern, $out);

	    $out =  trim($out);
	    $out='<?xml version="1.0"?><data>'.$out.'</data>';

	    return $out;
	}

	static public function backtrace()
	{
		$args	=	func_get_args();
		$bt		=	debug_backtrace();

		$slice	=	array($bt);
		if(count($args) && is_numeric($args[0])) $slice[] = array_shift($args);
		if(count($args) && is_numeric($args[0])) $slice[] = array_shift($args);

		if(count($slice) > 1) $bt = call_user_func_array("array_slice",$slice);

		return Amslib_Array::filterKey($bt,Amslib_Array::filterType($args,"is_string"));
	}

	static public function getStackTrace($index=NULL,$string=NULL,$var_dump=NULL)
	{
		$e = new Exception();
		$t = false;

		$t = $string ? $e->getTraceAsString() : $e->getTrace();

		if($index && is_numeric($index)){
			$t = $string
				? current(array_slice(explode("\n",$t),$index,1))
				: ($var_dump ? Amslib::var_dump($t[$index]) : $t[$index]);

			if($string || $var_dump){
				$t = htmlspecialchars($t,ENT_QUOTES,"UTF-8");
			}
		}

		return $t;
	}

	//	NOTE: This method has weird parameter names to make it harder to clash with extract()'d parameters from the $__p parameter
	static public function __importFile($__r,$__f,$__p=array(),$__b=false)
	{
		$path = "";

		if(!file_exists($__f)){
			$path = self::findFile($__f);

			if($path !== false && strlen($path)) $path = "$path/";
		}

		//	NOTE:	Cannot use Amslib_File::reduceSlashes here, chicken/egg type problem
		//	NOTE:	Actually, I think it's much more than that, you can't use anything autoloaded here
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

	static public function includeFile($file,$params=array(),$buffer=false)
	{
		return self::__importFile(false,$file,$params,$buffer);
	}

	static public function requireFile($file,$params=array(),$buffer=false)
	{
		return self::__importFile(true,$file,$params,$buffer);
	}

	static public function autoloader()
	{
		//	Only register it once.
		if(function_exists("amslib_autoload")) return;
		//	add the amslib directory and it's parent to the include path
		//	99.9999% of times, you want this to happen, so I just make it default
		self::addIncludePath(dirname(__FILE__));
		self::addIncludePath(dirname(dirname(__FILE__)));

		function amslib_autoload($c)
		{
			if($c == __CLASS__) return false;

			if(strpos($c,"Amslib_Translator")	!== false)	$c	=	"translator/$c";
			if(strpos($c,"Amslib_Router")		!== false) 	$c	=	"router/$c";
			if(strpos($c,"Amslib_Database")		!== false)	$c	=	"database/$c";
			if(strpos($c,"Amslib_XML")			!== false)	$c	=	"xml/$c";
			if(strpos($c,"Amslib_Plugin")		!== false)	$c	=	"plugin/$c";
			if(strpos($c,"Amslib_MVC")			!== false)	$c	=	"mvc/$c";
			if(strpos($c,"Amslib_Mixin")		!== false)	$c	=	"mvc/$c";
			if(strpos($c,"Amslib_File")			!== false)	$c	=	"file/$c";
			if(strpos($c,"Amslib_QueryPath")	!== false)	$c	=	"util/$c";
			if(strpos($c,"CakePHP")				!== false)	$c	=	"util/$c";
			if(strpos($c,"PiwikTracker")		!== false)	$c	=	"util/$c";
			if(strpos($c,"phpQuery")			!== false)	$c	=	"util/$c/$c";

			//	DEPRECATED: unless I can find a way to fix the utf-8 broken characters like ü
			if(strpos($c,"HtmlCutString")		!== false)	$c	=	"util/html_cut_string";

			if($c == "FirePHP")		$c	=	"util/FirePHPCore/$c.class";

			$f = str_replace("//","/","$c.php");

			return Amslib::requireFile($f);
		}

		//	register a special autoloader that will include correctly all of the amslib classes
		spl_autoload_register("amslib_autoload");
	}

	static public function shutdown($url)
	{
		function amslib_shutdown($url)
		{
			$error = array(
					"vars"	=>	get_defined_vars(),
					"fcns"	=>	get_defined_functions(),
					"clss"	=>	get_declared_classes(),
					"get"	=>	$_GET,
					"post"	=>	$_POST,
					"err"	=>	false
			);

			//	E_PARSE: you cannot catch parse errors without a prepend file.

			//	All the errors I believe to be fatal/non-recoverable/you're fucked/your code is shit
			$fatal = array(E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_COMPILE_WARNING,E_STRICT);

			if (($e = @error_get_last()) && @is_array($e) && @in_array($e["type"],$fatal)) {
				$error["err"] = array(
					"code"	=>	isset($e['type']) ? $e['type'] : 0,
					"msg"	=>	isset($e['message']) ? $e['message'] : '',
					"file"	=>	isset($e['file']) ? $e['file'] : '',
					"line"	=>	isset($e['line']) ? $e['line'] : '',
				);

				//	TODO: write the file to a logging directory

				$_SESSION["/amslib/php/fatal_error/"] = $error;
				header("Location: $url");
			}
		}

		register_shutdown_function("amslib_shutdown",$url);
	}

	static public function findKey($key,$source)
	{
		foreach($source as $k=>$ignore){
			if(strpos($k,$key) !== false) return $k;
		}

		return false;
	}

	/**
	 * 	function:	getParam
	 *
	 * 	Obtain a parameter from the GET global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the GET global array, if not exists, the value of the parameter return
	 */
	static public function getGET($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_GET,$key,$default,$erase);
	}

	static public function hasGET($key)
	{
		return (isset($_GET[$key])) ? true : false;
	}

	/**
	 *	function:	setGet
	 *
	 *	Insert a parameter into the global GET array
	 *
	 *	parameters:
	 *		$key	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	static public function setGET($key,$value)
	{
		$_GET[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	postParam
	 *
	 * 	Obtain a parameter from the POST global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the POST global array, if not exists, the value of the parameter return
	 */
	static public function getPOST($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_POST,$key,$default,$erase);
	}

	static public function hasPOST($key)
	{
		return (isset($_POST[$key])) ? true : false;
	}

	/**
	 *	function:	setPost
	 *
	 *	Insert a parameter into the global POST array
	 *
	 *	parameters:
	 *		$key	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	static public function setPOST($key,$value)
	{
		$_POST[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	sessionParam
	 *
	 * 	Obtain a parameter from the SESSION global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the SESSION global array, if not exists, the value of the parameter return
	 */
	static public function getSESSION($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_SESSION,$key,$default,$erase);
	}

	static public function hasSESSION($key)
	{
		return (isset($_SESSION[$key])) ? true : false;
	}

	static public function setSESSION($key,$value)
	{
		$_SESSION[$key] = $value;

		return $value;
	}

	/**
	 * 	COOKIE methods
	 */
	static public function getCOOKIE($key,$default=NULL)
	{
		return self::arrayParam($_COOKIE,$key,$default);
	}

	static public function hasCOOKIE($key)
	{
		return (isset($_COOKIE[$key])) ? true : false;
	}

	static public function setCOOKIE($key,$value)
	{
		$_COOKIE[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	filesParam
	 *
	 * 	Obtain a parameter from the FILES global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the FILES global array, if not exists, the value of the parameter return
	 */
	static public function getFILES($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_FILES,$key,$default,$erase);
	}

	static public function setFILES($key,$value)
	{
		$_FILES[$key] = $value;

		return $value;
	}

	/**
	 * 	function:	requestParam
	 *
	 * 	Obtain a parameter from the REQUEST global array
	 *
	 * 	parameters:
	 * 		$value		-	The value requested
	 * 		$default	-	The value to return if the value does not exist
	 * 		$erase		-	Whether or not to erase the value after it's been read
	 *
	 * 	returns:
	 * 		-	The value from the REQUEST global array, if not exists, the value of the parameter return
	 */
	static public function getREQUEST($key,$default=NULL,$erase=false)
	{
		return self::arrayParam($_REQUEST,$key,$default,$erase);
	}

	static public function setREQUEST($key,$value)
	{
		$_REQUEST[$key] = $value;

		return $value;
	}

	static public function arrayParam(&$source,$key,$default=NULL,$erase=false)

	{
		if(is_array($key)){
			$k = array_shift(array_intersect($key,array_keys($source)));
			return self::arrayParam($source,$k,$default,$erase);
		}

		if(isset($source[$key])){
			$default = $source[$key];
			if($erase) unset($source[$key]);
		}

		return $default;
	}

	//	IDEAS FOR A NEW(ER) SIMPLIFIED API
	static public function getSuper($type,$key,$default=NULL,$erase=false){}
	static public function setSuper($type,$key,$value){}

	//	DEPRECATED OLDER SUPER METHODS
	static public function insertRequestParam($key,$value){	self::setREQUEST($key,$value);	}
	static public function insertFilesParam($key,$value){	self::setFILES($key,$value);	}
	static public function insertCookieParam($key,$value){	self::setCOOKIE($key,$value);	}
	static public function insertSessionParam($key,$value){	self::setSESSION($key,$value);	}
	static public function insertPostParam($key,$value){	self::setPOST($key,$value);		}
	static public function insertGetParam($key,$value){		self::setGET($key,$value);		}

	static public function postParam($key,$default=NULL,$erase=false){ return self::getPost($key,$default,$erase); }
	static public function getParam($key,$default=NULL,$erase=false){ return self::getGET($key,$default,$erase); }
	static public function requestParam($key,$default=NULL,$erase=false){ return self::getREQUEST($key,$default,$erase);	}
	static public function filesParam($key,$default=NULL,$erase=false){ return self::getFILES($key,$default,$erase);	}
	static public function cookieParam($key,$default=NULL){ return self::getCOOKIE($key,$default); }
	static public function sessionParam($key,$default=NULL,$erase=false){ return self::getSESSION($key,$default,$erase); }

	//	DEPRECATED FUNCTION
	static public function exceptionBacktrace($string=false){
		return self::getStackTrace(NULL,true);
	}
}
