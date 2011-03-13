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
	
	//	NOTE:	we don't do anything to load as there is nothing to load 
	//			(unless I think of a way to do this from an array or something)
	public function load($location)
	{
		//	DO NOTHING
	}
	
	public function addLanguage($langCode)
	{
		if(is_string($langCode)){
			$this->permittedLanguage[] = $langCode;	
		}
		
		if(is_array($langCode)){
			$this->permittedLanguage = array_merge($this->permittedLanguage,$langCode);	
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
		return (is_string($k) && isset($this->store[$k])) ? $this->store[$k] : $k;	
	}
	
	public function learn($k,$v)
	{
		$this->store[$k] = $v;
	}
	
	public function forget($k)
	{
		unset($this->store[$k]);
	}
	
	public function updateKey($k,$nk)
	{
		$this->learn($nk,$this->translate($k));
		$this->forget($k);
	}
	
	public function getKeyList()
	{
		return array_keys($this->store);
	}
	
	public function getValueList()
	{
		return array_values($this->store);
	}
}