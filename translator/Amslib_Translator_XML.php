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
 * 	class:	Amslib_Translator_XML
 *
 *	group:	translator
 *
 *	file:	Amslib_Translator_XML.php
 *
 *	description:
 *		write description
 *
 * 	todo: 
 * 		write documentation
 *
 */
class Amslib_Translator_XML extends Amslib_Translator_Keystore
{
	protected $database;
	protected $location;
	protected $xpath;
	protected $xdoc;
	protected $error;

	public function __construct()
	{
		parent::__construct();

		$this->error = array();
		$this->xpath = false;
	}

	public function setError($error)
	{
		error_log(__METHOD__.": $error");

		$this->error[] = $error;
	}

	public function getErrors()
	{
		return $this->error;
	}

	public function setLocation($location)
	{
		$this->location = $location;
	}

	/**
	 * method: load
	 *
	 * parameters:
	 * 	$location	-	The location to load the XML database files from
	 *
	 * returns:
	 * 	Boolean true or false depending on whether it succeeded, there are some codepaths which call setError()
	 * 	this is because of serious errors which can't be handled at the moment
	 *
	 * NOTE:
	 * 	-	if language is false, you need to call setLanguage before you
	 * 		call load otherwise the source can't load the correct file
	 */
	public function load()
	{
		//	initialise the keystore to empty
		parent::reset();
		
		if($this->language)
		{
			$this->database = Amslib_Website::abs(Amslib_File::reduceSlashes("$this->location/{$this->language}.xml"));

			if(!file_exists($this->database)) $this->database = Amslib_File::find($this->database,true);

			if(!file_exists($this->database)){
				$this->setError("LOCATION: '$this->location', DATABASE '$this->database' for LANGUAGE '$this->language' DOES NOT EXIST<br/>");
			}

			$this->xdoc = new DOMDocument("1.0","UTF-8");
			if(@$this->xdoc->load($this->database)){
				$this->xdoc->preserveWhiteSpace = false;
				$this->xpath = new DOMXPath($this->xdoc);

				return true;
			}else{
				$this->setError("LOCATION: '$this->location', DATABASE: '$this->database' FAILED TO OPEN<br/>");
			}
		}

		return false;
	}

	public function translateExtended($n,$i,$l=NULL)
	{
		if(!$this->xpath){
			trigger_error(__METHOD__.": xpath was invalid, db[$this->database], loc[$this->location], lang[$this->language]");
			return $n;
		}

		$v = parent::translateExtended($n,$i,$l);

		if($v == $n){
			$node = $this->xpath->query("//database/translation[@key='$n'][1]");

			if($node->length > 0){
				$v = "";

				$node = $node->item(0);

				foreach($node->childNodes as $item) $v .= $this->xdoc->saveXML($item);
				$v = trim($v);

				//	Now cache the value read from the xml
				parent::learnExtended($n,$i,$v,$l);
			}
		}

		return $v;
	}

	//	TODO: we need to add the key/value to the xml database on disk
	public function learnExtended($n,$i,$v,$l=NULL)
	{
		return parent::learnExtended($n,$i,$v,$l);
	}

	//	TODO: do the physical remove the key from the xml database
	//	TODO: do I remove from just a single language, or all of them?
	//	TODO: perhaps remove all by default, or specify the language to single a particular xml database out.
	public function forgetExtended($n,$i,$l=NULL)
	{
		$cache	=	parent::forgetExtended($n,$i,$l);
		$xml	=	false;

		return $cache && $xml;
	}

	public function updateKeyExtended($n,$i,$nn,$l=NULL)
	{
		$this->learnExtended($nn,$i,$this->translateExtended($n,$i,$l),$l);
		$this->forgetExtended($n,$i,$l);
	}

	public function getKeyListExended($i,$l=NULL)
	{
		$list = $this->xpath->query("//database/translation/attribute::key");

		foreach($list as $k) $keys[] = $k->value;

		return $keys;
	}

	//	TODO: NOT IMPLEMENTED YET
	public function getValueListExtended($i,$l=NULL)
	{
		return array();
	}

	//	TODO: NOT IMPLEMENTED YET
	public function getListExtended($i,$l=NULL)
	{
		return array();
	}
}