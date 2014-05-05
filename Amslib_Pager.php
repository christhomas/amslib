<?php
class Amslib_Pager
{
	static protected function setFromArray($array)
	{
		if(isset($array["pager_page"]) && isset($array["pager_length"])){
			return array($array["pager_page"],$array["pager_length"]);
		}else if(isset($array[0]) && isset($array[1])){
			return array($array[0],$array[1]);
		}

		return array(NULL,NULL);
	}

	static public function set($page,$length=NULL)
	{
		if(is_array($page)){
			list($page,$length) = self::setFromArray($page);
		}

		if(strlen($page) && strlen($length)){
			//	Don't trust them to actually send the correct page, autofix any stupid things
			if(intval($page) < 0) $page = 0;

			//	Set the filters and calculate the offset based on that
			Amslib_Keystore::set("pager_page",		intval($page));
			Amslib_Keystore::set("pager_length",	intval($length));
			Amslib_Keystore::set("pager_offset",	intval($page * $length));
		}

		return self::get();
	}

	static public function get(&$count=NULL,&$offset=NULL)
	{
		$count	=	Amslib_Keystore::get("pager_length",$count);
		$offset	=	Amslib_Keystore::get("pager_offset",$offset);

		return array($count,$offset);
	}

	static public function count($count)
	{
		$length = Amslib_Keystore::get("pager_length");

		return $length ? ceil($count / $length) : $length;
	}
}