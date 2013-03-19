<?php 
class Amslib_Translator_Keystore extends Amslib_Translator_Source
{
	protected $store;
	protected $permittedLanguage;	
	
	protected function sanitise($langCode)
	{
		if(in_array($langCode,$this->permittedLanguage)){ 
			//	Return the language code, it was found in the permitted list
			return $langCode;
		}
		
		//	The code tested was not found, default to the first added language
		return current($this->permittedLanguage);
	}
	
	public function __construct()
	{
		$this->store				=	array();
		$this->language				=	false;
		$this->permittedLanguage	=	array();
		$this->defaultKey			=	0;
	}
	
	//	Set location in the future could mean "set a default set of array information"
	public function setLocation($location){}

	//	The concept of "load" doesnt make sense for a keystore array
	public function load()
	{
		//	DO NOTHING
	}
	
	public function addLanguage($langCode)
	{
		if(is_string($langCode)){
			$this->permittedLanguage[] = $langCode;
			$this->store[$langCode] = array($this->defaultKey=>array());	
		}
		
		if(is_array($langCode)){
			$this->permittedLanguage = array_merge($this->permittedLanguage,$langCode);
			foreach($langCode as $l) $this->store[$l] = array($this->defaultKey=>array());	
		}
	}
	
	public function setLanguage($langCode)
	{
		$this->language = $this->sanitise($langCode);
	}
	
	public function getLanguage()
	{
		return $this->language;
	}
	
	public function getAllLanguages()
	{
		return $this->permittedLanguage;
	}
	
	public function isLanguage($langCode)
	{
		return ($langCode == $this->language);
	}
	
	public function translateExtended($n,$i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return is_string($n) && is_numeric($i) && isset($this->store[$l][$i][$n]) 
			? $this->store[$l][$i][$n] 
			: $n;
	}
	
	public function learnExtended($n,$i,$v,$l=NULL)
	{
		if(!is_int($i)) return false;
		
		if(!$l) $l = $this->language;
	
		$this->store[$l][$i][$n] = $v;
		
		return true;
	}
	
	public function forgetExtended($n,$i,$l=NULL)
	{
		if(!is_int($i)) return false;
		
		if(!$l) $l = $this->language;
	
		unset($this->store[$l][$i][$n]);
		
		return true;
	}
	
	public function updateKeyExtended($n,$i,$nn,$l=NULL)
	{
		$this->learnExtended($nn,$i,$this->translateExtended($n,$i,$l),$l);
		$this->forgetExtended($n,$i,$l);
	}
	
	public function searchKeyExtended($n,$i,$s=false,$l=NULL)
	{
		//	TODO: not implemented yet
		//	TODO: no extended version yet
		return array();		
	}
	
	public function searchValueExtended($v,$i,$s=false,$l=NULL)
	{
		//	TODO: not implemented yet
		//	TODO: no extended version yet
		return array();
	}
	
	public function getKeyListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return array_keys($this->store[$l][$i]);
	}
	
	public function getValueListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return array_values($this->store[$l][$i]);
	}
	
	public function getListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;
	
		return $this->store[$l][$i];
	}
}