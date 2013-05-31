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
 * 	class:	Amslib_Router_URL
 *
 *	group:	router
 *
 *	file:	Amslib_Router_URL.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Router_URL
{
	static public function getFullURL()
	{
		return Amslib_Router::getPath();
	}
	
	static public function getURL($route,$group=NULL)
	{
		if(is_array($route)){
			$r = array_shift($route);
			$p = implode("/",array_filter($route));
		}else{
			$r = $route;
			$p = "";
		}
	
		return is_string($r) ? Amslib_Router::getURL($r).$p : "";
	}

	/*	NOTE:	getURL was deactivated because it's defining URL policy for a website to 
	 * 			automatically include the language inside a URL, thats the applications job
	 * 
	 * static public function getURL($route=NULL,$group=NULL)
	{
		$lang = Amslib_Plugin_Application::getLanguage("website");

		$r = Amslib_Router::getURL($route,$group,$lang);

		if($r == "/" && $lang == "es_ES") $r = "/es/";

		return $r;
	}*/
	
	static public function getService($route,$group=NULL)
	{
		return Amslib_Router::getService($route,$group);
	}

	static public function getServiceURL($route,$group=NULL)
	{
		return Amslib_Router::getServiceURL($route,$group);
	}
	
	static public function redirectToRoute($route,$permenant=0)
	{
		return self::redirect(self::getURL($route),$permenant);
	}

	static public function redirect($url,$permenant=0)
	{
		$type = $permenant ? 301 : 0;

		return Amslib_Website::redirect($url,true,$type);
	}

	static public function getRouteParam($name=NULL,$default="")
	{
		return Amslib_Router::getRouteParam($name,$default);
	}

	static public function getURLParam($index=NULL,$default="")
	{
		return Amslib_Router::getURLParam($index,$default);
	}
	
	static public function decodeURLPairs($offset=0)
	{
		return Amslib_Router::decodeURLPairs($offset);
	}
	
	static public function externalURL($url="")
	{
		return (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST'].$url;
	}
	
	//	DEPRECATED METHODS, DO NOT USE THEM
	static public function getDomain($url=""){
		return self::externalURL($url);	
	}
}

/* NAUGHTY!! Two classes in one file, but in this case we're breaking the rules cause:
 * AMS_URL is a "shorthand" way to use Amslib_Router_URL
*/
class AMS_URL extends Amslib_Router_URL{}