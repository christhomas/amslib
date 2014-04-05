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
 * 	class:	Amslib_Translator_Source
 *
 *	group:	translator
 *
 *	file:	Amslib_Translator_Source.php
 *
 *	description:
 *		write description
 *
 * 	todo:
 * 		write documentation
 *
 */
abstract class Amslib_Translator_Source
{
	protected $name;
	protected $language;
	protected $defaultKey;

	/**
	 * 	method:	addLanguage
	 *
	 * 	todo: write documentation
	 */
	abstract public function addLanguage($langCode);

	/**
	 * 	method:	setLanguage
	 *
	 * 	todo: write documentation
	 */
	abstract public function setLanguage	($langCode);

	/**
	 * 	method:	getLanguage
	 *
	 * 	todo: write documentation
	 */
	abstract public function getLanguage();

	/**
	 * 	method:	getAllLanguages
	 *
	 * 	todo: write documentation
	 */
	abstract public function getAllLanguages();

	/**
	 * 	method:	isLanguage
	 *
	 * 	todo: write documentation
	 */
	abstract public function isLanguage($langCode);

	/**
	 * 	method:	setConfig
	 *
	 * 	todo: write documentation
	 */
	abstract public function setConfig($name,$value);

	/**
	 * 	method:	getConfig
	 *
	 * 	todo: write documentation
	 */
	abstract public function getConfig($name,$default=false);

	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	abstract public function load();

	/**
	 * 	method:	translateExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function translateExtended($n,$i,$l=NULL);

	/**
	 * 	method:	learnExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function learnExtended($n,$i,$v,$l=NULL);

	/**
	 * 	method:	forgetExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function forgetExtended($n,$i,$l=NULL);

	/**
	 * 	method:	updateKeyExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function updateKeyExtended($n,$i,$nn,$l=NULL);

	/**
	 * 	method:	searchKeyExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function searchKeyExtended($n,$i,$s=false,$l=NULL);

	/**
	 * 	method:	searchValueExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function searchValueExtended($v,$i,$s=false,$l=NULL);

	/**
	 * 	method:	getKeyListExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function getKeyListExtended($i,$l=NULL);

	/**
	 * 	method:	getValueListExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function getValueListExtended($i,$l=NULL);

	/**
	 * 	method:	getListExtended
	 *
	 * 	todo: write documentation
	 */
	abstract public function getListExtended($i,$l=NULL);

	//	These default methods are just proxies for the extended methods now, using the default key (0, zero)
	/**
	 * 	method:	translate
	 *
	 * 	todo: write documentation
	 */
	public function translate($n,$l=NULL)
	{
		return $this->translateExtended($n,$this->defaultKey,$l);
	}

	/**
	 * 	method:	learn
	 *
	 * 	todo: write documentation
	 */
	public function learn($n,$v,$l=NULL)
	{
		return $this->learnExtended($n,$this->defaultKey,$v,$l);
	}

	/**
	 * 	method:	forget
	 *
	 * 	todo: write documentation
	 */
	public function forget($n,$l=NULL)
	{
		return $this->forgetExtended($n,$this->defaultKey,$l);
	}

	/**
	 * 	method:	updateKey
	 *
	 * 	todo: write documentation
	 */
	public function updateKey($n,$nn,$l=NULL)
	{
		return $this->updateKeyExtended($n,$this->defaultKey,$nn,$l);
	}

	/**
	 * 	method:	searchKey
	 *
	 * 	todo: write documentation
	 */
	public function searchKey($n,$s=false,$l=NULL)
	{
		return $this->searchKeyExtended($n,$this->defaultKey,$s,$l);
	}

	/**
	 * 	method:	searchValue
	 *
	 * 	todo: write documentation
	 */
	public function searchValue($v,$s=false,$l=NULL)
	{
		return $this->searchValueExtended($n,$this->defaultKey,$s,$l);
	}

	/**
	 * 	method:	getKeyList
	 *
	 * 	todo: write documentation
	 */
	public function getKeyList($l=NULL)
	{
		return $this->getKeyListExtended($n,$this->defaultKey,$l);
	}

	/**
	 * 	method:	getValueList
	 *
	 * 	todo: write documentation
	 */
	public function getValueList($l=NULL)
	{
		return $this->getValueListExtended($n,$this->defaultKey,$l);
	}

	/**
	 * 	method:	getList
	 *
	 * 	todo: write documentation
	 */
	public function getList($l=NULL)
	{
		return $this->getListExtended($n,$this->defaultKey,$l);
	}

	//	NOTE: now all the shorthand versions of the method names, to keep the code "short and sweet"
	/**
	 * 	method:	t
	 *
	 * 	todo: write documentation
	 */
	public function t($n,$l=NULL)
	{
		return $this->translate($n,$l);
	}

	/**
	 * 	method:	te
	 *
	 * 	todo: write documentation
	 */
	public function te($n,$i,$l=NULL)
	{
		return $this->translateExtended($n,$i,$l);
	}

	/**
	 * 	method:	l
	 *
	 * 	todo: write documentation
	 */
	public function l($n,$v,$l=NULL)
	{
		return $this->learn($n,$v,$l);
	}

	/**
	 * 	method:	le
	 *
	 * 	todo: write documentation
	 */
	public function le($n,$i,$v,$l=NULL)
	{
		return $this->learnExtended($n,$i,$v,$l);
	}

	/**
	 * 	method:	f
	 *
	 * 	todo: write documentation
	 */
	public function f($n,$l=NULL)
	{
		return $this->forget($n,$l);
	}

	/**
	 * 	method:	fe
	 *
	 * 	todo: write documentation
	 */
	public function fe($n,$i,$l=NULL)
	{
		return $this->forgetExtended($n,$i,$l);
	}

	/**
	 * 	method:	sk
	 *
	 * 	todo: write documentation
	 */
	public function sk($n,$s=false,$l=NULL)
	{
		return $this->searchKey($n,$s,$l);
	}

	/**
	 * 	method:	ske
	 *
	 * 	todo: write documentation
	 */
	public function ske($n,$i,$s=false,$l=NULL)
	{
		return $this->searchKeyExtended($n,$i,$s,$l);
	}

	/**
	 * 	method:	sv
	 *
	 * 	todo: write documentation
	 */
	public function sv($v,$s=false,$l=NULL)
	{
		return $this->searchValue($v,$s,$l);
	}

	/**
	 * 	method:	sve
	 *
	 * 	todo: write documentation
	 */
	public function sve($v,$i,$s=false,$l=NULL)
	{
		return $this->searchValueExtended($v,$i,$s,$l);
	}
}