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
	protected $qp;

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * method: load
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
		if(!$this->language) return false;

		$dir		=	$this->getConfig("directory");
		$source		=	Amslib_File::reduceSlashes("$dir/{$this->language}.xml");
		$filename	=	false;

		try{
			//	NOTE: This is ugly and I believe it's a failure of Amslib_File::find() to not do this automatically
			if(file_exists($source)){
				$filename = $source;
			}else if(file_exists($f=Amslib_File::find(Amslib_Website::rel($source),true))){
				$filename = $f;
			}else if(file_exists($f=Amslib_File::find(Amslib_Website::abs($source),true))){
				$filename = $f;
			}

			if(!$filename){
				Amslib_Debug::errorlog("stack_trace","directory",$dir,"filename",$filename,"language",$this->language);
			}else{
				$this->qp = Amslib_QueryPath::qp($filename);

				return true;
			}
		}catch(Exception $e){
			Amslib_Debug::errorlog("Exception: ",$e->getMessage(),"file=",$filename,"source=",$source);
			Amslib_Debug::errorlog("stack_trace");
		}

		return false;
	}

	/**
	 * 	method:	translateExtended
	 *
	 * 	todo: write documentation
	 */
	public function translateExtended($n,$i,$l=NULL)
	{
		$v = parent::translateExtended($n,$i,$l);

		if($v == $n){
			$text = trim($this->qp->find("database translation[key='$n']")->text());

			if(strlen($text)){
				parent::learnExtended($n,$i,$text,$l);

				return $text;
			}
		}

		return $v;
	}

	/**
	 * 	method:	learnExtended
	 *
	 * 	TODO: write documentation
	 *	TODO: we need to add the key/value to the xml database on disk
	 */
	public function learnExtended($n,$i,$v,$l=NULL)
	{
		return parent::learnExtended($n,$i,$v,$l);
	}

	/**
	 * 	method:	forgetExtended
	 *
	 * 	todo: write documentation
	 *	TODO: do the physical remove the key from the xml database
	 *	TODO: do I remove from just a single language, or all of them?
	 *	TODO: perhaps remove all by default, or specify the language to single a particular xml database out.
	 */
	public function forgetExtended($n,$i,$l=NULL)
	{
		$cache	=	parent::forgetExtended($n,$i,$l);
		$xml	=	false;

		return $cache && $xml;
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
	 * 	method:	getKeyListExended
	 *
	 * 	todo: write documentation
	 */
	public function getKeyListExended($i,$l=NULL)
	{
		$list = $this->xpath->query("//database/translation/attribute::key");

		foreach($list as $k) $keys[] = $k->value;

		return $keys;
	}

	/**
	 * 	method:	getValueListExtended
	 *
	 * 	todo: write documentation
	 *	TODO: NOT IMPLEMENTED YET
	 */
	public function getValueListExtended($i,$l=NULL)
	{
		return array();
	}

	/**
	 * 	method:	getListExtended
	 *
	 * 	todo: write documentation
	 * 	TODO: NOT IMPLEMENTED YET
	 */
	public function getListExtended($i,$l=NULL)
	{
		return array();
	}
}