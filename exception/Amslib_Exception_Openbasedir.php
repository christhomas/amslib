<?php
class Amslib_Exception_Openbasedir extends Amslib_Exception
{
	protected $data;

	public function __construct($message,$code=null)
	{
		parent::__construct($message,array(
			"code" => $code
		));

		Amslib_Debug::log(__CLASS__,$message);
	}
}