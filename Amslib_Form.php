<?php
class Amslib_Form
{
	static public function selectOptions($array,$selected=NULL,$indexText=NULL,$indexValue=NULL)
	{
		$options = array();

		foreach(Amslib_Array::valid($array) as $arrayKey=>$item){
			if(is_array($item)){
				$text	=	$indexText && isset($item[$indexText])		?	$item[$indexText]	:	"";
				$value	=	$indexValue && isset($item[$indexValue])	?	$item[$indexValue]	:	"";
			}else if(is_string($item) && $indexText == "useKey"){
				$text	=	$item;
				$value	=	$arrayKey;
			}else{
				$text = $value = $item;
			}

			if(strlen($text) == 0 || strlen($value) == 0) continue;

			$enabled = $value == $selected ? "selected='selected'" : "";

			$options[] = "<option $enabled value='$value'>$text</option>";
		}

		return implode("",$options);
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

	static public function selectRadioButton($value,$compare)
	{
		return ($value == $compare) ? "checked='checked'" : "";
	}

	static public function selectCheckbox($value,$compare)
	{
		return ($value == $compare) ? "checked='checked'" : "";
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

	//	DEPRECATED METHOD: use selectOptions() instead
	static public function arrayToSelectOptions($array,$keyText,$keyValue,$selected=NULL)
	{
		return self::selectOptions($array,$selected,$keyText,$keyValue);
	}
}