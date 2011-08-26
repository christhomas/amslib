<?php
// Author prajwala
// email  m.prajwala@gmail.com
// Date   12/04/2009
// version 1.0

//	02/06/2011: (chris.thomas@antimatter-studios.com)
//	modified loadXML -> loadHTML to stop it having problems with HTML entities

//	02/06/2011: (chris.thomas@antimatter-studios.com)
//	modified loadHTML call and removed the <div>$string</div> which was the default, I dont want my strings modified

//	08/07/2011: (chris.thomas@antimatter-studios.com)
//	added the ("1.0","UTF-8") parameters to the creation of DomDocument to fix problems with UTF-8 encoded text

class HtmlCutString{
	function __construct($string, $limit){
		// create dom element using the html string
		$this->tempDiv = new DomDocument("1.0","UTF-8");
		$this->tempDiv->loadHTML($string);
		// keep the characters count till now
		$this->charCount = 0;
		$this->encoding = 'UTF-8';
		// character limit need to check
		$this->limit = $limit;
	}

	function cut(){
		// create empty document to store new html
		$this->newDiv = new DomDocument("1.0","UTF-8");
		// cut the string by parsing through each element
		$this->searchEnd($this->tempDiv->documentElement,$this->newDiv);
		
		return $this->newDiv->saveHTML();
	}

	function deleteChildren($node) {
		while (isset($node->firstChild)) {
			$this->deleteChildren($node->firstChild);
			$node->removeChild($node->firstChild);
		}
	} 

	function searchEnd($parseDiv, $newParent){
		foreach($parseDiv->childNodes as $ele){
			// not text node
			if($ele->nodeType != 3){
				$newEle = $this->newDiv->importNode($ele,true);
				
				if(count($ele->childNodes) === 0){
					$newParent->appendChild($newEle);
					continue;
				}
			
				$this->deleteChildren($newEle);
				$newParent->appendChild($newEle);
				$res = $this->searchEnd($ele,$newEle);
			
				if($res) return $res; 
				else{
					continue;
				}
			}
			
			// the limit of the char count reached
			
			if(mb_strlen($ele->nodeValue,$this->encoding) + $this->charCount >= $this->limit){
				$newEle = $this->newDiv->importNode($ele);
				$newEle->nodeValue = mb_substr($newEle->nodeValue,0, $this->limit - $this->charCount);
				$newParent->appendChild($newEle);
				return true;
			}
			
			$newEle = $this->newDiv->importNode($ele);
			$newParent->appendChild($newEle);
			$this->charCount += mb_strlen($newEle->nodeValue,$this->encoding);
		}
		
		return false;
	}
}

function cut_html_string($string, $limit){
	$output = new HtmlCutString($string, $limit);
	return $output->cut();
}