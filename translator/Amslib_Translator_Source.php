<?php 
abstract class Amslib_Translator_Source
{
	protected $name;
	protected $language;
	
	abstract public function addLanguage		($langCode);
	abstract public function setLanguage		($langCode);
	abstract public function getLanguage		();
	abstract public function getAllLanguages	();	
	abstract public function isLanguage		($langCode);
	abstract public function setLocation		($location);
	abstract public function load			();
	abstract public function translate		($k,$l=NULL);
	abstract public function learn			($k,$v,$l=NULL);
	abstract public function forget			($k,$l=NULL);
	abstract public function updateKey		($k,$nk,$l=NULL);
	abstract public function searchKey		($k,$s=false,$l=NULL);
	abstract public function searchValue		($v,$s=false,$l=NULL);
	abstract public function getKeyList		($l=NULL);
	abstract public function getValueList	($l=NULL);
	abstract public function getList			($l=NULL);
	
	public function t($k,$l=NULL){
		return $this->translate($k,$l);
	}
	
	public function l($k,$v,$l=NULL){
		return $this->learn($k,$v,$l);
	}
	
	public function f($k,$l=NULL){
		return $this->forget($k,$l);
	}
	
	public function sk($k,$s=false,$l=NULL){
		return $this->searchKey($k,$s,$l);
	}
	
	public function sv($v,$s=false,$l=NULL){
		return $this->searchValue($v,$s,$l);
	}
}