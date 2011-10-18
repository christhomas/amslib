<?php 
class Amslib_Translator2_XML extends Amslib_Translator2_Keystore
{
	protected $database;
	protected $location;
	protected $xpath;
	protected $xdoc;
	
	public function __construct()
	{
		parent::__construct();
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
	 * 	Boolean true or false depending on whether it succeeded, there are some codepaths which call die() 
	 * 	this is because of serious errors which can't be handled at the moment
	 * 
	 * NOTE:
	 * 	-	if language is false, you need to call setLanguage before you 
	 * 		call load otherwise the source can't load the correct file
	 */
	public function load()
	{	
		if($this->language)
		{
			$this->database = Amslib_File::relative("$this->location/{$this->language}.xml");
			$this->database = Amslib_File::absolute($this->database);
			
			if(!file_exists($this->database)) $this->database = Amslib_File::find($this->database,true);
			
			if(!file_exists($this->database)){
				die(get_class($this)."::load(), LOCATION: '$this->location', DATABASE '$this->database' for LANGUAGE '$this->language' DOES NOT EXIST<br/>");
			}
			
			$this->xdoc = new DOMDocument("1.0","UTF-8");
			if(@$this->xdoc->load($this->database)){
				$this->xdoc->preserveWhiteSpace = false;
				$this->xpath = new DOMXPath($this->xdoc);
				
				return true;
			}else{
				die(get_class($this)."::load() LOCATION: '$this->location', DATABASE: '$this->database' FAILED TO OPEN<br/>");
			}
		}
		
		return false;
	}
	
	public function translate($k)
	{			
		$v = parent::translate($k);
		
		if($v == $k){
			$node = $this->xpath->query("//database/translation[@key='$k'][1]");

			if($node->length > 0){
				$v = "";
				
				$node = $node->item(0);

				foreach($node->childNodes as $n) $v .= $this->xdoc->saveXML($n);
				$v = trim($v);

				//	Now cache the value read from the xml	
				parent::learn($k,$v);
			}
		}
		
		return $v;
	}
	
	//	TODO: we need to add the key/value to the xml database on disk
	public function learn($k,$v)
	{			
		return parent::learn($k,$v);		
	}
	
	//	TODO: do the physical remove the key from the xml database
	//	TODO: do I remove from just a single language, or all of them?
	//	TODO: perhaps remove all by default, or specify the language to single a particular xml database out.
	public function forget($k)
	{
		$cache	=	parent::forget($k);
		$xml	=	false;

		return $cache && $xml;
	}
	
	public function updateKey($k,$nk)
	{
		$this->learn($nk,$this->translate($k));
		$this->forget($k);
	}
	
	public function getKeyList()
	{					
		$list = $this->xpath->query("//database/translation/attribute::key");

		$keys = array();
		foreach($list as $k) $keys[] = $k->value;
		
		return $keys;				
	}
	
	//	TODO: NOT IMPLEMENTED YET
	public function getValueList()
	{				
		return array();		
	}
	
	public function getList()
	{
		return array();
	}
}