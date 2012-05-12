<?php
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
		if($this->language)
		{
			$this->database = Amslib_Website::abs(Amslib_File::reduceSlashes("$this->location/{$this->language}.xml"));

			if(!file_exists($this->database)) $this->database = Amslib_File::find($this->database,true);

			if(!file_exists($this->database)){
				$this->setError(get_class($this)."::load(), LOCATION: '$this->location', DATABASE '$this->database' for LANGUAGE '$this->language' DOES NOT EXIST<br/>");
			}

			$this->xdoc = new DOMDocument("1.0","UTF-8");
			if(@$this->xdoc->load($this->database)){
				$this->xdoc->preserveWhiteSpace = false;
				$this->xpath = new DOMXPath($this->xdoc);

				return true;
			}else{
				$this->setError(get_class($this)."::load(), LOCATION: '$this->location', DATABASE: '$this->database' FAILED TO OPEN<br/>");
			}
		}

		return false;
	}

	public function translate($k,$l=NULL)
	{
		$v = parent::translate($k,$l);

		if($v == $k){
			$node = $this->xpath->query("//database/translation[@key='$k'][1]");

			if($node->length > 0){
				$v = "";

				$node = $node->item(0);

				foreach($node->childNodes as $n) $v .= $this->xdoc->saveXML($n);
				$v = trim($v);

				//	Now cache the value read from the xml
				parent::learn($k,$v,$l);
			}
		}

		return $v;
	}

	//	TODO: we need to add the key/value to the xml database on disk
	public function learn($k,$v,$l=NULL)
	{
		return parent::learn($k,$v,$l);
	}

	//	TODO: do the physical remove the key from the xml database
	//	TODO: do I remove from just a single language, or all of them?
	//	TODO: perhaps remove all by default, or specify the language to single a particular xml database out.
	public function forget($k,$l=NULL)
	{
		$cache	=	parent::forget($k,$l);
		$xml	=	false;

		return $cache && $xml;
	}

	public function updateKey($k,$nk,$l=NULL)
	{
		$this->learn($nk,$this->translate($k,$l),$l);
		$this->forget($k,$l);
	}

	public function getKeyList($l=NULL)
	{
		$list = $this->xpath->query("//database/translation/attribute::key");

		foreach($list as $k) $keys[] = $k->value;

		return $keys;
	}

	//	TODO: NOT IMPLEMENTED YET
	public function getValueList($l=NULL)
	{
		return array();
	}

	public function getList($l=NULL)
	{
		return array();
	}
}