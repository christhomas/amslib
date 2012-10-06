<?php
class Amslib_XML
{
	private $rawData;

	protected $domdoc;
	protected $xpath;
	protected $documentLoaded;
	protected $filename;

	public function __construct()
	{
		$this->domdoc			=	false;
		$this->xpath			=	false;
		$this->documentLoaded	=	false;
	}

	protected function readURL($url)
	{
		if($handle = @fopen($url,"r")){
			$this->rawData = "";

			while(!feof($handle)) $this->rawData .= fgets($handle,4096);

			fclose($handle);

			return true;
		}

		return false;
	}

	protected function getRawData()
	{
		return $this->rawData;
	}

	public function openURL($url)
	{
		$this->readURL($url);

		//	TODO: now do something with the data you read
	}

	public function openFile($filename)
	{
		//	NOTE:	Added a call to Amslib_Website::abs to fix finding the file, because in some cases,
		//			the file cannot be found. But I am not sure of the side-effects (if any) of doing this
		$this->filename = Amslib_File::find(Amslib_Website::abs($filename),true);

		$document = new DOMDocument('1.0', 'UTF-8');
		if(is_file($this->filename) && $document->load($this->filename)){
			$this->xpath = new DOMXPath($document);

			return true;
		}

		Amslib_Keystore::add("error","Amslib_XML::openFile[$filename], path[$this->filename] failed");

		return false;
	}

	public function var_dump()
	{
		$data = array();
		$data["filename"] = $this->filename;

		if(!$this->queryResults){
			$data["results"]	=	"INVALID";
			$data["length"]		=	"INVALID";
		}else{
			$data["results"]	=	array();
			$data["length"]		=	$this->length();

			foreach($this->queryResults as $r){
				$data["results"][$r->nodeName] = $r->nodeValue;
			}
		}

		return __METHOD__.Amslib::var_dump($data,true);
	}

	public function query($query)
	{
		if(!$this->xpath) return;

		$this->queryResults = $this->xpath->query($query);

		return $this->queryResults;
	}

	public function sort($method,$nodeName,$order="asc")
	{
		if(!$this->queryResults) return -1;

		$nodes = array();
		foreach($this->queryResults as $c){
			if($c->hasChildNodes()) foreach($c as $n){
				print(Amslib::var_dump($n->nodeName,true));
			}else print("No Child Nodes: ".$c->nodeName);
		}

		if(count($nodes)) $this->queryResults = $nodes;
	}

	public function limit($count=NULL)
	{
		return $count === NULL ? $this->queryResults : array_slice($this->queryResults,0,$count);
	}

	public function length()
	{
		if(!$this->queryResults) return -1;

		return $this->queryResults->length;
	}

	public function getArray($node=false,$attributes=false)
	{
		if(!$node){
			if(!$this->queryResults) return NULL;

			$node = $this->queryResults;
		}

		$data = array();

		foreach($node as $c){
			print(__METHOD__.": nodeType = ".$c->nodeType.", nodeName = ".$c->nodeName."<br/>");
			$data[$c->nodeName] = $c->hasChildNodes() && $c->nodeType == XML_ELEMENT_NODE
				? $this->getArray($c,$attributes)
				: $c->nodeValue;

			if($attributes) $data[$c->nodeName]["__attr"] = $this->getArray($c->attributes);
		}

		print(__METHOD__.": data = ".Amslib::var_dump($data,true));
		return $data;
	}
}