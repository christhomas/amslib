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
 * File: Amslib_Router_URL.php
 * Title: Base resource<->URL convertor for the router system
 * Version: 1.0
 * Project: Amslib/Router
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_Router_URL
{
	protected static $router;

	protected static function getRoute($route,$option="default")
	{
		return self::$router->getRoute($route,$option);
	}

	public static function setRouter($router)
	{
		self::$router = $router;
	}

	public static function AddHost($url)
	{
		return "http://{$_SERVER["SERVER_NAME"]}$url";
	}

	public static function Home()
	{
		return self::getRoute("Home");
	}

	public static function Error_404()
	{
		return self::getRoute("ERROR","404");
	}
}