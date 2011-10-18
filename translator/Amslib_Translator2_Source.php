<?php 
abstract class Amslib_Translator2_Source
{
	protected $language;
	
	abstract public function addLanguage		($langCode);
	abstract public function setLanguage		($langCode);
	abstract public function getLanguage		();
	abstract public function getAllLanguages	();	
	abstract public function isLanguage			($langCode);
	abstract public function setLocation		($location);
	abstract public function load				();
	abstract public function translate			($k);
	abstract public function learn				($k,$v);
	abstract public function forget				($k);
	abstract public function updateKey			($k,$nk);
	abstract public function getKeyList			();
	abstract public function getValueList		();
	abstract public function getList			();
	
	public function t($k){
		return $this->translate($k);
	}
	
	public function l($k,$v){
		return $this->learn($k,$v);
	}
	
	public function f($k){
		return $this->forget($k);
	}
}