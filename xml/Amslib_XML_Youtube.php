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
 * 	class:	Amslib_XML_Youtube
 *
 *	group:	XML
 *
 *	file:	Amslib_XML_Youtube.php
 *
 *	description:  todo, write description
 *
 * 	todo: write documentation
 *
 */
class Amslib_XML_Youtube extends Amslib_XML
{
	protected $article;
	protected $articleLoaded;

	protected $feed;

	/**
	 * 	method:	decodeFeedEntry
	 *
	 * 	todo: write documentation
	 */
	protected function decodeFeedEntry(&$feed,$parent)
	{
		$entry = array();

		foreach($parent->childNodes as $node){
			switch($node->nodeName){
				case "title":{
					$this->decodeText($entry,"title",$node);
				}break;

				case "media:group":{
					$this->decodeMediaGroup($entry,$node);
				}break;
			}
		}

		$feed["entry"][] = $entry;
	}

	/**
	 * 	method:	decodeText
	 *
	 * 	todo: write documentation
	 */
	protected function decodeText(&$node,$name,$parent)
	{
		$node[$name] = $parent->nodeValue;
	}

	/**
	 * 	method:	decodeMediaGroup
	 *
	 * 	todo: write documentation
	 */
	protected function decodeMediaGroup(&$entry,$parent)
	{
		$mediaGroup = array();

		foreach($parent->childNodes as $node){
			switch($node->nodeName){
				case "media:player":{
					$this->decodeMediaPlayer($mediaGroup,$node);
				}break;

				case "media:thumbnail":{
					$this->decodeMediaThumbnail($mediaGroup,$node);
				}break;
			}
		}

		$entry["media:group"] = $mediaGroup;
	}

	/**
	 * 	method:	decodeMediaPlayer
	 *
	 * 	todo: write documentation
	 */
	protected function decodeMediaPlayer(&$mediaGroup,$parent)
	{
		$mediaGroup["media:player"][] = $parent->getAttribute("url");
	}

	/**
	 * 	method:	decodeMediaThumbnail
	 *
	 * 	todo: write documentation
	 */
	protected function decodeMediaThumbnail(&$mediaGroup,$parent)
	{
		$mediaGroup["media:thumbnail"][] = $parent->getAttribute("url");
	}

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

			$this->feed = array();
			foreach($this->domdoc->documentElement->childNodes as $node){
				switch($node->nodeName){
					case "title":{
						$this->decodeText($this->feed,"title",$node);
					}break;

					case "entry":{
						$this->decodeFeedEntry($this->feed,$node);
					}break;
				}
			}

			$this->documentLoaded = true;

			return true;
		}

		return false;
	}

	/**
	 * 	method:	getFeed
	 *
	 * 	todo: write documentation
	 */
	public function getFeed()
	{
		return $this->feed;
	}
}