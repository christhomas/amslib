<?php 
class Amslib_XML_RSS extends Amslib_XML
{
	protected $article;
	protected $articleLoaded;
	
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
			
			$this->documentLoaded = true;
			
			return true;
		}
		
		return false;
	}
	
	public function getArticle($item)
	{
		return $this->setArticle($item) ? $this->article : false;
	}
	
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
	
	public function getArticleTitle()
	{
		if($this->articleLoaded == false) return false;
		
		return $this->article["title"];
	}
	
	public function getArticleContent()
	{
		if($this->articleLoaded == false) return false;
		
		return $this->article["content"];
	}
}