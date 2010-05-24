<?php 
class Amslib_Translator_MemoryInterface extends Amslib_Translator_BaseAccessLayer
{
	var $__keyStore;
	
	function Amslib_Translator_MemoryInterface()
	{
		parent::Amslib_Translator_BaseAccessLayer();
		
		$this->__keyStore = array();
	}
	
	function l($key,$value)
	{
		return $this->learn($key,$value);
	}
	
	function learn($key,$value)
	{
		$this->__keyStore[$key] = $value;
	}
	
	function t($key)
	{
		return $this->translate($key);
	}
	
	function translate($key)
	{
		if(is_string($key) && isset($this->__keyStore[$key])){
			return $this->__keyStore[$key];	
		}
		
		return $key;
	}
}