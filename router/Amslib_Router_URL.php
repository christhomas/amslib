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
	/**
	 * 	method:	getFullURL
	 *
	 * 	todo: write documentation
	 */
	static public function getFullURL()
	{
		return Amslib_Website::relative(Amslib_Router::getPath());
	}

	/**
	 * 	method:	getURL
	 *
	 *	A method to obtain the url via it's name, group, language and domain
	 *
	 *	params
	 *		$name	-	The name of the route to request
	 *		$group	-	The group this route belongs to, allows selecting a route with a similar
	 *					name, specifying the group directly
	 *		$lang	-	The language for the url to return
	 *		$domain	-	The domain of the route, this might be because a router from another URI was
	 *					imported, meaning a router contains multiple routers, each segregated by their domain
	 *
	 *	notes:
	 *		-	The $domain parameter is not really the domain, it's the "location", it CAN BE
	 *			the domain, but it's not explicitly ONLY THIS, it can be any URI
	 */
	static public function getURL($name=NULL,$group=NULL,$lang="default",$domain=NULL)
	{
		return Amslib_Router::getURL($name,$group,$lang,$domain);
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

	/**
	 * 	method:	getService
	 *
	 * 	todo: write documentation
	 */
	static public function getService($route,$group=NULL,$domain=NULL)
	{
		return Amslib_Router::getService($route,$group,$domain=NULL);
	}

	/**
	 * 	method:	getServiceURL
	 *
	 * 	todo: write documentation
	 */
	static public function getServiceURL($route,$group=NULL,$lang="default",$domain=NULL)
	{
		return Amslib_Router::getServiceURL($route,$group,$lang,$domain);
	}

	/**
	 * 	method:	redirectToRoute
	 *
	 * 	todo: write documentation
	 */
	static public function redirectToRoute($route,$permenant=0)
	{
		return self::redirect(self::getURL($route),$permenant);
	}

	/**
	 * 	method:	redirect
	 *
	 * 	todo: write documentation
	 */
	static public function redirect($url,$permenant=0)
	{
		$type = $permenant ? 301 : 0;

		return Amslib_Website::redirect($url,true,$type);
	}

	/**
	 * 	method:	getRouteParam
	 *
	 * 	todo: write documentation
	 */
	static public function getRouteParam($name=NULL,$default="")
	{
		return Amslib_Router::getRouteParam($name,$default);
	}

	/**
	 * 	method:	getURLParam
	 *
	 * 	todo: write documentation
	 */
	static public function getURLParam($index=NULL,$default="")
	{
		return Amslib_Router::getURLParam($index,$default);
	}

	/**
	 * 	method:	decodeURLPairs
	 *
	 * 	todo: write documentation
	 */
	static public function decodeURLPairs($offset=0)
	{
		return Amslib_Router::decodeURLPairs($offset);
	}

	/**
	 * 	method:	externalURL
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		-	inside here we do a cheap detection for the "://" string meaning most likely
	 * 			the url has a protocol/http host so then it's already an external url, this
	 * 			might not be safe in all circumstances but without more information, I'm
	 * 			going to leave it like this and hope it's ok
	 */
	static public function externalURL($url="")
	{
		if(strpos($url,"://") !== false) return $url;

		$protocol = isset($_SERVER['HTTPS']) ? "https" : "http";

		return "{$protocol}://{$_SERVER['HTTP_HOST']}{$url}";
	}

	//	DEPRECATED METHODS, DO NOT USE THEM
	/**
	 * 	method:	getDomain
	 *
	 * 	todo: write documentation
	 */
	static public function getDomain($url="")
	{
		return self::externalURL($url);
	}
}

/* NAUGHTY!! Two classes in one file, but in this case we're breaking the rules cause:
 * AMS_URL is a "shorthand" way to use Amslib_Router_URL
*/
class AMS_URL extends Amslib_Router_URL{}