<?php 
class Amslib_Array
{
	static public function filter($array,$key,$value,$returnFiltered=false)
	{
		$filter = array();
		
		foreach($array as $k=>$v)
		{
			if((is_array($value) && in_array($v[$key],$value)) || ($v[$key] == $value)){
				$filter[$k] = $v;
				unset($array[$k]);
			}	
		}
		
		return $returnFiltered ? $filter : $array;
	}
	
	static public function find($array,$key,$value)
	{
		foreach($array as $a){
			if($a[$key] == $value) return $a;
		}
		
		return false;
	}
	
	static public function searchKeys($array,$filter)
	{
		$matches = array();
		
		foreach($array as $k=>$v){
			if(strpos($k,$filter) !== false) $matches[$k] = $v;
		}
		
		return $matches;
	}
}