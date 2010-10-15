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
 * File: Amslib_Router_Language2.php
 * Title: Language component for the Router subsystem
 * Version: 2.0
 * Project: Amslib/Router
 * 
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router_Language2
{
	protected static $router				=	false;
	protected static $enabled				=	false;
	protected static $supportedLanguages	=	array();
	protected static $enabledLanguage		=	false;
	protected static $defaultLanguage		=	false;
	protected static $pushLanguage			=	false;
	
	//	If there was a language passed, make sure it's one of those supported
	protected static function sanitise($language)
	{
		if($language !== NULL && in_array($language,self::$supportedLanguages)){
			return $language;
		}

		return self::$defaultLanguage;
	}
	
	public static function setup($supported,$default)
	{
		self::$supportedLanguages	=	$supported;
		self::$defaultLanguage		=	$default;
	}
	
	public static function add($langCode,$langName,$default=false)
	{
		self::$supportedLanguages[$langCode] = $langName;
		
		if($default) self::$defaultLanguage = $langCode;
	}
	
	public static function setRouter($router)
	{
		self::$router = $router;
	}

	public static function initialise($langName=NULL,$sessionKey="language")
	{
		//	TODO:	Perhaps what we should pass is the language code and not the url name
		//			Where language code = en_GB and name = en
		self::enable();
		 
		//	If the language passed was invalid, set the language requested to the default
		//	FIXME: This doesn't make much sense since setting default in method "add" is different to this
		$langName = self::sanitise($langName);
		
		//	If there is no session language, set it to the language being requested
		if(!isset($_SESSION[$sessionKey])) $_SESSION[$sessionKey] = $langName;

		//	Then enabledLanguage becomes a reference to this new language
		//	Remember, if there is a session language set, you cannot override it through this method
		//	To override this session language, you must provide a routerPath with a language parameter embedded
		self::$enabledLanguage = &$_SESSION[$sessionKey];
	}
		
	public static function enable()
	{
		self::$enabled = true;
	}
	
	public static function disable()
	{
		self::$enabled = false;
	}
	
	/**
	 * method: detect
	 * 
	 * Detect from the routerPath a language embedded as the first url part, if there is one, you need
	 * to override the session language so the page will render in the correct language, this is also
	 * used in the construction of urls to show in the pages
	 */
	public static function detect(&$routerPath)
	{
		$parts = explode("/",trim($routerPath));

		//	strip the first and last array elements, if they are empty strings 
		//	(this happens when the url starts and ends with /)
		if(strlen(current($parts)) == 0) array_shift($parts);
		if(strlen(end($parts)) == 0) array_pop($parts);

		if(count($parts) > 0){
			$p0 = reset($parts);
			$language = self::sanitise($p0);
			if($language == $p0) self::$enabledLanguage = array_shift($parts);	
		}
		
		$routerPath = "/".implode("/",$parts);
		
		return $routerPath;
	}
	
	/**
	 * method: change
	 * 
	 * Create a url for the current route that will change the language into the other one
	 * this is completely application specific in how this happens, per application, requires perhaps
	 * a different way to change languages, as long as the url scheme for languages remains the same
	 * 
	 * FIXME:	Seems to be hard coded into dealing with only spanish and english languages
	 * 			(	This FIXME seems out of date now, I can't find anything 
	 * 				that is spanish/english only here)
	 * 
	 * TODO:	Perhaps this is better off in the router than here? we are making
	 * 			the language object depend on the router object
	 */
	public static function change($langName)
	{
		self::push();
		self::set($langName);
		
		$url = self::$router->getRoute();
		
		self::pop();
		
		return $url;
	}
	
	public static function set($langName)
	{
		self::$enabledLanguage = self::sanitise($langName);
	}

	public static function get($url=false)
	{
		$lang = "";
		
		if(self::$enabled){
			if($url) $lang = "/";
			$lang = $lang.self::$enabledLanguage;
		}
		
		return $lang;
	}
	
	public static function getCode()
	{
		return array_search(self::$enabledLanguage,self::$supportedLanguages);	
	}
	
	public static function push()
	{
		self::$pushLanguage = self::$enabledLanguage;
	}
	
	public static function pop()
	{
		self::$enabledLanguage = self::$pushLanguage;
	}
}