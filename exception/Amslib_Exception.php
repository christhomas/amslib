<?php
class Amslib_Exception extends Exception
{
	protected $data;

	public function __construct($message,$data=array())
	{
		parent::__construct($message);

		$this->data = $data;

		//	NOTE: I have to test whether this records the correct location in all circumstances
		$this->setData("location",Amslib_Debug::getCodeLocation(3));
	}

	public function setData($key,$value)
	{
		return is_string($key) && strlen($key)
			? $this->data[$key] = $value
			: NULL;
	}

	public function getData($key=NULL)
	{
		if(!is_string($key) || !strlen($key)){
			return $this->data;
		}else if(!isset($this->data[$key])){
			return NULL;
		}

		return $this->data[$key];
	}
	
	public function setMessage($message)
	{
		if(is_string($message) && strlen($message)){
			$this->message = $message;
		}
	}
}