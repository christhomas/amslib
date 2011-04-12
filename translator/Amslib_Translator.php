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
 * version: 2.5
 * description: A translator object which uses a language catalog like gettext but doesnt
 *		really suck by actually doing things cleverly, you can learn, forget, translate
 *		mix catalogues from different parts of projects together, it's much more powerful
 *		and does exactly the same job.
 *
 * Missing features:
 * 	-	Support for "languages" instead of loading databases directly, right now, you have to specify the file on disk
 * 	-	Right now, the last loading translation is not overloading the current one, but being saved, this should reverse
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
class Amslib_Translator
{
	protected $keyStore;

	public function __construct()
	{
		$this->keyStore = array();
	}

	public function open($database)
	{
		$this->keyStore = $database;	
	}
	
	public function close()
	{
		$this->keyStore = array();
	}

	//	NOTE: These methods have no purpose in the basic runtime memory translator
	public function sync(){}
	public function async(){}
	
	//	TODO: missing in the memory interface
	public function listAll($language=NULL){}
	
	public function getKeys()
	{
		return array_keys($this->keyStore);
	}

	public function t($key,$language=NULL)
	{
		return $this->translate($key);
	}

	//	TODO: This method has no way to translate multiple languages
	public function translate($key,$language=NULL)
	{
		if(is_string($key) && isset($this->keyStore[$key])){
			return $this->keyStore[$key];	
		}
		
		return $key;
	}
	
	public function l($key,$translation,$language=NULL)
	{
		return $this->learn($key,$translation,$language);
	}
	
	//	TODO: This method cannot learn from various languages
	public function learn($key,$translation,$language=NULL)
	{
		$this->keyStore[$key] = $translation;
	}
	
	public function f($key,$database=NULL)
	{
		$this->forget($key,$database);
	}

	//	TODO: This language has no concept of languages, so the parameter is not used
	//	NOTE: WTF??? was I drunk when I wrote this???
	public function forget($key,$language=NULL)
	{
		unset($this->keyStore[$key]);
	}

	//	TODO: missing in the memory interface
	public function getMissing(){}

	//	TODO: missing in the memory interface
	public function updateKey($old,$new,$deleteOld=true){}

	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}
?>
