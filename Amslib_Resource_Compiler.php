<?php 
class Amslib_Resource_Compiler
{
	static protected $stylesheet = array();
	static protected $javascript = array();
	
	static protected $js	=	array();
	static protected $jsc	=	array();
	static protected $jsi	=	array();
	
	static protected $cssc	=	array();
	static protected $cssm	=	array();
	static protected $cssi	=	array();
	static protected $css	=	array();
	
	static public function addStylesheet($id,$file,$conditional=NULL,$media=NULL)
	{
		if(!$id || !is_string($id) || !$file || !is_string($file)) return;
		
		if(is_string($conditional) && strlen($conditional)){
			self::$cssc[$conditional][$id] = $file;
		}else if(is_string($media) && strlen($media)){
			self::$cssm[$media][$id] = $file;
		}else if(strpos($file,"?")){
			self::$cssi[$id] = $file;
		}else{
			self::$css[$id] = $file;
		}
	}
	
	static public function compile()
	{
		self::compileStylesheet();
		self::compileJavascript();
	}
	
	static protected function compileStylesheet()
	{
		self::$stylesheet = array();
		
		$content = "";
		foreach(self::$css as $file) $content .= file_get_contents(Amslib_File::absolute($file))."/**/";
		self::$stylesheet[] = "<style type='text/css'>$content</style>";
		
		foreach(self::$cssm as $media => $list){
			foreach($list as $file) self::$stylesheet[] = "<link rel='stylesheet' type='text/css' href='$file' media='$media' />";
		}
		
		foreach(self::$cssc as $conditional => $list){
			$content = "";
			foreach($list as $file) $content .= file_get_contents(Amslib_File::absolute($file));
			self::$stylesheet[] = "<!--[$conditional]><style type='text'css'>$content</style><![endif]-->";
		}
		
		foreach(self::$cssi as $file){
			self::$stylesheet[] = "<link rel='stylesheet' type='text/css' href='$file' />";
		}
	}
	
	static protected function compileJavascript()
	{

	}
	
	static public function getStylesheet()
	{
		return implode("",self::$stylesheet);
	}
	
	static public function removeStylesheet($id)
	{
		//	TODO: This functionality is broken until I can unify the resource models
		//unset(self::$stylesheet[$id]);
	}
	
	static public function addJavascript($id,$file,$conditional=NULL)
	{
		if($id && $file){
			if(is_string($conditional) && strlen($conditional)){
				self::$jsc[$conditional][$id] = $file;
			}else if(strpos($file,"?")){
				self::$jsi[$id] = $file;
			}else{
				self::$js[$id] = $file;
			}
		}	
	}

	static public function getJavascript()
	{
		$output = "";
		
		$content = "";
		foreach(self::$js as $file) $content .= file_get_contents(Amslib_File::absolute($file));
		$output .= "<script type='text/javascript'>$content</script>";
		
		foreach(self::$jsc as $condition => $list){
			$content = "";
			foreach($list as $file) $content .= file_get_contents(Amslib_File::absolute($file));
			$output .= "<!--[$conditional]><script type='text/javascript'>$content</script><![endif]-->";
		}
		
		foreach(self::$jsi as $file){
			$output .= "<script type='text/javascript' src='$file'></script>";	
		}
		
		return $output;
	}
	
	static public function removeJavascript($id)
	{
		//	TODO: This functionality is broken until I can unify the resource models		
		//unset(self::$javascript[$id]);
	}
	
	static public function addGoogleFont($id,$font,$conditional=NULL)
	{
		Amslib_Resource::addStylesheet($id,"http://fonts.googleapis.com/css?$font",$conditional);
	}
	
	static public function removeGoogleFont($id)
	{
		Amslib_Resource::removeStylesheet($id);
	}
}