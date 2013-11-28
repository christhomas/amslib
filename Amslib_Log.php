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
class Amslib_Log
{
	protected $logger;

	protected $appender;

	protected $appenderMap = array(
		"file"	=>	"LoggerAppenderFile"
	);

	public function __construct($name=NULL)
	{
		$name = $this->setName($name);

		$this->logger = Logger::getLogger($name);
	}

	public function &getInstance($name)
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

		$appender = new $this->appenderMap[$type];
		$appender->setThreshold("all");

		$this->appender[] = $appender;

		return count($this->appender);
	}

	public function setDestination($index, $destination,$append=true)
	{
		if(!isset($this->appender[$index])) return false;

		switch(get_class($this->appender[$index])){
			case "LoggerAppenderFile":{
				$exists = file_exists($destination);

				if(!$exists){
					$exists = $this->createFileLog($destination);
				}

				if($exists){
					$this->appender[$index]->setFile($destination);
					$this->appender[$index]->setAppend($append);
				}else{
					//	Ironically, the logging class fails, so we use the error log :)
					Amslib::errorLog(__METHOD__.", Failed to find or create the log file that we needed");
				}
			}break;
		}

		return true;
	}

	protected function createFileLog($destination)
	{
		if(file_exists($destination)) return true;

		return touch($destination);
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
		$this->logger->appAppender($this->appender[$index]);

		return true;
	}
}