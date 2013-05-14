<?php
class Amslib_QueryPath
{
	static protected $qp;

	static public function qp($document=NULL, $selector=NULL, $options=array())
	{
		//	we have to do this here because QueryPath has no constructor we can call, so it can't be autoloaded
		//	this class basically "fixes" that by wrapping it all up in a way that can be autoloaded
		//	it's such a hack :P
		require_once(dirname(__FILE__)."/QueryPath/QueryPath.php");

		self::$qp = qp($document,$selector,$options);

		return self::$qp;
	}
	
	static public function htmlqp($document=NULL,$selector=NULL,$options=array())
	{
		// I copied and modified the default options from the htmlqp method to provide a custom version for Amslib
		
		if(is_string($document)) $document = htmlspecialchars($document,ENT_COMPAT,"UTF-8");

		$options += array(
			'ignore_parser_warnings'	=>	true,
			'convert_to_encoding'		=>	'UTF-8',
			'convert_from_encoding'		=>	'auto',
			'use_parser'				=>	'html'
		);
		
		return self::qp($document, $selector, $options);
	}

	static public function get()
	{
		return self::$qp;
	}
}