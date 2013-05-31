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
 * 	class:	Amslib_XML_RSS
 *
 *	group:	XML
 *
 *	file:	Amslib_XML_RSS.php
 *
 *	description:  todo, write description
 *
 * 	todo: write documentation
 *
 */
class Amslib_XML_RSS extends Amslib_XML
{
	protected $article;
	protected $articleLoaded;
	
	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->articleLoaded = false;
	}
	
	/**
	 * 	method:	load
	 *
	 * 	todo: write documentation
	 */
	public function load($string)
	{
		
	}
	
	/**
	 * 	method:	loadURL
	 *
	 * 	todo: write documentation
	 */
	public function loadURL($url)
	{
		$this->documentLoaded = false;
		
		if($this->readURL($url)){
			$this->domdoc = new DOMDocument("1.0", "UTF-8");
			$this->domdoc->loadXML($this->getRawData());
			
			$this->xpath = new DOMXPath($this->domdoc);
			
			$this->documentLoaded = true;
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * 	method:	getArticle
	 *
	 * 	todo: write documentation
	 */
	public function getArticle($item)
	{
		return $this->setArticle($item) ? $this->article : false;
	}
	
	/**
	 * 	method:	setArticle
	 *
	 * 	todo: write documentation
	 */
	public function setArticle($item)
	{
		$this->articleLoaded = false;
		
		if($this->documentLoaded == false) return false;
		
		if($item == "first")	$item = "first()";
		if($item == "last")		$item = "last()";
		
		$node = $this->xpath->query("//item[$item]");
		
		if($node->length){
			$node = $node->item(0);
			
			$this->article				=	array();
			$this->article["title"]		=	$this->xpath->query("title",$node);
			$this->article["link"]		=	$this->xpath->query("link",$node);
			$this->article["date"]		=	$this->xpath->query("pubDate",$node);
			$this->article["creator"]	=	$this->xpath->query("dc:creator",$node);
			$this->article["category"]	=	$this->xpath->query("category",$node);
			$this->article["content"]	=	$this->xpath->query("content:encoded",$node);
			
			foreach($this->article as &$param){
				if($param) $param = $param->item(0)->nodeValue;
			}
			
			$this->articleLoaded = true;
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * 	method:	getArticleTitle
	 *
	 * 	todo: write documentation
	 */
	public function getArticleTitle()
	{
		if($this->articleLoaded == false) return false;
		
		return $this->article["title"];
	}
	
	/**
	 * 	method:	getArticleContent
	 *
	 * 	todo: write documentation
	 */
	public function getArticleContent()
	{
		if($this->articleLoaded == false) return false;
		
		return $this->article["content"];
	}
}