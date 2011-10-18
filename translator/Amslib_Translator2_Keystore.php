<?php 
class Amslib_Translator2_Keystore extends Amslib_Translator2_Source
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
			$this->store[$langCode] = array();	
		}
		
		if(is_array($langCode)){
			$this->permittedLanguage = array_merge($this->permittedLanguage,$langCode);
			foreach($langCode as $l) $this->store[$l] = array();	
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
	
	public function translate($k,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return (is_string($k) && isset($this->store[$l][$k])) ? $this->store[$l][$k] : $k;	
	}
	
	public function learn($k,$v,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		$this->store[$l][$k] = $v;
	}
	
	public function forget($k,$l=NULL)
	{
		if(!$l) $l = $this->language;
		
		unset($this->store[$l][$k]);
	}
	
	public function updateKey($k,$nk,$l=NULL)
	{
		$this->learn($nk,$this->translate($k,$l),$l);
		$this->forget($k,$l);
	}
	
	public function getKeyList($l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return array_keys($this->store[$l]);
	}
	
	public function getValueList($l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return array_values($this->store[$l]);
	}
	
	public function getList($l=NULL)
	{
		if(!$l) $l = $this->language;
		
		return $this->store[$l];
	}
}