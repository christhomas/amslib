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
 * 	class:	Amslib_Resource
 *
 *	group:	core
 *
 *	file:	Amslib_Resource.php
 *
 *	description: todo, write description
 *
 * 	todo: write documentation
 */
class Amslib_Resource
{
	static protected $stylesheet = array();
	static protected $javascript = array();
	static protected $default_position = "__common";

	static protected function validatePosition($position=NULL)
	{
		return is_string($position) && strlen($position)
			? $position
			: self::$default_position;
	}

	/**
	 * 	method:	addStylesheet
	 *
	 * 	todo: write documentation
	 */
	static public function addStylesheet($id,$file,$conditional=NULL,$media=NULL,$position=NULL)
	{
		$position = self::validatePosition($position);

		if(!isset(self::$stylesheet[$position])) self::$stylesheet[$position] = array();
		$p = &self::$stylesheet[$position];

		if($id && $file){
			$media = $media ? "media='$media'" : "";
			$p[$id] = "<link rel='stylesheet' type='text/css' href='$file' $media />";

			if(is_string($conditional) && strlen($conditional)){
				$p[$id] = "<!--[$conditional]>{$p[$id]}<![endif]-->";
			}
		}

		unset($p); // release the reference (prevents problems with loops)
	}

	/**
	 * 	method:	getStylesheet
	 *
	 * 	todo: write documentation
	 */
	static public function getStylesheet($position=NULL)
	{
		$position = self::validatePosition($position);

		if(isset(self::$stylesheet[$position])){
			return implode("\n",self::$stylesheet[$position]);
		}

		Amslib_Debug::log(
			"the requested position to obtain the stylesheets from was not valid",
			$position,
			array_keys(self::$stylesheet)
		);

		return "";
	}

	/**
	 * 	method:	removeStylesheet
	 *
	 * 	todo: write documentation
	 */
	static public function removeStylesheet($id,$position=NULL)
	{
		$position = self::validatePosition($position);

		if(!isset(self::$stylesheet[$position])){
			Amslib_Debug::log("position requested to remove stylesheet from is not exist");
		}else if(!isset(self::$stylesheet[$position][$id])){
			Amslib_Debug::log("index requested to remove from stylesheet array is not valid");
		}else{
			unset(self::$stylesheet[$position][$id]);
		}
	}

	/**
	 * 	method:	addJavascript
	 *
	 * 	todo: write documentation
	 */
	static public function addJavascript($id,$file,$conditional=NULL,$position=NULL)
	{
		$position = self::validatePosition($position);

		if(!isset(self::$javascript[$position])) self::$javascript[$position] = array();
		$p = &self::$javascript[$position];

		if($id && $file){
			$p[$id] = "<script type='text/javascript' src='$file'></script>";

			if(is_string($conditional) && strlen($conditional)){
				$p[$id] = "<!--[$conditional]>{$p[$id]}<![endif]-->";
			}
		}

		unset($p); // release the reference (prevents problems with loops)
	}

	/**
	 * 	method:	getJavascript
	 *
	 * 	todo: write documentation
	 */
	static public function getJavascript($position=NULL)
	{
		$position = self::validatePosition($position);

		if(isset(self::$javascript[$position])){
			return implode("\n",self::$javascript[$position]);
		}

		Amslib_Debug::log(
			"the requested position to obtain the javascripts from was not valid",
			$position,
			array_keys(self::$javascript)
		);

		return "";
	}

	/**
	 * 	method:	removeJavascript
	 *
	 * 	todo: write documentation
	 */
	static public function removeJavascript($id,$position=NULL)
	{
		$position = self::validatePosition($position);

		if(!isset(self::$javascript[$position])){
			Amslib_Debug::log("position requested to remove javascript from is not valid");
		}else if(!isset(self::$javascript[$position][$id])){
			Amslib_Debug::log("index requested to remove from javascript array is not valid");
		}else{
			unset(self::$javascript[$position][$id]);
		}
	}

	/**
	 * 	method:	addFont
	 *
	 * 	todo: write documentation
	 */
	static public function addFont($id,$font,$conditional=NULL,$position)
	{
		$position = self::validatePosition($position);

		self::addStylesheet($id,"http://fonts.googleapis.com/css?$font",$conditional,NULL,$position);
	}

	/**
	 * 	method:	removeFont
	 *
	 * 	todo: write documentation
	 */
	static public function removeFont($id,$position=NULL)
	{
		self::removeStylesheet($id,$position);
	}

	//	DEPRECATED GOOGLE FONT METHODS
	static public function addGoogleFont($id,$font,$conditional=NULL){	self::addFont($id,$font,$conditional);	}
	static public function removeGoogleFont($id){	self::removeFont($id);}
}
