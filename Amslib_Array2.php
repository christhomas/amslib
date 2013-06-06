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
 *	description:  todo, write description
 *
 * 	todo: write documentation
 *
 */
class Amslib_Array2
{
	/**
	 * 	method:	valid
	 *
	 * 	todo: write documentation
	 * 
	 * 	notes:
	 * 		-	if you return nothing from a method, then catch that into a parameter
	 * 			pass that parameter into this function, you'll freeze the browser
	 * 			well done francisco for catching this :)
	 * 
	 * 		-	Extended this method to support passing in a variable and then asking 
	 * 			for a key instead of passing it directly.  This is useful when you have 
	 * 			a variable but not sure whether the key exists or not, if it doesnt, then
	 * 			it'll cause an error to go into the log, but testing it by passing the 
	 * 			variable and the key separately means we can handle the situation more gracefully
	 */
	static public function valid($array=NULL,$key=NULL)
	{
		if($key !== NULL && is_string($key)) $array = isset($array[$key]) ? $array[$key] : array();
		//	Invalid values return an empty array
		if(empty($array) || !$array || !is_array($array) || is_null($array)) return array();
		//	cast objects to arrays
		if(is_object($array)) $array = (array)$a;
		//	return the original value
		return $array;
	}

	/**
	 * 	method:	min
	 *
	 * 	todo: write documentation
	 */
	static public function min($array,$key,$returnKey=NULL)
	{
		$min = NULL;

		foreach(self::valid($array) as $item){
			if($min === NULL) $min = $item;

			if($item[$key] < $min[$key]) $min = $item;
		}

		return $returnKey !== NULL && isset($min[$returnKey]) ? $min[$returnKey] : $min;
	}

	/**
	 * 	method:	max
	 *
	 * 	todo: write documentation
	 */
	static public function max($array,$key,$returnKey=NULL)
	{
		$max = NULL;

		foreach(self::valid($array) as $item){
			if($max === NULL) $max = $item;

			if($item[$key] > $max[$key]) $max = $item;
		}

		return $returnKey !== NULL && isset($max[$returnKey]) ? $max[$returnKey] : $max;
	}

	/**
	 * 	method:	sort
	 *
	 * 	todo: write documentation
	 */
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
	
	/**
	 * 	method:	pluck
	 *
	 * 	todo: write documentation
	 */
	static public function pluck($array,$key)
	{
		if(!is_array($array)) return array();
	
		$values = array();
	
		if(self::isMulti($array)){
			foreach(self::valid($array) as $item){
				if(isset($item[$key])) $values[] = $item[$key];
			}
		}else{
			if(isset($array[$key])) $values[] = $array[$key];
		}
	
		return $values;
	}
	
	/**
	 * 	method:	unique
	 *
	 * 	todo: write documentation
	 */
	static public function unique($array,$field)
	{
		if(!self::isMulti($array)) return false;
	
		$v = $unique = array();
	
		foreach(self::valid($array) as $k=>$a){
			if(isset($a[$field]) && !in_array($a[$field],$v)){
				$v[] = $a[$field];
				$unique[$k] = $a;
			}
		}
	
		return $unique;
	}
	
	/**
	 * 	method:	removeValue
	 *
	 * 	todo: write documentation
	 */
	static public function removeValue(array $array,$value,$strict=false)
	{
		return array_diff_key($array, array_flip(array_keys($array, $value, $strict)));
	}
	
	/**
	 * method: removeKeys
	 *
	 * This method is a wrapper around unset to make it easier and less verbose in your code to remove multiple elements
	 */
	static public function removeKeys($array,$keys)
	{
		if(is_string($keys)) $keys = array($keys);
	
		$array	=	self::valid($array);
		$keys	=	self::valid($keys);
	
		foreach($keys as $k) unset($array[$k]);
	
		return $array;
	}
	
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
	
	/**
	 * 	method:	reindexByKey
	 *
	 * 	todo: write documentation
	 */
	static public function reindexByKey($array,$key)
	{
		$array = self::valid($array);
	
		if(!is_string($key)) return $array;
	
		$copy = array();
	
		foreach($array as $item) if(isset($item[$key])){
			$copy[$item[$key]] = $item;
		}
	
		return $copy;
	}
	
	/**
	 * 	method:	missingKeys
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		-	This method allows me to batch fix missing keys in arrays in broken code quickly and easily.
	 */
	static public function missingKeys($array,$key,$value=NULL)
	{
		if(is_string($key)) $key = array($key);
	
		foreach($key as $k) if(!isset($array[$k])) $array[$k] = $value;
	
		return $array;
	}
	
	/**
	 * 	method:	hasKeys
	 *
	 *	A function to search an array for keys which exist, or keys which are missing and return true or false depending
	 *
	 * 	parameters:
	 * 		$array		-	The array to search for the present or missing keys
	 * 		$present	-	A string, or array of strings to search for in the array
	 * 		$missing	-	A string, or array of strings to search that are missing from the array
	 *
	 * 	returns:
	 * 		NULL, Boolean true or false, depending on the result of various actions
	 * 		-	If the array is not an array, will return NULL, this is a different error than false,
	 * 			used to know whether the array was invalid without further effort
	 * 		-	If you find a key from the $present variable is missing, will return false
	 * 		-	If you find a key from the $missing various actually exists, will return false
	 * 		-	If all the $present keys exist and none of the $missing keys exist and the
	 * 			array was a valid array, will return true
	 *
	 * 	notes:
	 * 		-	$missing is not required, if you do not pass it, it will resolve to NULL and nothing will be searched
	 * 		-	$present and $missing can be a single string, or an array of strings, the function will internally deal with both
	 * 		-	perhaps I can investigate the use of array intersections instead of manually looping through and detecting
	 */
	static public function hasKeys($array,$present,$missing=NULL)
	{
		if(!is_array($array)) return NULL;
	
		if(is_string($present) && strlen($present))	$present = array($present);
		if(is_string($missing) && strlen($missing))	$missing = array($missing);
	
		foreach(self::valid($present) as $k){
			if(!isset($array[$k])) return false;
		}
	
		foreach(self::valid($missing) as $k){
			if(isset($array[$k])) return false;
		}
	
		return true;
	}
	
	/**
	 * 	method:	isMulti
	 *
	 * 	todo: write documentation
	 */
	static public function isMulti($array)
	{
		return count($array)!==count($array, COUNT_RECURSIVE);
	}
	
	/**
	 * 	method:	diff
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		-	I got this function from here: http://stackoverflow.com/questions/8917039/php-checking-difference-between-two-multidim-arrays
	 * 		-	06/02/2013=> I modified this with the $strict parameter to let me check types as well as values
	 * 		-	this method will tell you the missing keys and different values in the second array
	 */
	static public function diff($array1, $array2, $strict=false)
	{
		$return = array();
	
		if(!is_array($array1) || !is_array($array2)) return $return;
	
		foreach($array1 as $key => $value)
		{
			if(array_key_exists($key, $array2))
			{
				if (is_array($value))
				{
					$diff = self::diff($value, $array2[$key],$strict);
						
					if(count($diff)){
						$return[$key] = $diff;
					}
				}else{
					if((!$strict && $value != $array2[$key]) || ($strict && $value !== $array2[$key])){
						$return[$key] = $value;
					}
				}
			}else{
				$return[$key] = $value;
			}
		}
	
		return $return;
	}
	
	/**
	 * 	method:	diffBoth
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		-	this will give both sets of changes from both arrays compared against each other instead of in one direction only
	 */
	static public function diffBoth($array1,$array2,$strict=false)
	{
		return array(
				"src"	=>	$a=self::diff($array1,$array2,$strict),
				"dst"	=>	$b=self::diff($array2,$array1,$strict),
				"equal"	=>	count($a) == 0 && count($b) == 0
		);
	}
}