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
 * 	class:	Amslib_Log
 *
 *	group:	core
 *
 *	file:	Amslib_Log.php
 *
 *	description: TODO
 */
class Amslib_Log extends Amslib_Mixin
{
	protected $log4php;

	protected $appender;

	protected $appenderMap = array(
		"file"	=>	"LoggerAppenderFile"
	);

	static protected $defaultLogger = "error_log";

	protected function createFileLog(&$filename)
	{
		$dirname	= dirname($filename);
		$basename	= Amslib::slugify2(basename($filename),"-",".");
		$filename = "$dirname/$basename";

		if(file_exists($filename)) return true;

		if(!is_dir(dirname($filename)) && !@mkdir(dirname($filename),0777,true)){
			Amslib::errorLog(error_get_last(),$dirname,$basename,$filename);
			return false;
		}

		$t=$c=0;

		if($t=@touch($filename) && $c=@chmod($filename,0777)){
			return true;
		}

		Amslib::errorLog(__METHOD__,"file failed to create, or modify it's permissions",error_get_last(),"touch=".intval($t),"chmod=".intval($c));

		return false;
	}

	//	FIXME:	this method no longer works, it's supposed to obtain the function call which was the first entry point
	//			into the logging system, so we can record the moment the logging system started, we can report where
	//			in the code the person should look for the origin of these logging requests, but since I changed
	//			around a lot the logging system, the assumptions and code it uses is now out of date :(
	static protected function getLogOrigin()
	{
		$stack = Amslib::getStackTrace();

		if(!is_numeric($function)) $function = count($stack) > 1 ? 1 : 0;

		$line		= array("line"=>-1);
		$function	= false;

		if($function == 0){
			if(isset($stack[$function])){
				$line		= $stack[$function]["line"];
				$f			= explode("/",$stack[$function]["file"]);
				$function	= array(
						"class"=>"",
						"type"=>"",
						"function"=>end($f)
				);
			}
		}else{
			if(isset($stack[$function-1])){
				$line = $stack[$function-1]["line"];
			}

			if(isset($stack[$function])){
				$function = $stack[$function];
			}
		}

		if(!$function || !isset($function["class"]) || !isset($function["type"]) || !isset($function["function"])){
			$function	=	"(ERROR, function invalid: ".Amslib::var_dump($function).")";
			$data		=	array(Amslib::var_dump($stack));
		}else{
			$function	=	"{$function["class"]}{$function["type"]}{$function["function"]}($line)";
			//$data[] = Amslib::var_dump($stack);
		}
	}

	static protected function processLog($type,$vargs)
	{
		if(!in_array($type,array("trace","message","debug","info","warn","error","fatal"))) return false;
		if(count($vargs) == 0) return false;

		if(is_string($vargs[0]) && strlen($vargs[0])){
			$logger = self::getInstance($vargs[0]);

			if($logger) array_shift($vargs);
		}

		if(!$logger){
			$logger = self::getInstance(self::getDefaultLogger());
		}

		$message = self::processMessage($vargs);

		foreach($message as $m){
			$logger->log4php->$type($m);
		}
	}

	static protected function processMessage($list)
	{
		$data		=	array();
		$function	=	false;

		foreach($list as $k=>$a){
			if(is_string($a) && strpos($a,"stack_trace") === 0){
				$command = explode(",",$a);

				$stack = Amslib::getStackTrace(NULL,true);
				$stack = explode("\n",$stack);

				$c = count($command);

				if($c == 2){
					$stack = array_slice($stack,$command[1]);
				}else if($c == 3 && $command[2] > 0){
					$stack = array_slice($stack,$command[1],$command[2]);
				}

				$trace = array("\n");
				foreach($stack as $row){
					$trace[] = "[STACK TRACE] ".str_replace("\n","",Amslib::var_dump($row));
				}
				$data[] = implode("\n",$trace);
			}else if(is_string($a) && strpos($a,"func_offset") === 0){
				$command = explode(",",array_shift($list));

				if(count($command) == 1) $function = $command[0];
			}else{
				if(is_object($a))	$a = array(get_class($a),Amslib::var_dump($a));
				if(is_array($a)) 	$a = Amslib::var_dump($a);
				if(is_bool($a))		$a = $a ? "true" : "false";
				if(is_null($a))		$a = "null";

				$a = trim(preg_replace("/\s+/"," ",$a));

				$data[] = "arg[$k]=> $a";
			}
		}

		$function = self::getLogOrigin();

		return $data;
	}

	public function __construct($name=NULL)
	{
		$name = $this->setName($name);

		$this->log4php = Logger::getLogger($name);
	}

	static public function &getInstance($name=NULL)
	{
		static $instance = array();

		if($name === NULL){
			$keys = array_keys($instance);
			$name = array_shift($keys);
		}

		if(!strlen($name)) $name = self::getDefaultLogger();

		if(!isset($instance[$name])){
			$object	= new self($name);
			$name	= $object->getName();

			//	If the instance already exists, we throw away the newly created one
			//	we can't safely do this before we create the object because it might be invalid and the
			//	test code for this is inside the class, so in this scenario, we just take a small hit
			if(!isset($instance[$name])) $instance[$name] = $object;

			if(!self::getDefaultLogger()){
				self::setDefaultLogger($name);
			}
		}

		return $instance[$name];
	}

	public function setName($name)
	{
		if(!$name || !is_string($name) || !strlen($name)) $name = __CLASS__;

		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function createAppender($type,$name)
	{
		if(!isset($this->appenderMap[$type])) return false;

		$appender = new $this->appenderMap[$type]($name);
		$appender->setThreshold("all");

		$this->appender[] = $appender;

		return count($this->appender)-1;
	}

	public function setDestination($index, $destination,$append=true)
	{
		if(!isset($this->appender[$index])) return false;

		switch(get_class($this->appender[$index])){
			case "LoggerAppenderFile":{
				$exists = $this->createFileLog($destination);

				if($exists){
					$this->appender[$index]->setFile($destination);
					$this->appender[$index]->setAppend($append);

					//	Create a default layout, which you can override later
					$layout = new LoggerLayoutTTCC();
					$layout->activateOptions();

					$this->appender[$index]->setLayout($layout);
				}else{
					//	We do this to make sure the appender cannot be activated or used
					unset($this->appender[$index]);
					//	Ironically, the logging class fails, so we use the error log :)
					Amslib::errorLog(__METHOD__.", Failed to find or create the log file that we needed");
				}
			}break;
		}

		return true;
	}

	public function setThreshold($index,$threshold)
	{
		if(!isset($this->appender[$index])) return false;

		$this->appender[$index]->setThreshold($threshold);

		return true;
	}

	public function addAppender($index)
	{
		if(!isset($this->appender[$index])) return false;

		$this->appender[$index]->activateOptions();
		$this->log4php->addAppender($this->appender[$index]);

		return true;
	}

	static public function setDefaultLogger($name)
	{
		self::$defaultLogger = $name;
	}

	static public function getDefaultLogger()
	{
		return self::$defaultLogger;
	}

	//	NOTE:	this method exists in the online docs, but not in the code,
	//			so I'm shadowing an "info" debug message for now
	static public function trace($vargs)
	{
		$vargs = func_get_args();

		self::processLog("info",$vargs);
	}

	static public function message($vargs)
	{
		$vargs = func_get_args();

		self::processLog("info",$vargs);
	}

	static public function debug($vargs)
	{
		$vargs = func_get_args();

		self::processLog("debug",$vargs);
	}

	static public function info($vargs)
	{
		$vargs = func_get_args();

		self::processLog("info",$vargs);
	}

	static public function warn($vargs)
	{
		$vargs = func_get_args();

		self::processLog("warn",$vargs);
	}

	static public function error($vargs)
	{
		$vargs = func_get_args();

		self::processLog("error",$vargs);
	}

	static public function fatal($vargs)
	{
		$vargs = func_get_args();

		self::processLog("fatal",$vargs);
	}
}
