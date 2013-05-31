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

	static public function addStylesheet($id,$file,$conditional=NULL,$media=NULL)
	{
		if($id && $file){
			$media = $media ? "media='$media'" : "";
			self::$stylesheet[$id] = "<link rel='stylesheet' type='text/css' href='$file' $media />";

			if(is_string($conditional) && strlen($conditional)){
				self::$stylesheet[$id] = "<!--[$conditional]>".self::$stylesheet[$id]."<![endif]-->";
			}
		}
	}

	static public function getStylesheet()
	{
		return implode("\n",self::$stylesheet);
	}

	static public function removeStylesheet($id)
	{
		unset(self::$stylesheet[$id]);
	}

	static public function addJavascript($id,$file,$conditional=NULL)
	{
		if($id && $file){
			self::$javascript[$id] = "<script type='text/javascript' src='$file'></script>";

			if(is_string($conditional) && strlen($conditional)){
				self::$javascript[$id] = "<!--[$conditional]>".self::$javascript[$id]."<![endif]-->";
			}
		}
	}

	static public function getJavascript()
	{
		return implode("\n",self::$javascript);
	}

	static public function removeJavascript($id)
	{
		unset(self::$javascript[$id]);
	}

	static public function addFont($id,$font)
	{
		self::addStylesheet($id,"http://fonts.googleapis.com/css?$font");
	}

	static public function removeFont($id)
	{
		self::removeStylesheet($id);
	}

	//	DEPRECATED GOOGLE FONT METHODS
	static public function addGoogleFont($id,$font,$conditional=NULL){	self::addFont($id,$font,$conditional);	}
	static public function removeGoogleFont($id){	self::removeFont($id);}
}