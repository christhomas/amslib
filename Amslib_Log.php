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
	protected $logger;

	protected $appender;

	protected $appenderMap = array(
		"file"	=>	"LoggerAppenderFile"
	);

	protected function createFileLog(&$filename)
	{
		$dirname	= dirname($filename);
		$basename	= Amslib::slugify2(basename($filename),"-",".");
		$filename = "$dirname/$basename";

		if(file_exists($filename)) return true;

		if(!is_dir(dirname($filename)) && !mkdir(dirname($filename),0777,true)){
			return false;
		}

		if($t=touch($filename) && $c=chmod($filename,0777)){
			return true;
		}

		Amslib::errorLog(__METHOD__.", file failed to create, or modify it's permissions","touch=".intval($t),"chmod=".intval($c));
		return false;
	}

	public function __construct($name=NULL)
	{
		$name = $this->setName($name);

		$this->logger = Logger::getLogger($name);
		$this->addMixin($this->logger);
	}

	static public function &getInstance($name)
	{
		static $instance = array();

		if(!isset($instance[$name])){
			$class	= new self($name);
			$name	= $class->getName();

			//	If the instance already exists, we throw away the newly created one
			//	we can't safely do this before we create the object because it might be invalid and the
			//	test code for this is inside the class, so in this scenario, we just take a small hit
			if(!isset($instance[$name])) $instance[$name] = $class;
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
		$this->logger->addAppender($this->appender[$index]);

		return true;
	}

	/**	This method is in the docs, but not in the amslib installed version
	static public function logTrace($logger,$message,$throwable=NULL)
	{
		$logger = self::getInstance($logger);
		$logger->trace($message,$throwable);
	}*/

	static public function logMessage($logger,$message,$throwable=NULL)
	{
		self::logInfo($logger,$message,$throwable);
	}

	static public function logDebug($logger,$message,$throwable=NULL)
	{
		$logger = self::getInstance($logger);
		$logger->debug($message,$throwable);
		//testing what happens when you edit git files on android tablet
	}

	static public function logInfo($logger,$message,$throwable=NULL)
	{
		$logger = self::getInstance($logger);
		$logger->info($message,$throwable);
	}

	static public function logWarn($logger,$message,$throwable=NULL)
	{
		$logger = self::getInstance($logger);
		$logger->warn($message,$throwable);
	}

	static public function logError($logger,$message,$throwable=NULL)
	{
		$logger = self::getInstance($logger);
		$logger->error($message,$throwable);
	}

	static public function logFatal($logger,$message,$throwable=NULL)
	{
		$logger = self::getInstance($logger);
		$logger->fatal($message,$throwable);
	}
}