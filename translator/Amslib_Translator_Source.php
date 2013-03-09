<?php 
abstract class Amslib_Translator_Source
{
	protected $name;
	protected $language;
	protected $defaultKey;
	
	abstract public function addLanguage				($langCode);
	abstract public function setLanguage				($langCode);
	abstract public function getLanguage				();
	abstract public function getAllLanguages			();	
	abstract public function isLanguage				($langCode);
	abstract public function setLocation				($location);
	abstract public function load					();
	abstract public function translateExtended		($n,$i,$l=NULL);
	abstract public function learnExtended			($n,$i,$v,$l=NULL);
	abstract public function forgetExtended			($n,$i,$l=NULL);
	abstract public function updateKeyExtended		($n,$i,$nn,$l=NULL);
	abstract public function searchKeyExtended		($n,$i,$s=false,$l=NULL);
	abstract public function searchValueExtended		($v,$i,$s=false,$l=NULL);
	abstract public function getKeyListExtended		($i,$l=NULL);
	abstract public function getValueListExtended	($i,$l=NULL);
	abstract public function getListExtended			($i,$l=NULL);
	
	//	These default methods are just proxies for the extended methods now, using the default key (0, zero)
	public function translate		($n,$l=NULL){			return $this->translateExtended($n,$this->defaultKey,$l);		}
	public function learn			($n,$v,$l=NULL){		return $this->learnExtended($n,$this->defaultKey,$v,$l);		}
	public function forget		($n,$l=NULL){			return $this->forgetExtended($n,$this->defaultKey,$l);			}
	public function updateKey		($n,$nn,$l=NULL){		return $this->updateKeyExtended($n,$this->defaultKey,$nn,$l);	}
	public function searchKey		($n,$s=false,$l=NULL){	return $this->searchKeyExtended($n,$this->defaultKey,$s,$l);	}
	public function searchValue	($v,$s=false,$l=NULL){	return $this->searchValueExtended($n,$this->defaultKey,$s,$l);	}
	public function getKeyList	($l=NULL){				return $this->getKeyListExtended($n,$this->defaultKey,$l);		}
	public function getValueList	($l=NULL){				return $this->getValueListExtended($n,$this->defaultKey,$l);	}
	public function getList		($l=NULL){				return $this->getListExtended($n,$this->defaultKey,$l);		}
	
	//	NOTE: now all the shorthand versions of the method names, to keep the code "short and sweet"
	public function t		($n,$l=NULL){				return $this->translate($n,$l);					}
	public function te	($n,$i,$l=NULL){			return $this->translateExtended($n,$i,$l);			}
	public function l		($n,$v,$l=NULL){			return $this->learn($n,$v,$l);						}
	public function le	($n,$i,$v,$l=NULL){			return $this->learnExtended($n,$i,$v,$l);			}
	public function f		($n,$l=NULL){				return $this->forget($n,$l);						}
	public function fe	($n,$i,$l=NULL){			return $this->forgetExtended($n,$i,$l);			}
	public function sk	($n,$s=false,$l=NULL){		return $this->searchKey($n,$s,$l);					}
	public function ske	($n,$i,$s=false,$l=NULL){	return $this->searchKeyExtended($n,$i,$s,$l);		}
	public function sv	($v,$s=false,$l=NULL){		return $this->searchValue($v,$s,$l);				}
	public function sve	($v,$i,$s=false,$l=NULL){	return $this->searchValueExtended($v,$i,$s,$l);	}
}