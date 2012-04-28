<?php
class Amslib_XML_Youtube extends Amslib_XML
{
	protected $article;
	protected $articleLoaded;

	protected $feed;

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

	protected function decodeText(&$node,$name,$parent)
	{
		$node[$name] = $parent->nodeValue;
	}

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

	protected function decodeMediaPlayer(&$mediaGroup,$parent)
	{
		$mediaGroup["media:player"][] = $parent->getAttribute("url");
	}

	protected function decodeMediaThumbnail(&$mediaGroup,$parent)
	{
		$mediaGroup["media:thumbnail"][] = $parent->getAttribute("url");
	}

	public function __construct()
	{
		parent::__construct();

		$this->articleLoaded = false;
	}

	public function load($string)
	{

	}

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

	public function getFeed()
	{
		return $this->feed;
	}
}