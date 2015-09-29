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
    static protected $FATAL     =   array(E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,E_COMPILE_WARNING,E_STRICT,E_USER_ERROR);
    static protected $WARNINGS  =   array(E_WARNING,E_NOTICE,E_CORE_WARNING,E_USER_WARNING,E_RECOVERABLE_ERROR,E_DEPRECATED);

    static protected $shutdown_url = false;
    static protected $callback = false;
    static protected $trap_list = [];

    static protected function __exec_shutdown($data)
    {
        if(is_callable(self::$callback)){
            call_user_func(self::$callback,$data);
        }

        die("Amslib_Shutdown::setMode was not called with one of the following parameters [html,json,text]");
    }

    static public function setMode($mode="html")
    {
        if(in_array($mode,array("html","text","json")))
        {
            self::$callback = ["Amslib_Shutdown","render".ucwords($mode)];
        }
    }

    static public function setup($url,$trap_warnings=false)
    {
        self::$shutdown_url	= $url;

        //	E_PARSE: you cannot catch parse errors without a prepend file.
        //	NOTE: I think this has to do with being a different apache request stage

        //	All the errors I believe to be fatal/non-recoverable/you're fucked/your code is shit
        self::$trap_list = $trap_warnings ? array_merge(self::$FATAL,self::$WARNINGS) : self::$FATAL;

        set_error_handler(array("Amslib_Shutdown","__exception_error_handler"));

        register_shutdown_function(array("Amslib_Shutdown","__shutdown_handler"));

        set_exception_handler(array("Amslib_Shutdown","__shutdown_exception"));
    }

    static public function __exception_error_handler($errno, $errstr, $errfile, $errline)
    {
        if(in_array($errno,self::$trap_list)) {
            if (strpos($errstr, "open_basedir restriction in effect") !== false) {
                throw new Amslib_Exception_Openbasedir($errstr, $errno);
            } else {
                throw new Amslib_Exception($errstr, $errno);
            }
        }else{
            Amslib_Debug::log("Error/Warning occurred but did not trigger exception",$errno,$errstr,$errfile,$errline);
        }
    }

    /**
     * 	method:	__shutdown_handler
     *
     * 	todo: write documentation
     */
    static public function __shutdown_handler()
    {
        $e = @error_get_last();

        if($e && is_array($e) && array_key_exists("type",$e) && in_array($e["type"],self::$trap_list))
        {
            $data = array(
                "code"	=>	isset($e['type']) ? $e['type'] : 0,
                "msg"	=>	isset($e['message']) ? $e['message'] : '',
                "file"	=>	isset($e['file']) ? $e['file'] : '',
                "line"	=>	isset($e['line']) ? $e['line'] : '',
                "uri"	=>	$_SERVER["REQUEST_URI"],
                "root"	=>	isset($_SERVER["__WEBSITE_ROOT__"]) ? $_SERVER["__WEBSITE_ROOT__"] : "/"
            );

            self::__exec_shutdown($data);
        }
    }

    /**
     * 	method: __shutdown_exception
     *
     * 	todo: write documentation
     */
    static public function __shutdown_exception($e)
    {
        $stack = Amslib_Debug::getStackTrace("exception", $e);

        if (empty($stack)) $stack = array(array("file" => "__STACK_ERROR__", "line" => "__STACK_ERROR__"));
        if (!array_key_exists("file", $stack[0])) $stack[0]["file"] = "__FILE_NOT_AVAILABLE__";
        if (!array_key_exists("line", $stack[0])) $stack[0]["line"] = "__LINE_NOT_AVAILABLE__";

        $data = array(
            "error" => "Uncaught Exception",
            "code" => get_class($e),
            "msg" => $e->getMessage(),
            "data" => is_callable(array($e, "getData")) ? $e->getData() : false,
            "file" => $stack[0]["file"],
            "line" => $stack[0]["line"],
            "stack" => $stack,
            "uri" => $_SERVER["REQUEST_URI"],
            "root" => isset($_SERVER["__WEBSITE_ROOT__"]) ? $_SERVER["__WEBSITE_ROOT__"] : "/"
        );

        self::__exec_shutdown($data);
    }

    static public function renderHtml($data)
    {
        if(self::$shutdown_url){
            header("Location: ".self::$shutdown_url."?data=".base64_encode(json_encode($data)));
            die("waiting to redirect");
        }

        die(__METHOD__.", url was invalid, cannot execute");
    }

    static public function renderText($data)
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