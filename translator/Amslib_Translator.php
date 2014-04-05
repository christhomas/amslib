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
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *
 *******************************************************************************/

/**
 * 	class:	Amslib_Translator
 *
 *	group:	translator
 *
 *	file:	Amslib_Translator.php
 *
 *	description:
 *		Translate strings from between a recognised input and output, it's fast,
 *		flexible and easy to use (easier than PO files anyway)
 *
 *		The translator object uses a language catalog like gettext but doesnt
 *		really suck by actually doing things cleverly, you can learn, forget, translate
 *		mix catalogues from different parts of projects together, it's much more powerful
 *		and does exactly the same job.
 *
 * 	todo: write documentation
 *
 */
class Amslib_Translator extends Amslib_Translator_Source
{
	protected	$source;
	protected	$stackLanguage;

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct($type,$name=NULL)
	{
		$this->name = $name;

		switch($type){
			case "xml":{		$this->source = new Amslib_Translator_XML();		}break;
			case "database":{	$this->source = new Amslib_Translator_Database();	}break;
			case "keystore":{	$this->source = new Amslib_Translator_Keystore();	}break;
		}
	}

	/**
	 * 	method:	getInstance
	 *
	 * 	todo: write documentation
	 */
	static public function &getInstance($type)
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self($type);

		return $instance;
	}

	/********************************************************************************
	 *	LANGUAGE METHODS
	********************************************************************************/
	/**
	 * 	method:	addLanguage
	 *
	 * 	todo: write documentation
	 */
	public function addLanguage($langCode)
	{
		return $this->source->addLanguage($langCode);
	}

	/**
	 * 	method:	setLanguage
	 *
	 * 	todo: write documentation
	 */
	public function setLanguage($langCode)
	{
		return $this->source->setLanguage($langCode);
	}

	/**
	 * 	method:	getLanguage
	 *
	 * 	todo: write documentation
	 */
	public function getLanguage()
	{
		return $this->source->getLanguage();
	}

	/**
	 * 	method:	getAllLanguages
	 *
	 * 	todo: write documentation
	 */
	public function getAllLanguages()
	{
		return $this->source->getAllLanguages();
	}

	/**
	 * 	method:	isLanguage
	 *
	 * 	todo: write documentation
	 */
	public function isLanguage($langCode)
	{
		return $this->source->isLanguage($langCode);
	}

	//	NOTE: This method is used to temporarily change the language of the translator, but not lose the original
	//	NOTE: should change this method to use like a stack of plates
	/**
	 * 	method:	pushLanguage
	 *
	 * 	todo: write documentation
	 */
	public function pushLanguage($langCode)
	{
		if(is_string($langCode) && strlen($langCode)){
			$this->stackLanguage = $this->source->getLanguage();
			$this->source->setLanguage($langCode);
		}
	}

	//	NOTE: Then after you've done your "thing" you can swap it back out.
	//	NOTE: should change this method to use like a stack of plates
	/**
	 * 	method:	popLanguage
	 *
	 * 	todo: write documentation
	 */
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
	/**
	 * 	method:	setConfig
	 *
	 * 	todo: write documentation
	 */
	public function setConfig($name,$value)
	{
		return $this->source->setConfig($name,$value);
	}

	/**
	 * 	method:	getConfig
	 *
	 * 	todo: write documentation
	 */
	public function getConfig($name,$default=false)
	{
		return $this->source->getConfig($name,$default);
	}

	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	public function load()
	{
		return $this->source->load();
	}

	/**
	 * 	method:	translate
	 *
	 * 	todo: write documentation
	 */
	public function translate($n,$l=NULL)
	{
		return $this->source->translate($n,$l);
	}

	/**
	 * 	method:	translateExtended
	 *
	 * 	todo: write documentation
	 */
	public function translateExtended($n,$i,$l=NULL)
	{
		return $this->source->translateExtended($n,$i,$l);
	}

	/**
	 * 	method:	learn
	 *
	 * 	todo: write documentation
	 */
	public function learn($n,$v,$l=NULL)
	{
		return $this->source->learn($n,$v,$l);
	}

	/**
	 * 	method:	learnExtended
	 *
	 * 	todo: write documentation
	 */
	public function learnExtended($n,$i,$v,$l=NULL)
	{
		return $this->source->learnExtended($n,$i,$v,$l);
	}

	/**
	 * 	method:	forget
	 *
	 * 	todo: write documentation
	 */
	public function forget($n,$l=NULL)
	{
		return $this->source->forget($n,$l);
	}

	/**
	 * 	method:	forgetExtended
	 *
	 * 	todo: write documentation
	 */
	public function forgetExtended($n,$i,$l=NULL)
	{
		return $this->source->forgetExtended($n,$i,$l);
	}

	/**
	 * 	method:	searchKey
	 *
	 * 	todo: write documentation
	 */
	public function searchKey($n,$s=false,$l=NULL)
	{
		return $this->source->searchKey($n,$s,$l);
	}

	/**
	 * 	method:	searchKeyExtended
	 *
	 * 	todo: write documentation
	 */
	public function searchKeyExtended($n,$i,$s=false,$l=NULL)
	{
		return $this->source->searchKeyExtended($n,$i,$s,$l);
	}

	/**
	 * 	method:	searchValue
	 *
	 * 	todo: write documentation
	 */
	public function searchValue($v,$s=false,$l=NULL)
	{
		return $this->source->searchValue($v,$s,$l);
	}

	/**
	 * 	method:	searchValueExtended
	 *
	 * 	todo: write documentation
	 */
	public function searchValueExtended($v,$i,$s=false,$l=NULL)
	{
		return $this->source->searchValueExtended($v,$i,$s,$l);
	}

	/**
	 * 	method:	getKeyList
	 *
	 * 	todo: write documentation
	 */
	public function getKeyList($l=NULL)
	{
		return $this->source->getKeyList($l);
	}

	/**
	 * 	method:	getKeyListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getKeyListExtended($i,$l=NULL)
	{
		return $this->source->getKeyListExtended($i,$l);
	}

	/**
	 * 	method:	getValueList
	 *
	 * 	todo: write documentation
	 */
	public function getValueList($l=NULL)
	{
		return $this->source->getValueList($l);
	}

	/**
	 * 	method:	getValueListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getValueListExtended($i,$l=NULL)
	{
		return $this->source->getValueListExtended($i,$l);
	}

	/**
	 * 	method:	getList
	 *
	 * 	todo: write documentation
	 */
	public function getList($l=NULL)
	{
		return $this->source->getList($l);
	}

	/**
	 * 	method:	getListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getListExtended($i,$l=NULL)
	{
		return $this->source->getListExtended($i,$l);
	}

	/**
	 * 	method:	updateKey
	 *
	 * 	todo: write documentation
	 */
	public function updateKey($n,$nk,$l=NULL)
	{
		return $this->source->updateKey($n,$nk,$l);
	}

	/**
	 * 	method:	updateKeyExtended
	 *
	 * 	todo: write documentation
	 */
	public function updateKeyExtended($n,$i,$nn,$l=NULL)
	{
		return $this->source->updateKeyExtended($n,$i,$nn,$l);
	}

	/********************************************************************************
	 *	IMPORT TRANSLATION METHODS
	********************************************************************************/
	/**
	 * 	method:	importKeyedArray
	 *
	 * 	todo: write documentation
	 */
	public function importKeyedArray($array)
	{
		foreach($array as $key=>$string){
			if(is_string($key) && is_string($value)){
				$this->learn($key,$string);
			}
		}
	}

	/**
	 * 	method:	importArray
	 *
	 * 	todo: write documentation
	 */
	public function importArray($array,$keyIndex,$valueIndex)
	{
		foreach($array as $translation){
			if(isset($translation[$keyIndex]) && isset($translation[$valueIndex]))
			{
				$this->learn($translation[$keyIndex],$translation[$valueIndex]);
			}
		}
	}

	/**
	 * 	method:	importSource
	 *
	 * 	todo: write documentation
	 */
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