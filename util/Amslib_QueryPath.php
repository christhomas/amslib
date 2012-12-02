<?php
class Amslib_QueryPath
{
	static protected $qp;

	static public function qp($document=NULL, $string=NULL, $options=array())
	{
		//	we have to do this here because QueryPath has no constructor we can call, so it can't be autoloaded
		//	this class basically "fixes" that by wrapping it all up in a way that can be autoloaded
		//	it's such a hack :P
		require_once(dirname(__FILE__)."/QueryPath/QueryPath.php");

		self::$qp = qp($document,$string,$options);

		return self::$qp;
	}

	static public function get()
	{
		return self::$qp;
	}
}