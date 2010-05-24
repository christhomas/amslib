<?php 
class Amslib_Translator_XMLInterface extends Amslib_Translator_MemoryInterface
{
	var $__xdoc;
	var $__xpath;
	
	function Amslib_Translator_XMLInterface()
	{
		parent::Amslib_Translator_MemoryInterface();
	}

	function open($database,$readAll=false)
	{
		$this->__xdoc = new DOMDocument('1.0', 'UTF-8');
		if(!$this->__xdoc->load($database)) die("XML FILE FAILED TO OPEN<br/>");
		
		$this->__xpath = new DOMXPath($this->__xdoc);

		if($readAll){
			$translations = $this->__xpath->query("//database/translation");
		
			foreach($translations as $name=>$t){
				$this->l($t->getAttribute("name"),$t->nodeValue);	
			}
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
}