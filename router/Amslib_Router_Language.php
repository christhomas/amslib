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
 * File: Amslib_Router_Language.php
 * Title: Language component for the Router subsystem
 * Version: 1.0
 * Project: Amslib/Router
 * 
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router_Language
{
	protected static $enabled				=	false;
	protected static $defaultLanguage		=	"en";
	protected static $supportedLanguages	=	array("en","es","fr","de","it","pt");
	protected static $enabledLanguage;
	
			//	If there was a language passed, make sure it's one of those supported
	protected static function sanitise($language)
	{
		if($language !== NULL && in_array($language,self::$supportedLanguages)){
			return $language;
		}

		return self::$defaultLanguage;
	}
		
	public static function enable()
	{
		self::$enabled = true;
	}
	
	public static function disable()
	{
		self::$enabled = false;
	}

	public static function initialise($language=NULL,$sessionKey="language")
	{
		self::enable();
		 
		//	If the language passed was invalid, set the language requested to the default
		$language = self::sanitise($language);
		
		//	If there is no session language, set it to the language being requested
		if(!isset($_SESSION[$sessionKey])) $_SESSION[$sessionKey] = $language;

		//	Then enabledLanguage becomes a reference to this new language
		//	Remember, if there is a session language set, you cannot override it through this method
		//	To override this session language, you must provide a routerPath with a language parameter embedded
		self::$enabledLanguage = &$_SESSION[$sessionKey];
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
	 * FIXME: Seems to be hard coded into dealing with only spanish and english languages
	 */
	public static function change()
	{
		if(self::$enabled){
			//	Switch language
			$lang = (self::$enabledLanguage == "es") ? "/en" : "/es";
			self::set($lang);
		}
		
		// return the current page, but with the language swapped
		$Router = Router::getInstance();
		return $Router->getCurrentRoute();
	}
	
	public static function set($language)
	{
		self::$enabledLanguage = self::sanitise($language);
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
}