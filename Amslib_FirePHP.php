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
 *	description:  Wrapper around FirePHP
 *
 * 	todo: write documentation
 *
 */
class Amslib_FirePHP extends FirePHP
{
	public function __construct()
	{
		parent::__construct();
	}

	public static function backtrace($levels=false)
	{
		$e = new Exception();

		$trace = (is_numeric($levels) && $levels > 0)
			? array_slice($e->getTrace(),0,(int)$levels)
			: $e->getTrace();

		self::output("backtrace",$trace);
	}

	public static function output($name,$data){
		$a=func_get_args();

		try{
			$n = array_shift($a);
			self::$instance->log(count($a) > 1 ? $a : current($a),$n);
		}catch(Exception $e){
			print("Amslib_FirePHP::output(), exception occured, output has already started? backtrace = ".Amslib::var_dump(debug_backtrace(),true));
		}
	}

	public static function init()
	{
		parent::init();

		$options = array(	'maxObjectDepth'		=>	5,
							'maxArrayDepth'			=>	10,
							'maxDepth'				=>	10,
							'useNativeJsonEncode'	=>	true,
							'includeLineNumbers'	=>	true);

		self::$instance->setOptions($options);
	}
}

Amslib_FirePHP::init();
