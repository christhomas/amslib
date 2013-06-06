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
 * 	deprecated: use the Amslib_Array2 object for everything that has been ported, dont use this in new code unless the method is not available
 *
 */
class Amslib_Array
{
	//	NOTE: I dont think "filterType" is a good function name for this, when it takes a callback, perhaps filterCallback instead?
	/**
	 * 	method:	filterType
	 *
	 * 	todo: write documentation
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
	 * 	method:	findKey
	 *
	 * 	todo: write documentation
	 */
	static public function findKey($array,$key,$value)
	{
		foreach(self::valid($array) as $k=>$a){
			if($a[$key] == $value) return $k;
		}

		return false;
	}

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
	
	/**	DEPRECATED METHODS: use the Amslib_Array2 version of each method, all parameters are identical */
	static public function valid($array=NULL,$key=NULL){					return Amslib_Array2::valid($array,$key);					}
	static public function min($array,$key,$returnKey=NULL){					return Amslib_Array2::min($array,$key,$returnKey);			}
	static public function max($array,$key,$returnKey=NULL){					return Amslib_Array2::max($array,$key,$returnKey);			}	
	static public function sort($array,$index){								return Amslib_Array2::sort($array,$index);					}
	static public function pluck($array,$key){								return Amslib_Array2::pluck($array,$key);					}
	static public function unique($array,$field){							return Amslib_Array2::unique($array,$field);				}	
	static public function removeValue(array $array,$value,$strict=false){	return Amslib_Array2::removeValue($array,$value,$strict);	}
	static public function removeKeys($array,$keys){							return Amslib_Array2::removeKeys($array,$keys);			}	
	static public function reindexByKey($array,$key){						return Amslib_Array2::reindexByKey($array,$key);			}
	static public function missingKeys($array,$key,$value=NULL){				return Amslib_Array2::missingKeys($array,$key,$value);		}
	static public function hasKeys($array,$present,$missing=NULL){			return Amslib_Array2::hasKeys($array,$present,$missing);	}
	static public function isMulti($array){									return Amslib_Array2::isMulti($array);						}
	static public function diff($array1,$array2,$strict=false){				return Amslib_Array2::diff($array1,$array2,$strict);		}
	static public function diffBoth($array1,$array2,$strict=false){			return Amslib_Array2::diffBoth($array1,$array2,$strict);	}
}
