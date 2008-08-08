<?php
require_once("lib/dni.php");

class FormValidator
{
	var $__error;
	var $__items;
	var $__source;
	
	function FormValidator($source)
	{
		$this->__items = array();
		$this->__error = array();
		$this->__source = $source;
	}
	
	function __text($value,$required,$minlength)
	{
		$len = strlen($value);
		if(($len == 0 || $len < $minlength) && $required == true) return "length of text cannot be zero";
		
		return true;
	}
	
	function __dni($dni,$required)
	{
		$result = validateDNI($dni);

		if($required == false) return true;
		
		return ($result > 0) ? true : false;
	}
	
	function __boolean($value,$required,$minlength)
	{
		if($required === true)	return is_bool($value);
		
		return true;			
	}
	
	function __number($value,$required,$minvalue)
	{	
		//	I am adding and removing required from here on a case by case basis?
		//	Does this mean I am not sure of the logic and I am customising
		//	depending on the "project" needs?
		if(is_numeric($value) == false && $required == true){
			if($value === NULL) return "value was NaN (NaN = not a number, number is required)";
		}

		return true;
	}
	
	function __email($value,$required,$minlength)
	{
		if(strlen($value) == 0 && $required == true) return "Email address was empty, value is required";
		
		$pattern = "^[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+(\.[a-z0-9,!#\$%&'\*\+/=\?\^_`\{\|}~-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,})$";
		return (eregi($pattern,$value)) ? true : "invalid email address";
	}
	
	function __phone($value,$required,$minlength)
	{
		if(strlen($value) == 0 && $required == true) return "Phone number was empty, value is required";
		
		$value = str_replace("(","",$value);
		$value = str_replace(")","",$value);
		$value = str_replace("+","",$value);
		$value = str_replace("-","",$value);
		$value = str_replace(" ","",$value);
		$value = preg_replace("/\d/","",$value);
		$value = trim($value);

		return (strlen($value)) ? "phone number format was incorrect" : true;
	}
	
	function __file($value,$required,$minlength)
	{
		if($required == false) return true;
		else if(is_file($value["tmp_name"])) return true;
		
		return "File was not present in the temporary upload location";
	}
	
	function setType($item,$type,$required=false,$minlength=0)
	{
		$this->__items[$item] = array($type,$required,$minlength);
	}
	
	function execute()
	{
		foreach($this->__items as $item=>$validator){
			$callback = "__{$validator[0]}";
			$required = $validator[1];
			$minlength = $validator[2];
			
			if($validator[0] == "file"){
				$value = $_FILES[$item];
			}else{
				$value = (isset($this->__source[$item])) ? $this->__source[$item] : NULL;
			}
			
			$error = $this->$callback($value,$required,$minlength);
			if($error !== true){
				$this->__error[] = array($item,$value,$error);
			}
		}
		
		return $this->getStatus();
	}
	
	function getErrors()
	{
		return $this->__error;
	}
	
	function getSuccess($field){
		foreach($this->__error as $error){
			if($error[0] == $field) return false;
		}
		
		return true;
	}
	
	function getStatus()
	{
		return (count($this->__error)) ? false : true;
	}
	
	function itemCount()
	{
		return count($this->__items);
	}
}
?>