<?php
class Amslib_Resource_Compiler
{
	static protected $stylesheet = array();
	static protected $javascript = array();
	
	static public function addStylesheet($id,$file,$conditional=NULL)
	{
		if($id && $file){
			self::$stylesheet[$id] = "<link rel='stylesheet' type='text/css' href='$file' />";
			
			if(is_string($conditional) && strlen($conditional)){
				self::$stylesheet[$id] = "<!--[$conditional]>".self::$stylesheet[$id]."<![endif]-->";
			}
		}
	}

	static public function getStylesheet()
	{
		return implode("\n",self::$stylesheet);
	}
	
	static public function removeStylesheet($id)
	{
		unset(self::$stylesheet[$id]);
	}
	
	static public function addJavascript($id,$file,$conditional=NULL)
	{
		if($id && $file){
			self::$javascript[$id] = "<script type='text/javascript' src='$file'></script>";
			
			if(is_string($conditional) && strlen($conditional)){
				self::$javascript[$id] = "<!--[$conditional]>".self::$javascript[$id]."<![endif]-->";
			}
		}
	}

	static public function getJavascript()
	{
		return implode("\n",self::$javascript);
	}
	
	static public function removeJavascript($id)
	{
		unset(self::$javascript[$id]);
	}
	
	static public function addGoogleFont($id,$font,$conditional=NULL)
	{
		Amslib_Resource_Compiler::addStylesheet($id,"http://fonts.googleapis.com/css?$font",$conditional);
	}
	
	static public function removeGoogleFont($id)
	{
		Amslib_Resource_Compiler::removeStylesheet($id);
	}
}