<?php 
class Amslib_Translator_XML extends Amslib_Translator
{
	var $__xdoc;
	var $__xpath;
	
	function __construct()
	{
		parent::__construct();
	}

	/** DEPRECATED: use load() instead **/
	function open($database,$readAll=false){ $this->load($database,$readAll); }
	
	//	TODO: readAll parameter is ignored for now
	function load($database,$readAll=false)
	{
		if(!file_exists($database)) $database = Amslib_Filesystem::find($database,true);
		
		if(!file_exists($database)){
			print("XML TRANSLATION DATABASE: '$database' DOES NOT EXIST<br/>");
		}
		
		$this->__xdoc = new DOMDocument('1.0', 'UTF-8');
		if($this->__xdoc->load($database)){
			$this->__xdoc->preserveWhiteSpace = false;
			$this->__xpath = new DOMXPath($this->__xdoc);
	
			if($readAll){
				$keys = $this->getKeys();
				foreach($keys as $k) $this->t($k);
			}	
		}else{
			die("XML TRANSLATION DATABASE: '$database' FAILED TO OPEN<br/>");
		}
	}
	
	function getKeys()
	{
		$values = $this->__xpath->query("//database/translation/attribute::name");
		
		$keys = array();
		foreach($values as $v) $keys[] = $v->value;
		
		return $keys;
	}

	//	TODO: This method has no way to translate from other languages
	function translate($expression,$language=NULL)
	{
		$t = parent::translate($expression);
		
		if($t == $expression){
			$node = $this->__xpath->query("//database/translation[@name='$expression'][1]");

			if($node->length > 0){
				$t = "";
				
				$node = $node->item(0);

				foreach($node->childNodes as $n) $t .= $this->__xdoc->saveXML($n);
				$t = trim($t);

				if(strlen($t)) $this->l($expression,$t);
				else $t = $expression;
			}
		}
		
		return $t;
	}
	
	//	TODO: This method just stores new translations in memory, doesnt write them to xml
	function learn($expression,$string,$language=NULL)
	{
		return parent::learn($expression,$string,$language);
	}
	
	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}