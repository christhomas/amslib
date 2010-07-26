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
	
	function load($database,$readAll=false)
	{
		if(!file_exists($database)){
			print("XML TRANSLATION DATABASE: '$database' DOES NOT EXIST<br/>");
		}
		
		$this->__xdoc = new DOMDocument('1.0', 'UTF-8');
		if($this->__xdoc->load($database)){
			$this->__xpath = new DOMXPath($this->__xdoc);
	
			if($readAll){
				$translations = $this->__xpath->query("//database/translation");
			
				foreach($translations as $name=>$t){
					$this->l($t->getAttribute("name"),$t->nodeValue);	
				}
			}	
		}else{
			print("XML TRANSLATION DATABASE: '$database' FAILED TO OPEN<br/>");
		}
	}

	function translate($key)
	{
		$t = parent::translate($key);
		
		if(!$t){
			$node = $this->__xpath->query("//database/translation[@name='$key']");
			
			if(count($node) == 1){
				$node = $node->item(0);
				if($node){
					$this->l($key,$node->nodeValue);
					return $node->nodeValue;
				}
			}
		}
		
		return $t;
	}
	
	function learn($key,$value,$database=NULL)
	{
		return parent::learn($key,$value,$database);
	}
	
	static public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new self();

		return $instance;
	}
}