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
 * 	class:	Amslib_Router_Language
 *
 *	group:	router
 *
 *	file:	Amslib_Router_Language.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Router_Language
{
	protected static $router		=	false;
	protected static $current		=	false;
	protected static $supported		=	array();
	protected static $enabled		=	false;
	protected static $default		=	false;
	protected static $push			=	false;
	//	FIXME: this won't work with the translator system, so we need to combine these together
	protected static $sessionKey	=	"Amslib_Router_Language";
	
	//	If there was a language passed, make sure it's one of those supported
	protected static function sanitise($langName=NULL,$langCode=NULL)
	{
		//	Detect by language name (en, de, fr, es, pt, cat, etc, etc)
		if($langName && in_array($langName,self::$supported)){
			return $langName;
		}
		
		//	Sanitise by language Code (en_GB, de_DE, fr_FR, es_ES, pt_PT, es_CA, etc, etc)
		if($langCode && isset(self::$supported[$langCode])){
			return self::$supported[$langCode];	
		}
		
		//	Wasn't found, so return the default language
		return self::$default;
	}
	
	public static function setSessionKey($name)
	{
		self::$sessionKey = $name;
	}
	
	public static function add($langCode,$langName,$default=false)
	{
		self::$supported[$langCode] = $langName;
		
		/*	NOTE:	Set the default language to the first language added
					OR if you passed in a language (which will override 
					anything you set automatically)
		*/
		if(!self::$default || $default) self::$default = $langCode;
	}
	
	/**
	 * method: extract
	 * 
	 * Extract from the $path a language embedded as the first part of the url, if there is one, you need
	 * to override the session language so the page will render in the correct language, this is also
	 * used in the construction of urls to show in the pages
	 */
	public static function extract($path)
	{
		//	If the path is just /, then obviously it has nothing, just return it
		if($path == "/") return $path;
		
		$parts = explode("/",trim($path,"/ "));
		
		if(count($parts)){
			$first	=	array_shift($parts);
			$lang	=	self::sanitise($first);
			if($lang == $first){
				self::set($lang);
				
				//	Make sure there are only single slashes and make sure the path starts and ends with a slash
				return Amslib_File::reduceSlashes("/".implode("/",$parts)."/");
			}
		}
		
		return $path;
	}
	
	public static function execute($langName=NULL)
	{
		//	NOTE: You should only enable the system if there are languages added to it.
		
		//	If there was no language passed, use the default language
		if($langName == NULL && self::$default) $langName = self::$supported[self::$default];
		
		//	Enable the language system
		self::enable();
		
		//	Enable the required language
		$langName = self::sanitise($langName);
		
		//	Create a session parameter to store the current language in
		if(!isset($_SESSION[self::$sessionKey])){
			$_SESSION[self::$sessionKey] = $langName;	
		}
		
		//	You can ONLY set the session parameter, if it doesnt already exist
		//	If it already exists, the only way to change the current language, is to call setLanguage
		//	Or pass a language through the url which will/should be picked up through the router
		self::$current = &$_SESSION[self::$sessionKey];
	}
	
	public static function enable()
	{
		self::$enabled = true;
	}
	
	public static function disable()
	{
		self::$enabled = false;
	}
	
	public static function set($langName)
	{
		self::$current = self::sanitise($langName);
	}
	
	public static function setCode($langCode)
	{
		self::$current = self::sanitise(NULL,$langCode);
	}

	public static function getName()
	{
		return self::$current;
	}
	
	public static function getCode()
	{
		return array_search(self::$current,self::$supported);	
	}
	
	public static function push()
	{
		self::$push = self::$current;
	}
	
	public static function pop()
	{
		self::$current = self::$push;
	}
}