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
 *	group:	Core
 *
 *	file:	Amslib_Array2.php
 *
 *	description:  todo, write description
 *
 * 	todo: write documentation
 *
 */
class Amslib_Array2
{
	static public function valid($array=NULL)
	{
		//	Invalid values return an empty array
		if(empty($array) || !$array || !is_array($array) || is_null($array)) return array();
		//	cast objects to arrays
		if(is_object($array)) $array = (array)$a;
		//	return the original value
		return $array;
	}

	static public function min($array,$key,$returnKey=NULL)
	{
		$min = NULL;

		foreach(self::valid($array) as $item){
			if($min === NULL) $min = $item;

			if($item[$key] < $min[$key]) $min = $item;
		}

		return $returnKey !== NULL && isset($min[$returnKey]) ? $min[$returnKey] : $min;
	}

	static public function max($array,$key,$returnKey=NULL)
	{
		$max = NULL;

		foreach(self::valid($array) as $item){
			if($max === NULL) $max = $item;

			if($item[$key] > $max[$key]) $max = $item;
		}

		return $returnKey !== NULL && isset($max[$returnKey]) ? $max[$returnKey] : $max;
	}

	static public function sort($array,$index)
	{
		if(count($array) < 2) return $array;

		$left = $right = array();

		reset($array);
		$pivot_key = key($array);
		$pivot = array_shift($array);

		foreach($array as $k => $v) {
			if($v[$index] < $pivot[$index]){
				$left[$k] = $v;
			}else{
				$right[$k] = $v;
			}
		}

		return array_merge(self::sort($left,$index), array($pivot_key => $pivot), self::sort($right,$index));
	}
	
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