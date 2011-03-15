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
	
	//	Set location in the future could mean "set a default set of information"
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
	
	public function translate($k)
	{
		return (is_string($k) && isset($this->store[$this->language][$k])) ? $this->store[$this->language][$k] : $k;	
	}
	
	public function learn($k,$v)
	{
		$this->store[$this->language][$k] = $v;
	}
	
	public function forget($k)
	{
		unset($this->store[$this->language][$k]);
	}
	
	public function updateKey($k,$nk)
	{
		$this->learn($nk,$this->translate($k));
		$this->forget($k);
	}
	
	public function getKeyList()
	{
		return array_keys($this->store[$this->language]);
	}
	
	public function getValueList()
	{
		return array_values($this->store[$this->language]);
	}
}