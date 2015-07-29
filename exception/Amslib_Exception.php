<?php
class Amslib_Exception extends Exception
{
	protected $data;
	
	static protected $callback = false;

	public function __construct($message,$data=array())
	{
		parent::__construct($message);

		foreach(Amslib_Array::valid($data) as $key=>$value){
			$this->setData($key,$value);
		}

		//	NOTE: I have to test whether this records the correct location in all circumstances
		$this->setData("location",basename($this->getFile())."@".$this->getLine());
		
		//	if this callback is usable, call it for the custom functionality
		if(is_callable(self::$callback)){
			call_user_func(
				//	the function to call
				self::$callback,
				//	the message, data, stack parameters to pass
				$message,
				$this->data,
				Amslib_Debug::getStackTrace("type","text")
			);
		}
	}
	
	static public function setCallback($callback)
	{
		self::$callback = $callback;
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