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
 * 	class:	Amslib_Array
 *
 *	group:	core
 *
 *	file:	Amslib_Array.php
 *
 *	description:  todo, write description
 *
 * 	todo: write documentation
 *
 * 	notes:
 * 		-	there are sometimes better versions of methods here in Amslib_Array2 object which is being used
 * 			to explore better ideas, consider those before using versions in this object
 *
 */
class Amslib_Array
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

		if(!isset($pivot[$index])){
			Amslib::errorLog("ERROR, PIVOT INDEX DOES NOT EXIST","stack_trace");
		}

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
		if(!is_array($array))	return array();
		if(!is_array($key))		$key = array($key);

		$values = array();

		if(self::isMulti($array)){
			foreach(self::valid($array) as $item){
				$v = array();

				foreach($key as $k){
					if(isset($item[$k])) $v[$k] = $item[$k];
				}

				$values[] = count($v) == 1 ? array_shift($v) : $v;
			}
		}else{
			$v = array();

			foreach($key as $k){
				if(isset($array[$k])) $v[$k] = $array[$k];
			}

			$values[] = count($v) == 1 ? array_shift($v) : $v;

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
	 * 	method:	findKey
	 *
	 * 	look into an array and find a key which has a partial match for the key parameter
	 *
	 * 	parameters:
	 * 		$source - The source array to scan
	 * 		$key - The partial array key to look for
	 *
	 * 	returns:
	 * 		Boolean false if it was not found, or the first match key that was found
	 *
	 * 	notes:
	 * 		-	we should extend this with the ability to find an exact or partial key
	 */
	static public function findKey($source,$key)
	{
		if(is_array($source)) foreach($source as $k=>$ignore){
			if(strpos($k,$key) !== false) return $k;
		}

		return false;
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

	/**
	 * 	method:	filterType
	 *
	 * 	todo: write documentation
	 *
	 *	notes:
	 *		I dont think "filterType" is a good function name for this, when
	 *		it takes a callback, perhaps filterCallback instead?
	 */
	static public function filterType($array,$callback)
	{
		return function_exists($callback) ? array_filter(self::valid($array),$callback) : $array;
	}

	/**
	 * 	method:	filterKey
	 *
	 * 	todo: write documentation
	 */
	static public function filterKey($array,$filter,$similar=false)
	{
		$array = self::valid($array);

		if($similar === false){
			if(self::isMulti($array)){
				//	NOTE: perhaps I should recurse here?
				//	EXAMPLE: foreach($array as &$a) $a = self::FilterKey($a,$filter,$similar);
				//	NOTE: can we use array_map here??
				//	NOTE: I don't think array_map will work because this method requires parameters that it can't forward
				foreach($array as &$a){
					if(is_array($filter)){
						$a = array_intersect_key($a, array_flip($filter));
					}else if(isset($a[$filter])){
						$a = $a[$filter];
					}else{
						$a = NULL;
					}
				}
			}else{
				$array = array_intersect_key($array, array_flip($filter));
			}

			return $array;
		}

		return self::filter($array,NULL,$filter,true,true);
	}

	/**
	 * 	method:	filter
	 *
	 * 	todo: write documentation
	 */
	static public function filter($array,$key,$value,$returnFiltered=false,$similar=false)
	{
		$filter = array();

		foreach(self::valid($array) as $k=>$v)
		{
			$found = false;

			//	TODO: I'm sure that there are more situations I could take into account here
			//	TODO: I should document exactly what this method does, because right now I can't remember

			//	FIXME: there is a bug here if the key doesnt exist in the array
			//	NOTE:	there is a side effect of the bug, if the key doesnt exist, it'll return NULL,
			//			passing $value as true will mean it'll compare either the existing key against
			//			true or null against true, so it in effect is a facinatingly cool way to filter
			//			by key and only return arrays which have a particular key.  This whilst being very nice
			//			is still a bug, I either need to codify this to make it official, or fix the bug
			$search = $key ? $v[$key] : $v;

			if($similar == true && strpos($search,$value) !== false) $found = true;
			else if($search == $value) $found = true;
			else if(is_array($value)){
				if($similar == true){
					foreach($value as $subvalue){
						if(strpos($search,$subvalue) !== false){
							//	NOTE: here I am adjusting the key to reply the key=>value meaning the subvalue=>value
							//	NOTE: but I am not sure whether I want to do this 100% of the time, for now, it's a good choice
							$k = $subvalue;
							$found = true;
							break;
						}
					}
				}else if(in_array($search,$value)){
					$found = true;
				}
			}

			if($found){
				$filter[$k] = $v;
				unset($array[$k]);
			}
		}

		return $returnFiltered ? $filter : $array;
	}

	//	TODO: this method is a little open to abuse and in some situations wouldn't do the right thing
	//	TODO: explain in words what this does and how it should work
	/**
	 * 	method:	countValues
	 *
	 * 	todo: write documentation
	 */
	static public function countValues($array)
	{
		$counts = array();

		foreach(self::valid($array) as $v){
			if(!isset($counts[$v])) $counts[$v] = 0;

			$counts[$v]++;
		}

		return $counts;
	}

	/**
	 * 	method:	find
	 *
	 * 	todo: write documentation
	 */
	static public function find($array,$key,$value)
	{
		$result = NULL;

		foreach(self::valid($array) as $a){
			if($a[$key] == $value) return $a;
		}

		return false;
	}

	/*	I don't know how to integrate this into the api yet, but it's a cool function!!!
	//	author: d3x from freenode's #php channel
	//	usage: find($array,$k1,$k2,$k3,$k4)
	//	description: you have a multi-dimensional array, the keys will represent a path to the final kv pair
	function find($array) {
		$args = func_get_args();
		array_shift($args);
		if ($args) {
			$key = array_shift($args);
			array_unshift($args, $array[$key]);
			return call_user_func_array(__FUNCTION__, $args);
		}
		return $array;
	}*/

	/**
	 * 	method:	searchKeys
	 *
	 * 	todo: write documentation
	 */
	static public function searchKeys($array,$filter)
	{
		$matches = array();

		foreach(self::valid($array) as $k=>$v){
			if(strpos($k,$filter) !== false) $matches[$k] = $v;
		}

		return $matches;
	}

	/**
	 * 	method:	stripSlashesMulti
	 *
	 * 	todo: write documentation
	 */
	static public function stripSlashesMulti($array,$key=NULL)
	{
		if(is_string($key)) $key = array($key);

		$array = self::valid($array);

		foreach($array as &$element){
			if(!$key){
				$element = array_map("stripslashes",$element);
			}else{
				foreach($key as $index) $element[$index] = stripslashes($element[$index]);
			}
		}

		return $array;
	}

	/**
	 * 	method:	stripSlashesSingle
	 *
	 * 	todo: write documentation
	 */
	static public function stripSlashesSingle($array,$key="")
	{
		if($key == "") $key = array_keys($array);

		if(!is_array($key)){
			$array[$key] = stripslashes($array[$key]);
		}else{
			foreach($key as $index){
				$array[$index] = stripslashes($array[$index]);
			}
		}
		return $array;
	}

	/**
	 * 	method:	stripSlashes
	 *
	 * 	todo: write documentation
	 */
	static public function stripSlashes($array,$key="")
	{
		if(!is_array($array) || empty($array)) return $array;

		if($key == "") $key = array_keys($array);

		return (self::isMulti($array)) ?
					self::stripSlashesMulti($array,$key) :
					self::stripSlashesSingle($array,$key);
	}

	//	deprecated: this is not supposed to be here
	//	FIXME: glob() on an array object? when it refers to the filesystem or array? I think it's a mistake to put this method here
	/**
	 * 	method:	glob
	 *
	 * 	todo: write documentation
	 */
	static public function glob($location,$relative=false)
	{
		$items = glob(Amslib_Website::abs($location));

		if($relative){
			foreach($items as &$i){
				$i = Amslib_Website::rel($i);
			}
		}

		return $items;
	}

	/**
	 * 	method:	implodeQuote
	 *
	 * 	todo: write documentation
	 */
	static public function implodeQuote($array,$join=",",$quote="")
	{
		$spaceList = array();
		foreach($array as $s){
			$spaceList[] = "{$quote}$s{$quote}";
		}

		return implode($join,$spaceList);


	}

	/**
	 * 	method:	wrap
	 *
	 * 	todo: write documentation
	 *
	 *	NOTE:	Of course, we use a normal separator "," to split the string
	 *			But if any of the elements in each array element contains a "," character
	 *			this will break, so in these circumstances, you need to choose a separator
	 *			which ISNT present in ANY array element, e.g: #array_sep# or something unique
	 *
	 *	NOTE:	I am adding slashes to the " character, but this might not be the only character which
	 *			causes a problem here, we need to investigate any potential others, but I think for now this is ok
	 */
	static public function wrap($array,$char,$returnArray=true,$sep=",")
	{
		$wrap	=	$char=="\"" ? addslashes($char) : $char;
		$sep	=	addslashes($sep);

		$string = "$char".implode("{$char}{$sep}{$char}",$array)."$char";

		return $returnArray ? explode($sep,$string) : $string;
	}

	/**
	 * 	method:	implodeQuoteSingle
	 *
	 * 	todo: write documentation
	 */
	static public function implodeQuoteSingle($array,$join=",")
	{
		return self::implodeQuote($array,$join,"'");
	}

	/**
	 * 	method:	implodeQuoteDouble
	 *
	 * 	todo: write documentation
	 */
	static public function implodeQuoteDouble($array,$join=",")
	{
		return self::implodeQuote($array,$join,"\"");
	}
}
