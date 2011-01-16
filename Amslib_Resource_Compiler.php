<?php
class Amslib_Resource_Compiler
{
	static protected $stylesheet = array();
	static protected $javascript = array();
	
	static public function addStylesheet($name,$file,$conditional=NULL)
	{
		if($name && $file){
			self::$stylesheet[$name] = "<link rel='stylesheet' type='text/css' href='$file' />";
			
			if(is_string($conditional) && strlen($conditional)){
				self::$stylesheet[$name] = "<!--[$conditional]>".self::$stylesheet[$name]."<![endif]-->";
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
	
	static public function addJavascript($name,$file,$conditional=NULL)
	{
		if($name && $file){
			self::$javascript[$name] = "<script type='text/javascript' src='$file'></script>";
			
			if(is_string($conditional) && strlen($conditional)){
				self::$javascript[$name] = "<!--[$conditional]>".self::$javascript[$name]."<![endif]-->";
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
	
	static public function addGoogleFont($name,$font)
	{
		Amslib_Resource_Compiler::addStylesheet($name,"http://fonts.googleapis.com/css?$font");
	}
	
	static public function removeGoogleFont($name)
	{
		Amslib_Resource_Compiler::removeStylesheet($name);
	}
}