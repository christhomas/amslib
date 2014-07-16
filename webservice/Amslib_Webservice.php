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

class Amslib_Webservice
{
	static protected $debug = false;

	protected $router_name;

	public function __construct()
	{
		self::setDebugState(false);
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}

	static public function setDebugState($state)
	{
		self::$debug = !!$state;
	}

	static public function getDebugState()
	{
		return self::$debug;
	}

	public function create($url,$params)
	{
		$request = new Amslib_Webservice_Request($this);
		$request->setURL($url);
		$request->setParameters($params);

		return $request;
	}

	public function callFromRouter($name,$params=array())
	{
		if(class_exists("Amslib_Router_URL")){
			$this->router_name = name;

			$url = Amslib_Router_URL::getServiceURL($name);

			return $this->call($url,$params);
		}

		return false;
	}

	public function call($url,$params=array())
	{
		$request = $this->create($url,$params);

		return $request->execute();
	}
}