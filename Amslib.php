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
 * Version: 2.7
 * Project: Amslib (antimatter studios library)
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

//	Amslib helper class
class Amslib
{
	const VERSION = 2.8;

	static $showErrorTrigger = false;

	function locate()
	{
		return dirname(__FILE__);
	}

	function showErrors()
	{
		//	Enable all error reporting
		ini_set("display_errors", "On");
		error_reporting(E_ALL);
		self::$showErrorTrigger = true;
	}

	/**
	 *	function:	addIncludePath
	 *
	 *	Add an include path to the PHP include path
	 *
	 *	parameters:
	 *		$path	-	The path to add to the PHP include path
	 */
	function addIncludePath($path)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		$valid = true;
		foreach($includePath as $p){
			if(strcmp($path,$p) == 0) $valid = false;
		}

		if($valid) array_unshift($includePath,$path);
		ini_set("include_path",implode(PATH_SEPARATOR,$includePath));
	}

	function getIncludeContents($filename)
	{
		ob_start();
		include($filename);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	function var_dump($dump,$preformat=false)
	{
		ob_start();
		var_dump($dump);
		$dump = ob_get_contents();
		ob_end_clean();

		return ($preformat) ? "<pre>$dump</pre>" : $dump;
	}

	function findPath($file)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));

		foreach($includePath as $p){
			$test = (strpos($file,"/") !== 0) ? "$p/$file" : "{$p}{$file}";
			if(@file_exists($test)) return $p;
		}

		return false;
	}

	function includeFile($file,$data=array())
	{
		$path = "";

		if(!file_exists($file)){
			$path = self::findPath($file);

			if($path !== false && strlen($path)) $path = "$path/";
		}

		$file = "{$path}$file";

		if(is_file($file) && file_exists($file)){
			if(is_array($data) && count($data)) extract($data, EXTR_SKIP);
			include($file);

			return true;
		}

		return false;
	}

	function requireFile($file,$data=array())
	{
		$path = "";

		if(!file_exists($file)){
			$path = self::findPath($file);

			if($path !== false && strlen($path)) $path = "$path/";
		}

		$file = "{$path}$file";

		if(is_file($file) && file_exists($file)){
			if(is_array($data) && count($data)) extract($data, EXTR_SKIP);

			if(isset($data["require_once"])) require_once($file);
			else require($file);

			return true;
		}

		return false;
	}

	function autoloader()
	{
		//	Only register it once.
		if(function_exists("amslib_autoload")) return;
		//	add the amslib directory and it's parent to the include path
		//	99.9999% of times, you want this to happen, so I just make it default
		self::addIncludePath(dirname(__FILE__));
		self::addIncludePath(dirname(dirname(__FILE__)));

		function amslib_autoload($class_name)
		{
			if($class_name == __CLASS__) return false;

			//	Special case for the FirePHP library
			if($class_name == "FirePHP"){
				$class_name	=	"util/FirePHPCore/$class_name.class";
			}

			//	Redirect to include the correct path for the translator system
			if(strpos($class_name,"Amslib_Translator") !== false){
				$class_name	=	"translator/$class_name";
			}

			//	Redirect to include the correct path for the router system
			if(strpos($class_name,"Amslib_Router") !== false){
				$class_name	=	"router/$class_name";
			}

			$class_name = str_replace("//","/","$class_name.php");

			return Amslib::requireFile($class_name);
		}

		//	register a special autoloader that will include correctly all of the amslib classes
		spl_autoload_register("amslib_autoload");
	}

	function findKey($key,$source)
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
	function getParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_GET,$value,$default,$erase);
	}

	/**
	 *	function:	insertGetParameter
	 *
	 *	Insert a parameter into the global GET array
	 *
	 *	parameters:
	 *		$parameter	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	function insertGetParam($parameter,$value)
	{
		$_GET[$parameter] = $value;
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
	function postParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_POST,$value,$default,$erase);
	}

	/**
	 *	function:	insertPostParameter
	 *
	 *	Insert a parameter into the global POST array
	 *
	 *	parameters:
	 *		$parameter	-	The parameter to insert
	 *		$value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	function insertPostParam($parameter,$value)
	{
		$_POST[$parameter] = $value;
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
	function sessionParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_SESSION,$value,$default,$erase);
	}

	function insertSessionParam($parameter,$value)
	{
		$_SESSION[$parameter] = $value;
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
	function filesParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_FILES,$value,$default,$erase);
	}

	function arrayParam(&$source,$value,$default=NULL,$erase=false)
	{
		if(isset($source[$value])){
			$default = $source[$value];
			if($erase) unset($source[$value]);
		}

		return $default;
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
	function requestParam($value,$default=NULL,$erase=false)
	{
		return self::arrayParam($_REQUEST,$value,$default,$erase);
	}

	function insertRequestParam($parameter,$value)
	{
		$_REQUEST[$parameter] = $value;
	}
}