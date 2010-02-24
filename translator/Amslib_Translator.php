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
 * version: 2.2
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
	static $interfaceType = false;

	var $__accessLayer;

	function Amslib_Translator($database=NULL,$immediateClose=false)
	{
		// TODO: Throw an exception instead, it's better
		if(self::$interfaceType == false) die("Amslib_Translator requires an interface type to continue");

		switch(self::$interfaceType){
			case "database":{
				$this->__accessLayer = new Amslib_Translator_DatabaseInterface($database,$immediateClose);
			}break;

			case "file":{
				$this->__accessLayer = new Amslib_Translator_FileInterface($database,$immediateClose);
			}break;
			
			case "memory":{
				$this->__accessLayer = new Amslib_Translator_MemoryInterface($database,$immediateClose);
			}break;
			
			case "xml":{
				$this->__accessLayer = new Amslib_Translator_XMLInterface($database,$immediateClose);
			}break;
		};
	}

	function open($database,$readAll=false)
	{
		return $this->__accessLayer->open($database,$readAll);
	}

	function close()
	{
		$this->__accessLayer->close();
	}

	function sync()
	{
		$this->__accessLayer->sync();
	}

	function async()
	{
		$this->__accessLayer->async();
	}

	function listAll($language=NULL)
	{
		return $this->__accessLayer->listAll($language);
	}

	function translate($input,$language=NULL)
	{
		return $this->t($input,$language);
	}

	function t($input,$language=NULL)
	{
		return $this->__accessLayer->t($input,$language);
	}

	function learn($input,$translation,$database=NULL)
	{
		$this->l($input,$translation,$database);
	}

	function l($input,$translation,$database=NULL)
	{
		$this->__accessLayer->l($input,$translation,$database);
	}

	function forget($input,$database=NULL)
	{
		$this->f($input,$database);
	}

	function f($input,$database=NULL)
	{
		$this->__accessLayer->f($input,$database);
	}

	function getMissing()
	{
		return $this->__accessLayer->getMissing();
	}

	function updateKey($old,$new,$deleteOld=true)
	{
		$this->__accessLayer->updateKey($old,$new,$deleteOld);
	}

	static function &getInstance($database=NULL,$immediateClose=false)
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new AntimatterTranslator($database,$immediateClose);

		return $instance;
	}
}

class Amslib_Translator_BaseAccessLayer
{
	function Amslib_Translator_BaseAccessLayer()
	{

	}
}
?>
