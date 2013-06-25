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
 * 	class:	Amslib_Array2
 *
 *	group:	core
 *
 *	file:	Amslib_Array2.php
 *
 *	description:
 *		a class to explore new methods which duplicate existing methods in the original Amslib_Array object
 *		without breaking that code and allowing new ideas
 *
 */
class Amslib_Array2
{	
	/**
	 * 	method:	filterKey
	 *
	 * 	todo: write documentation
	 */
	static public function filterKey($array,$key,$returnFiltered=false)
	{
		$filter = array();

		foreach(self::valid($array) as $k=>$v){
			$found = false;

			if($k == $key){
				$found = true;
			}

			if($found){
				$filter[$k] = $v;
				unset($array[$k]);
			}
		}

		return $returnFiltered ? $filter : $array;
	}
}