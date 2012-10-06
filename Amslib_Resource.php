<?php
class Amslib_Resource
{
	static protected $stylesheet = array();
	static protected $javascript = array();

	static public function addStylesheet($id,$file,$conditional=NULL,$media=NULL)
	{
		if($id && $file){
			$media = $media ? "media='$media'" : "";
			self::$stylesheet[$id] = "<link rel='stylesheet' type='text/css' href='$file' $media />";

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

	static public function addFont($id,$font)
	{
		self::addStylesheet($id,"http://fonts.googleapis.com/css?$font");
	}

	static public function removeFont($id)
	{
		self::removeStylesheet($id);
	}

	//	DEPRECATED GOOGLE FONT METHODS
	static public function addGoogleFont($id,$font,$conditional=NULL){	self::addFont($id,$font,$conditional);	}
	static public function removeGoogleFont($id){	self::removeFont($id);}
}