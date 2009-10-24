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
 * File: amslib.php
 * Title: Amslib core utility object
 * Version: 1.0
 * Project: amslib
 * 
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

//	PROBABLY REMOVE THIS
$page = basename($_SERVER["PHP_SELF"]);

class amslib
{
	function showErrors()
	{
		//	Enable all error reporting
		ini_set("display_errors", "On");
		error_reporting(E_ALL);
	}
	
	/**
	 *	function:	addIncludePath
	 *
	 *	Add an include path to the PHP include path
	 *
	 *	parameters:
	 *		path	-	The path to add to the PHP include path
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
	
	function getVarDump($dump)
	{
		ob_start();
		var_dump($dump);
		$dump = ob_get_contents();
		ob_end_clean();
	
		return $dump;
	}
	
	function include_file($file,$showError=false)
	{
		$includePath = explode(PATH_SEPARATOR,ini_get("include_path"));
		
		foreach($includePath as $p){
			$test = (strpos($file,"/") !== 0) ? "$p/$file" : "{$p}{$file}";
			if(file_exists($test)){
				return @include_once($test);
			}	
		}
		
		if($showError) print("INCLUDE PATH($file) = <pre>".print_r($includePath,true)."</pre>");
		
		return false;
	}
	
	/**
	 * 	function:	getParam
	 * 	
	 * 	Obtain a parameter from the GET global array
	 * 
	 * 	parameters:
	 * 		value	-	The value requested
	 * 		return	-	The value to return if the value does not exist
	 * 		erase	-	Whether or not to erase the value after it's been read
	 * 
	 * 	returns:
	 * 		-	The value from the GET global array, if not exists, the value of the parameter return
	 */
	function getParam($value,$return=NULL,$erase=false)
	{
		if(isset($_GET[$value])){
			$ret = $_GET[$value];
			if($erase) unset($_GET[$value]);
		}else $ret = $return;
		
		return $ret;
	}
	
	/**
	 *	function:	insertGetParameter
	 *
	 *	Insert a parameter into the global GET array
	 *
	 *	parameters:
	 *		parameter	-	The parameter to insert
	 *		value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	function insertGetParam($parameter,$value)
	{
		$_GET[$parameter] = $value;
	}
	
	/**
	 *	function:	insertPostParameter
	 *
	 *	Insert a parameter into the global POST array
	 *
	 *	parameters:
	 *		parameter	-	The parameter to insert
	 *		value		-	The value of the parameter being inserted
	 *
	 *	notes:
	 *		-	Sometimes this is helpful, because it can let you build certain types of code flow which arent possible otherwise
	 */
	function insertPostParam($parameter,$value)
	{
		$_POST[$parameter] = $value;
	}
	
	/**
	 * 	function:	postParam
	 * 	
	 * 	Obtain a parameter from the POST global array
	 * 
	 * 	parameters:
	 * 		value	-	The value requested
	 * 		return	-	The value to return if the value does not exist
	 * 		erase	-	Whether or not to erase the value after it's been read
	 * 
	 * 	returns:
	 * 		-	The value from the POST global array, if not exists, the value of the parameter return
	 */
	function postParam($value,$return=NULL,$erase=false)
	{
		if(isset($_POST[$value])){
			$ret = $_POST[$value];
			if($erase) unset($_POST[$value]);
		}else $ret = $return;
		
		return $ret;
	}
	
	/**
	 * 	function:	sessionParam
	 * 	
	 * 	Obtain a parameter from the SESSION global array
	 * 
	 * 	parameters:
	 * 		value	-	The value requested
	 * 		return	-	The value to return if the value does not exist
	 * 		erase	-	Whether or not to erase the value after it's been read
	 * 
	 * 	returns:
	 * 		-	The value from the SESSION global array, if not exists, the value of the parameter return
	 */
	function sessionParam($value,$return=NULL,$erase=false)
	{
		if(isset($_SESSION[$value])){
			$ret = $_SESSION[$value];
			if($erase) unset($_SESSION[$value]);
		}else $ret = $return;
		
		return $ret;
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
	 * 		value	-	The value requested
	 * 		return	-	The value to return if the value does not exist
	 * 		erase	-	Whether or not to erase the value after it's been read
	 * 
	 * 	returns:
	 * 		-	The value from the FILES global array, if not exists, the value of the parameter return
	 */
	function filesParam($value,$return=NULL,$erase=false)
	{
		if(isset($_FILES[$value])){
			$ret = $_FILES[$value];
			if($erase) unset($_FILES[$value]);
		}else $ret = $return;
		
		return $ret;
	}
	
	/**
	 * 	function:	requestParam
	 * 	
	 * 	Obtain a parameter from the GET or POST global array
	 * 
	 * 	parameters:
	 * 		value	-	The value requested
	 * 		return	-	The value to return if the value does not exist
	 * 		erase	-	Whether or not to erase the value after it's been read
	 * 		order	-	A two element array specifying what order to obtain the parameter in { get, post } is the default order
	 * 
	 * 	returns:
	 * 		-	The value from the GET or POST global array, if not exists, the value of the parameter return
	 */
	function requestParam($value,$return=NULL,$erase=false,$order=array("getParam","postParam"))
	{
		$ret = amslib::$order[0]($value,$return,$erase);	
		if($ret === NULL) $ret = amslib::$order[1]($value,$return,$erase);
		
		return $ret;
	}
}
?>
