<?php 
class Amslib_Translator_XML extends Amslib_Translator
{
	protected $xdoc;
	protected $xpath;
	
	public function __construct()
	{
		parent::__construct();
	}

	/** DEPRECATED: use load() instead **/
	public function open($database,$readAll=false){ $this->load($database,$readAll); }
	
	//	TODO: readAll parameter is ignored for now
	public function load($database,$readAll=false)
	{
		if(!file_exists($database)) $database = Amslib_File::find($database,true);
		
		if(!file_exists($database)){
			print("XML TRANSLATION DATABASE: '$database' DOES NOT EXIST<br/>");
		}
		
		$this->xdoc = new DOMDocument('1.0', 'UTF-8');
		if($this->xdoc->load($database)){
			$this->xdoc->preserveWhiteSpace = false;
			$this->xpath = new DOMXPath($this->xdoc);
	
			if($readAll){
				$keys = $this->getKeys();
				foreach($keys as $k) $this->t($k);
			}	
		}else{
			die("XML TRANSLATION DATABASE: '$database' FAILED TO OPEN<br/>");
		}
	}
	
	public function loadFromRouter()
	{
		//	FIXME:	it's wrong that we should depend on Amslib_Router_Language2 
		//			here, the value should be passed in and eliminate the dependency
		$this->load("translations/".Amslib_Router_Language2::getCode().".xml",true);
	}

	public function getKeys()
	{
		$values = $this->xpath->query("//database/translation/attribute::name");
		
		$keys = array();
		foreach($values as $v) $keys[] = $v->value;
		
		return $keys;
	}

	//	TODO: This method has no way to translate from other languages
	public function translate($key,$language=NULL)
	{
		$t = parent::translate($key);
		
		if($t == $key){
			$node = $this->xpath->query("//database/translation[@name='$key'][1]");

			if($node->length > 0){
				$t = "";
				
				$node = $node->item(0);

				foreach($node->childNodes as $n) $t .= $this->xdoc->saveXML($n);
				$t = trim($t);

				if(strlen($t)) $this->l($key,$t);
				else $t = $key;
			}
		}
		
		return $t;
	}
	
	//	TODO: This method just stores new translations in memory, doesnt write them to xml
	public function learn($key,$string,$language=NULL)
	{
		return parent::learn($key,$string,$language);
	}
	
	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}