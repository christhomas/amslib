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
 * 	class:	Amslib_Shutdown
 *
 *	group:	core
 *
 *	file:	Amslib_Shutdown.php
 *
 */
class Amslib_Shutdown
{
	static protected $shutdown_url = false;
	static protected $mode = "html";

	static public function setMode($mode="html")
	{
		if(in_array($mode,array("html","text","json"))){
			self::$mode = $mode;
		}
	}

	static public function setup($url,$trap_warnings=false)
	{
		self::$shutdown_url = $url;

		register_shutdown_function(array("Amslib_Shutdown","__shutdown_handler"),$trap_warnings);

		set_exception_handler(array("Amslib_Shutdown","__shutdown_exception"));
	}

	/**
	 * 	method:	__shutdown_handler
	 *
	 * 	todo: write documentation
	 */
	static public function __shutdown_handler($trap_warnings)
	{
		$url = self::$shutdown_url;

		if(!$url){
			exit(__METHOD__.", url was invalid, cannot execute");
		}

		//	E_PARSE: you cannot catch parse errors without a prepend file.
		//	NOTE: I think this has to do with being a different apache request stage

		//	All the errors I believe to be fatal/non-recoverable/you're fucked/your code is shit
		$fatal = array(E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_COMPILE_WARNING,E_STRICT,E_USER_ERROR);

		if($trap_warnings){
			$fatal+=array(E_WARNING,E_NOTICE,E_CORE_WARNING,E_USER_WARNING,E_RECOVERABLE_ERROR);
		}

		$e = @error_get_last();

		if($e && is_array($e) && array_key_exists("type",$e) && in_array($e["type"],$fatal))
		{
			$data = array(
				"code"	=>	isset($e['type']) ? $e['type'] : 0,
				"msg"	=>	isset($e['message']) ? $e['message'] : '',
				"file"	=>	isset($e['file']) ? $e['file'] : '',
				"line"	=>	isset($e['line']) ? $e['line'] : '',
				"uri"	=>	$_SERVER["REQUEST_URI"],
				"root"	=>	isset($_SERVER["__WEBSITE_ROOT__"]) ? $_SERVER["__WEBSITE_ROOT__"] : "/"
			);

			switch(self::$mode){
				case "html":{
					self::renderHTML($url,$data);
				}break;

				case "text":{
					self::renderText($url,$data);
				}break;

				case "json":{
					self::renderJSON($data);
				}break;
			}
		}
	}

	/**
	 * 	method: __shutdown_exception
	 *
	 * 	todo: write documentation
	 */
	static public function __shutdown_exception($e)
	{
		$url = self::$shutdown_url;

		if(!$url){
			exit(__METHOD__.", url was invalid, cannot execute");
		}

		$stack = Amslib_Debug::getStackTrace("exception",$e);

		$data = array(
				"error"	=> "Uncaught Exception",
				"code"	=> get_class($e),
				"msg"	=> $e->getMessage(),
				"file"	=> $stack[0]["file"],
				"line"	=> $stack[0]["line"],
				"stack"	=> $stack,
				"uri"	=> $_SERVER["REQUEST_URI"],
				"root"	=> isset($_SERVER["__WEBSITE_ROOT__"]) ? $_SERVER["__WEBSITE_ROOT__"] : "/"
		);

		switch(self::$mode){
			case "html":{
				self::renderHTML($url,$data);
			}break;

			case "text":{
				self::renderText($url,$data);
			}break;

			case "json":{
				self::renderJSON($data);
			}break;
		}
	}

	static public function renderHTML($url,$data)
	{
		header("Location: $url?data=".base64_encode(json_encode($data)));
	}

	static public function renderText($url,$data)
	{
		self::renderJSON($data);
	}

	static public function renderJSON($data)
	{
		header("Cache-Control: no-cache");
		header("Content-Type: application/json");

		die(json_encode($data));
	}
}
