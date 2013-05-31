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
 * 	class:	Amslib_Form_Profile
 *
 *	group:	Core
 *
 *	file:	Amslib_Form_Profile.php
 *
 *	description: todo write description
 *
 * 	todo: write documentation
 *
 */
class Amslib_Form_Profile
{
	const SUCCESS	=	"form/success";
	const FAILURE	=	"form/failure";
	const AJAX		=	"form/ajax";
	
	const KEY		=	"amslib/form_profile";
	
	//	Here we cache all the profiles so they are only called once
	static protected $profiles	=	false;
	
	//	Here we cache all the data associated with a profile
	static protected $data		=	array();
	
	//	Here we can delegate how a profile is stored to another function/method who can create it on the fly
	static protected $delegate	=	false;
	
	static public function initialise()
	{
		if(!isset($_SESSION[self::KEY])) $_SESSION[self::KEY] = array();
		
		self::$profiles = &$_SESSION[self::KEY];
	}
	
	static public function setDelegate($method,$object=NULL)
	{
		if($object && $method && method_exists($object,$method)){
			self::$delegate = array($object,$method);
		}else if($method && function_exists($method)){
			self::$delegate = $method;
		}
	}
	
	//	Assign a new profile
	static public function setProfile($name,$data)
	{
		if(!is_string($name)) return false;
		
		self::$profiles[$name] = $data;
		
		self::resetProfile($name);
		
		return true;
	}
	
	//	Get a profile from the cache or the delegate
	static public function getProfile($name)
	{
		if(!is_string($name)) return array();
		
		if(isset(self::$profiles[$name])) return self::$profiles[$name];

		$profile = (self::$delegate) ? call_user_func(self::$delegate,$name) : array();
		
		if(!empty($profile)) self::set($name,$profile);
		
		return $profile;
	}
	
	//	Delete a profile
	static public function deleteProfile($name)
	{
		if(!is_string($name)) return;
		
		unset(self::$profiles[$name]);
	}
	
	static public function resetProfile($name)
	{
		if(!is_string($name)) return;
		
		$d = self::$profiles[$name]["data"];
		
		self::$profiles[$name]["data"] = array();
		
		return $d;
	}
	
	static public function getSuccessURL($name,$default="")
	{
		if(!is_string($name)) return;
		
		return isset(self::$profiles[$name][self::SUCCESS]) 
			?	self::$profiles[$name][self::SUCCESS]
			:	$default;
	}
	
	static public function getFailureURL($name,$default="")
	{
		if(!is_string($name)) return;
		
		return isset(self::$profiles[$name][self::FAILURE]) 
			?	self::$profiles[$name][self::FAILURE] 
			:	$default;
	}
	
	static public function getAjaxState($name)
	{
		return is_string($name) && isset(self::$profiles[$name][self::AJAX])
			?	self::$profiles[$name][self::AJAX] ? true : false
			:	false;
	}
	
	//	Store a piece of data inside the profile, ready to pickup the next time
	static public function setData($name,$key,$value)
	{
		if(is_string($name) && isset(self::$profiles[$name])){
			self::$profiles[$name]["data"][$key] = $vale;
		}
	}
	
	static public function getData($name)
	{ 
		if(!is_string($name)) return array();

		if(isset(self::$data[$name])) return self::$data[$name];
		
		if(!isset(self::$profiles[$name])) return array();
		
		self::$data[$name] = self::resetProfile($name);
		
		return self::$data[$name];
	}
	
	//	Retrieve a piece of data that was stored inside the profile
	static public function getDataElement($name,$key,$default=false)
	{
		if(is_string($name) && isset(self::$profiles[$name][$key])){
			return self::$profiles[$name]["data"][$key];
		}	
		
		return $default;
	}
}

//	Initialise and setup the object with it's basic data
Amslib_Form_Profile::initialise();