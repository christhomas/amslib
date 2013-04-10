<?php
class Amslib_Form
{
	static public function selectOptions($array,$selected=NULL,$indexText=NULL,$indexValue=NULL,$createAttributes=false)
	{
		$options = array();

		foreach(Amslib_Array::valid($array) as $arrayKey=>$item){
			if(is_array($item)){
				$text	=	$indexText && isset($item[$indexText])		?	$item[$indexText]	:	"";
				$value	=	$indexValue && isset($item[$indexValue])	?	$item[$indexValue]	:	"";
			}else if(is_string($item) && $indexText == "use_key"){
				$text	=	$item;
				$value	=	$arrayKey;
			}else{
				$text = $value = $item;
			}
			
			$attributes = array();
			if($createAttributes && is_array($item) && isset($item[$indexText]) && isset($item[$indexValue])){
				unset($item[$indexText],$item[$indexValue]);
				
				foreach($item as $k=>&$v) $v="$k='$v'";
				
				$attributes = $item;
			}
			$attributes = implode(" ",$attributes);

			if(strlen($text) == 0 || strlen($value) == 0) continue;

			$enabled = $value == $selected ? "selected='selected'" : "";

			$options[] = "<option $enabled value='$value' $attributes>$text</option>";
		}

		return implode("",$options);
	}
	
	static public function monthOptions($start,$stop,$selected=NULL,$pad=NULL)
	{
		if($start < 0 || $start > 12) $start = 12;
		if($stop < 0 || $stop > 12) $stop = 12;
		
		$keys = range($start,$stop);
		
		$months = array();
		foreach($keys as $k){
			$index = $k;
			
			if($pad !== NULL && is_string($pad)){
				$index = str_pad($k,strlen($pad),$pad[0],STR_PAD_LEFT);
			}
			
			$months[$index] = date("F",mktime(0,0,0,$k));
		}

		return self::selectOptions($months,$selected,"use_key");
	}

	static public function numericSelectOptions($start,$stop,$selected=NULL,$pad=NULL)
	{
		return self::numberSequenceToSelectOptions($start,$stop,$selected,$pad);
	}

	static public function numberSequenceToSelectOptions($start,$stop,$selected=NULL,$pad=NULL)
	{
		$options = "";

		if(!is_numeric($start) || !is_numeric($stop)) return $options;

		foreach(range($start,$stop) as $a){
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