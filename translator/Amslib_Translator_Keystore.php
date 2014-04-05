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
 * 	class:	Amslib_Translator_Keystore
 *
 *	group:	translator
 *
 *	file:	Amslib_Translator_Keystore.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_Translator_Keystore extends Amslib_Translator_Source
{
	protected $inst;
	protected $config;
	protected $store;
	protected $permittedLanguage;

	/**
	 * 	method:	sanitise
	 *
	 * 	todo: write documentation
	 */
	protected function sanitise($langCode)
	{
		if(in_array($langCode,$this->permittedLanguage)){
			//	Return the language code, it was found in the permitted list
			return $langCode;
		}

		//	The code tested was not found, default to the first added language
		return current($this->permittedLanguage);
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		$this->language = false;
		$this->permittedLanguage = array();

		$this->reset();
	}

	/**
	 * 	method:	setConfig
	 *
	 * 	todo: write documentation
	 */
	public function setConfig($name,$value)
	{
		if(is_string($name) && strlen($name)){
			$this->config[$name] = $value;
		}
	}

	/**
	 * 	method:	getConfig
	 *
	 * 	todo: write documentation
	 */
	public function getConfig($name,$default=false)
	{
		return is_string($name) && strlen($name) && isset($this->config[$name])
			? $this->config[$name]
			: $default;
	}


	//	NOTE: load has no functionality, since it has nothing to load
	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	public function load(){}

	//	Calling reset will reset the keystore to empty, read to accept translations
	/**
	 * 	method:	reset
	 *
	 * 	todo: write documentation
	 */
	public function reset()
	{
		//	PROTIP: don't reset the "config" member variable here, it'll stop the translator from working :D
		$this->store				=	array();
		$this->permittedLanguage	=	array();
		$this->defaultKey			=	0;
	}

	/**
	 * 	method:	addLanguage
	 *
	 * 	todo:	write documentation
	 *
	 * 	NOTE:	I really dislike the heirarchical key structure for the storage array
	 * 			the more I think about it, the more I think it should be flatter, using composite keys
	 */
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

	/**
	 * 	method:	setLanguage
	 *
	 * 	todo: write documentation
	 */
	public function setLanguage($langCode)
	{
		$this->language = $this->sanitise($langCode);
	}

	/**
	 * 	method:	getLanguage
	 *
	 * 	todo: write documentation
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * 	method:	getAllLanguages
	 *
	 * 	todo: write documentation
	 */
	public function getAllLanguages()
	{
		return $this->permittedLanguage;
	}

	/**
	 * 	method:	isLanguage
	 *
	 * 	todo: write documentation
	 */
	public function isLanguage($langCode)
	{
		return ($langCode == $this->language);
	}

	/**
	 * 	method:	translateExtended
	 *
	 * 	todo: write documentation
	 */
	public function translateExtended($n,$i,$l=NULL)
	{
		if(!$l) $l = $this->language;

		return is_string($n) && is_numeric($i) && isset($this->store[$l][$i][$n])
			? $this->store[$l][$i][$n]
			: $n;
	}

	/**
	 * 	method:	learnExtended
	 *
	 * 	todo: write documentation
	 */
	public function learnExtended($n,$i,$v,$l=NULL)
	{
		if(!is_int($i)) return false;

		if(!$l) $l = $this->language;

		$this->store[$l][$i][$n] = $v;

		return true;
	}

	/**
	 * 	method:	forgetExtended
	 *
	 * 	todo: write documentation
	 */
	public function forgetExtended($n,$i,$l=NULL)
	{
		if(!is_int($i)) return false;

		if(!$l) $l = $this->language;

		unset($this->store[$l][$i][$n]);

		return true;
	}

	/**
	 * 	method:	updateKeyExtended
	 *
	 * 	todo: write documentation
	 */
	public function updateKeyExtended($n,$i,$nn,$l=NULL)
	{
		$this->learnExtended($nn,$i,$this->translateExtended($n,$i,$l),$l);
		$this->forgetExtended($n,$i,$l);
	}

	/**
	 * 	method:	searchKeyExtended
	 *
	 * 	todo: write documentation
	 */
	public function searchKeyExtended($n,$i,$s=false,$l=NULL)
	{
		//	TODO: not implemented yet
		//	TODO: no extended version yet
		return array();
	}

	/**
	 * 	method:	searchValueExtended
	 *
	 * 	todo: write documentation
	 */
	public function searchValueExtended($v,$i,$s=false,$l=NULL)
	{
		//	TODO: not implemented yet
		//	TODO: no extended version yet
		return array();
	}

	/**
	 * 	method:	getKeyListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getKeyListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;

		return array_keys($this->store[$l][$i]);
	}

	/**
	 * 	method:	getValueListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getValueListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;

		return array_values($this->store[$l][$i]);
	}

	/**
	 * 	method:	getListExtended
	 *
	 * 	todo: write documentation
	 */
	public function getListExtended($i,$l=NULL)
	{
		if(!$l) $l = $this->language;

		return $this->store[$l][$i];
	}
}