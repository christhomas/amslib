<?php
class Amslib_Form
{
	static public function arrayToSelectOptions($array,$keyText,$keyValue,$selected=NULL)
	{
		$options = "";

		if(!$array || !is_array($array) || empty($array)) return $options;

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

	static public function numberSequenceToSelectOptions($start,$stop,$selected=NULL,$pad=NULL)
	{
		$options = "";

		if(!is_numeric($start) || !is_numeric($stop)) return $options;

		for($a=$start;$a<=$stop;$a++){
			$enabled = ($a == $selected) ? "selected='selected'" : "";
			
			if($pad !== NULL && is_string($pad)) $a = str_pad($a,strlen($pad),$pad[0],STR_PAD_LEFT); 

			$options .= "<option $enabled value='$a'>$a</option>";
		}

		return $options;
	}

	static public function getFilename($name)
	{
		$file = Amslib::filesParam($name);

		return ($file && isset($file["name"])) ? $file["name"] : false;
	}

	static public function getTempFilename($name)
	{
		$file = Amslib::filesParam($name);

		return ($file && isset($file["tmp_name"])) ? $file["tmp_name"] : false;
	}
}