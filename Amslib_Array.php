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
	 * 	method:	testKeys
	 *
	 * 	Test whether keys exist in an array or not
	 *
	 * 	NOTE: I think I could replace some of this code with a cheaper array_intersect or something instead
	 */
	static protected function testKeys($array,$keys,$ignoreFailure=false,$test=false)
	{
		if(!is_array($array)) return NULL;
		if(is_string($keys) && strlen($keys)) $keys = array($keys);
		if(!count($keys)) return NULL;

		$found = array();

		foreach(self::valid($keys) as $k){
			if(array_key_exists($k,$array) === $test){
				if($test){
					$found[$k] = $array[$k];
				}
			}else{
				if($ignoreFailure == false){
					return false;
				}
			}
		}

		return $found;
	}

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
		if($key !== NULL && is_string($key)){
			$array = array_key_exists($key,$array) ? $array[$key] : array();
		}
		
		//	Invalid values return an empty array
		if(empty($array) || !$array || !is_array($array) || is_null($array)){
			return array();
		}
		
		//	cast objects to arrays
		if(is_object($array)){
			$array = (array)$a;
		}
		
		//	return the original value
		return $array;
	}

	/**
	 * 	method:	isMulti
	 *
	 * 	Check the array is a multidimensional array or not
	 *  returns true if multi, false if not
	 *
	 *  notes:
	 *  	-	It's quite hard to know whether an array is an array containing results which are
	 *  		interesting, say an array of SQL rows, all containing various fields, or whether
	 *  		an array is just a normal array which may contain an array, but is not multi-dimensional
	 *  	-	This method simplifies this a lot, but may present issues in itself, because it
	 *  		basically considers an array to be multidimensional, IF and ONLY IF, the array
	 *  		being tested, contains a single type of data "array", but this is ignoring quite
	 *  		a few types of array I can think of which are still multidimensional
	 */
	static public function isMulti($array)
	{
		$result = array_unique(array_map("gettype",$array));

		return count($result) == 1 && array_shift($result) == "array";
	}

	static public function reindexByKeyValue($array,$key,$value)
	{
		$array = self::valid($array);

		if(!is_string($key) || !is_string($value)){
			return $array;
		}

		$copy = array();

		foreach($array as $item){
			if(isset($item[$key]) && isset($item[$value])){
				$copy[$item[$key]] = $item[$value];
			}
		}

		return $copy;
	}

	/**
	 * 	method:	reindexByKey
	 *
	 * 	todo: write documentation
	 *
	 * 	FIXME: \$optimise value is never used
	 */
	static public function reindexByKey($array,$key,$optimise=false)
	{
		$array = self::valid($array);

		if(!is_string($key)) return $array;

		$copy = array();

		foreach($array as $item){
			if(isset($item[$key])){
				$copy[$item[$key]] = $item;
			}
		}

		return $copy;
	}

	/**
	 * 	method:	fillMissing
	 *
	 * 	todo: write documentation
	 *
	 * 	notes:
	 * 		-	This method allows me to batch fix missing keys in arrays in broken code quickly and easily.
	 */
	static public function fillMissing($array,$key,$value=NULL)
	{
		if(is_string($key)) $key = array($key);

		return array_merge(array_fill_keys($key,$value),$array);
	}

	/**
	 * 	method:	hasKeys
	 *
	 *	Searches an array for a set of keys and returns either an
	 *	array of the matching key/values, boolean false, or null depending on what the array contained
	 *
	 *	parameters:
	 *		$array			-	The array to search
	 *		$keys			-	The keys used to search in the array
	 *		$ignoreFailure	-	Whether or not to ignore failed tests and return what did match
	 *
	 * 	returns:
	 * 		-	NULL if the array if not valid, boolean false for failure, or an array of matching key/values
	 * 			depending on the value of $ignoreFailure
	 */
	static public function hasKeys($array,$keys,$ignoreFailure=false)
	{
		if(is_string($keys) && is_string($ignoreFailure)){
			Amslib_Debug::log(
				"keys and ignoreFailure parameters are both detected as strings, probably you ".
				"forgot this method accepts an array of strings and are just passing the keys ".
				"one by one to the parameters of this function which will result in the first ".
				"being tested and ignoreFailure will be enabled, I don't think this is what you intended",
				$keys,
				$ignoreFailure
			);
		}

		return self::testKeys($array,$keys,$ignoreFailure,true);
	}

	/**
	 * 	method:	notKeys
	 *
	 *	Searches an array for a set of keys and returns either an
	 *	array of the non matching key/values, boolean false, or null depending on what the array contained
	 *
	 *	parameters:
	 *		$array			-	The array to search
	 *		$keys			-	The keys used to search in the array
	 *		$ignoreFailure	-	Whether or not to ignore failed tests and return what did match
	 *
	 * 	returns:
	 * 		-	NULL if the array if not valid, boolean false for failure, or an array of non-matching key/values

	 */
	static public function notKeys($array,$keys)
	{
		$return = self::testKeys($array,$keys,false,false);

		return is_array($return) ? true : $return;
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
			Amslib_Debug::log("ERROR, PIVOT INDEX DOES NOT EXIST","stack_trace");
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
		if(!is_array($array)){
			return array();
		}

		if (!is_array($key)) {
			$key = func_get_args();
			array_shift($key);
		}

		if(self::isMulti($array)){
			foreach($array as &$item){
				$item = self::pluck($item,$key);
			}

			if(count($key) == 1){
				foreach($array as &$r){
					$r = $r[$key[0]];
				}
			}

			return $array;
		}

		return array_intersect_key($array,array_flip($key));
	}

	static public function toKeyValue($list)
	{
		$list	=	self::valid($list);
		$array	=	array();

		if(empty($list) || count($list) == 1) return $array;

		$k = NULL;
		foreach($list as $index=>$item){
			if($k == NULL){
				if(is_numeric($item)){
					$k = intval($item);
				}else if(is_string($item)){
					$k = $item;
				}else{
					Amslib_Debug::log("list was invalid, key not string or number",$index,$item,$k,$array);
					return $array;
				}
			}else{
				$array[$k] = $item;

				$k = NULL;
			}
		}

		return $array;
	}

	static private function ______UPGRADE_METHODS_BELOW(){}

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
	 * method:removeKeys
	 *
	 * This method is a wrapper around unset to make it easier and less verbose in your code to remove multiple elements
	 * 
	 * note:
	 * 	-	I'm not entirely sure this code does 100% of what I think it should
	 */
	static public function removeKeys($array,$keys)
	{
		$args	=	func_get_args()+array(array(),"");

		$array	=	self::valid(array_shift($args));

		foreach(self::valid($args) as $k){
			if(is_array($k)){
				array_unshift($k,$array);
				$array = call_user_func_array("self::removeKeys",$k);
			}else if(is_string($k) || is_numeric($k)){
				unset($array[$k]);
			}
		}

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
	 * 	method:	countValues
	 *
	 * 	todo: write documentation
	 *
	 * 	TODO:	this method is a little open to abuse and in some situations wouldn't do the right thing
	 * 	TODO:	explain in words what this does and how it should work
	 * 	NOTE:	I am not sure what the above comment actually means, I should have been more explicit in my explanation
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
			if(array_key_exists($key,$a) && $a[$key] == $value){
				return $a;
			}
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
		$items = glob(Amslib_Website::absolute($location));

		if($relative){
			foreach($items as &$i){
				$i = Amslib_Website::relative($i);
			}
		}

		return $items;
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
	static public function filterKey($array,$filter,$similar=false,$multi=true)
	{
		if($similar) return self::filter($array,NULL,$filter,true,true);

		$array = self::valid($array);

		if(is_string($filter)) $filter = array($filter);

		if(self::isMulti($array) && $multi){
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
			if(!is_array($filter)){
				Amslib_Debug::log("stack_trace","filter is not an array",$filter);
			}else{
				$array = array_intersect_key($array, array_flip($filter));
			}
		}

		return $array;
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

			if($similar == true && strlen($value) && strpos($search,$value) !== false) $found = true;
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

	/**
	 * 	method:	filter2
	 *
	 * 	todo:	write documentation
	 *
	 * 	NOTE:	I am trying to create a better documented, better understood version of this method
	 * 			cause the above method has gone a little crazy, with me not really understanding why
	 * 			I added certain code, but I don't want to change it directly in case it subtly breaks
	 * 			stuff that depends on it's method of operation until I can identify and change them all
	 */
	static public function filter2($array,$key,$value,$returnFiltered=false,$similar=false)
	{
		$matched = array();

		foreach(self::valid($array) as $k=>$v){
			$found = false;

			if($key !== NULL && is_array($v) && isset($v[$key])){
				//	if each array element is an array, we need to search by the key given in each child array
				$v = $v[$key];
			}

			if($similar == true && !is_array($v) && strpos($v,$value) !== false){
				//	similar was enabled, search value was not an array and a fuzzy match was found
				$found = true;
			}else if(!is_array($v) && $v == $value){
				//	similar was not enabled, search value was not an array, and a perfect match was found
				$found = true;
			}

			if($found){
				//	match was found, add it to the matched array and remove it from the unmatched remaining array items
				$matched[$k] = $v;
				unset($array[$k]);
			}
		}

		//	If you ask for the filtered items, we return either the matched array or the remaining items not matched
		return $returnFiltered ? $matched : $array;
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

		$string = $char.implode($char.$sep.$char,$array).$char;

		return $returnArray ? explode($sep,$string) : $string;
	}

	/**
	 * 	method:	implode
	 *
	 * 	todo: write documentation
	 */
	static public function implode($array,$join=",",$quote="")
	{
		return $quote.implode($quote.$join.$quote,$array).$quote;
	}

	/**
	 * 	method:	implodeQuoteSingle
	 *
	 * 	todo: write documentation
	 */
	static public function implodeQuoteSingle($array,$join=",")
	{
		return self::implode($array,$join,"'");
	}

	/**
	 * 	method:	implodeQuoteDouble
	 *
	 * 	todo: write documentation
	 */
	static public function implodeQuoteDouble($array,$join=",")
	{
		return self::implode($array,$join,"\"");
	}

	static private function ______DEPRECATED_METHODS_BELOW(){}

	static public function missingKeys($array,$key,$value=NULL)
	{
		return self::fillMissing($array,$key,$value);
	}

	static public function pluck2($array,$keys)
	{
		return self::pluck($array,$keys);
	}

	static public function pluckMulti2($array,$keys)
	{
		return self::pluck($array,$keys);
	}
}
