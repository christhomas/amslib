<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * file: Amslib_Translator.php
 * title: Human Language Translator
 * version: 3.0
 * description: A translator object which uses a language catalog like gettext but doesnt
 *		really suck by actually doing things cleverly, you can learn, forget, translate
 *		mix catalogues from different parts of projects together, it's much more powerful
 *		and does exactly the same job.
 *
 * Contributors:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

/**
 * class: Amslib_Translator
 *
 * Translate strings from between a recognised input and output, it's fast,
 * flexible and easy to use (easier than PO files anyway)
 */
class Amslib_Translator extends Amslib_Translator_Source
{
	protected	$source;
	protected	$stackLanguage;

	public function __construct($type,$name=NULL)
	{
		$this->name = $name;
		
		switch($type){
			case "xml":{		$this->source = new Amslib_Translator_XML();		}break;
			case "database":{	$this->source = new Amslib_Translator_Database();	}break;
			case "keystore":{	$this->source = new Amslib_Translator_Keystore();	}break;
		}
	}
	
	static public function &getInstance($type)
	{
		static $instance = NULL;
		
		if($instance === NULL) $instance = new self($type);
		
		return $instance;
	}

	/********************************************************************************
	 *	LANGUAGE METHODS
	********************************************************************************/
	public function addLanguage($langCode){		return $this->source->addLanguage($langCode);	}
	public function setLanguage($langCode){		return $this->source->setLanguage($langCode);	}
	public function getLanguage(){				return $this->source->getLanguage();			}
	public function getAllLanguages(){			return $this->source->getAllLanguages();		}
	public function isLanguage($langCode){		return $this->source->isLanguage($langCode);	}
	
	//	NOTE: This method is used to temporarily change the language of the translator, but not lose the original
	//	NOTE: should change this method to use like a stack of plates
	public function pushLanguage($langCode)
	{
		if(is_string($langCode) && strlen($langCode)){
			$this->stackLanguage = $this->source->getLanguage();
			$this->source->setLanguage($langCode);
		}
	}

	//	NOTE: Then after you've done your "thing" you can swap it back out.
	//	NOTE: should change this method to use like a stack of plates
	public function popLanguage()
	{
		if(is_string($this->stackLanguage) && strlen($this->stackLanguage)){
			$this->source->setLanguage($this->stackLanguage);
			$this->stackLanguage = false;
		}
	}

	/********************************************************************************
	 *	TRANSLATOR METHODS
	********************************************************************************/
	public function setLocation($location){				return $this->source->setLocation($location);	}
	public function load(){								return $this->source->load();					}
	public function translate($k,$l=NULL){				return $this->source->translate($k,$l);		}
	public function learn($k,$v,$l=NULL){					return $this->source->learn($k,$v,$l);			}
	public function forget($k,$l=NULL){					return $this->source->forget($k,$l);			}
	public function searchKey($k,$s=false,$l=NULL){		return $this->source->searchKey($k,$s,$l);		}
	public function searchValue($v,$s=false,$l=NULL){	return $this->source->searchValue($v,$s,$l);	}
	public function getKeyList($l=NULL){					return $this->source->getKeyList($l);			}
	public function getValueList($l=NULL){				return $this->source->getValueList($l);		}
	public function getList($l=NULL){						return $this->source->getList($l);				}
	public function updateKey($k,$nk,$l=NULL){			return $this->source->updateKey($k,$nk,$l);	}
	
	/********************************************************************************
	 *	IMPORT TRANSLATION METHODS
	********************************************************************************/
	public function importKeyedArray($array)
	{
		foreach($array as $key=>$string){
			if(is_string($key) && is_string($value)){
				$this->learn($key,$string);	
			}
		}
	}
	
	public function importArray($array,$keyIndex,$valueIndex)
	{
		foreach($array as $translation){
			if(isset($translation[$keyIndex]) && isset($translation[$valueIndex]))
			{
				$this->learn($translation[$keyIndex],$translation[$valueIndex]);
			}
		}
	}
	
	public function importSource($source)
	{
		$list = $source->getKeyList();

		if(!empty($list)) foreach($list as $key){
			$value = $source->translate($key);

			if(is_string($key) && is_string($value)){
				$this->learn($key,$value);
			}
		}
	}
}