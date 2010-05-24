<?php 
class Amslib_Form
{
	static public function arrayToSelectOptions($array,$keyText,$keyValue,$selected=NULL)
	{
		if(!$array || !is_array($array) || empty($array)) return "";
		
		$options = "";
		
		foreach($array as $key=>$value){
			$text		=	(isset($value[$keyText])) ? $value[$keyText] : "";
			$value		=	(isset($value[$keyValue])) ? $value[$keyValue] : "";
			$enabled	=	($value == $selected) ? "selected='selected'" : "";

			//	ignore blank strings
			if(strlen($text) == 0 || strlen($value) == 0) continue;
			
			$options .= "<option $enabled value='$value'>$text</option>";
		}
		
		return $options;
	}
}