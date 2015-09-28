<?php
class Amslib_Exception extends Exception
{
    protected $data;

    static protected $callback = false;

    public function __construct($message,$data=array(),$use_callback=true)
    {
        parent::__construct($message);

        //	This is so if an exception is generated using a true/false/1/0 type value
        //	it'll still process into a predictable format
        if(is_scalar($data)){
            $data = array("data" => $data);
        }

        foreach(Amslib_Array::valid($data) as $key=>$value){
            $this->setData($key,$value);
        }

        //	NOTE: I have to test whether this records the correct location in all circumstances
        $this->setData("location",basename($this->getFile())."@".$this->getLine());

        //	if this callback is usable, call it for the custom functionality
        if(self::$callback && is_callable(self::$callback) && $use_callback){
            call_user_func(self::$callback,$this,Amslib_Debug::getStackTrace("type","text"));
        }
    }

    static public function setCallback($callback)
    {
        self::$callback = $callback;
    }

    public function setData($key,$value)
    {
        return is_scalar($key) && strlen($key)
            ? $this->data[$key] = $value
            : NULL;
    }

    public function getData($key=NULL,$default=null)
    {
        if(!is_scalar($key) || !strlen($key)){
            return $this->data;
        }else if(!isset($this->data[$key])){
            return $default;
        }

        return $this->data[$key];
    }

    public function removeData($key)
    {
        $data = $this->getData($key);

        if(array_key_exists($key,$this->data)){
            unset($this->data[$key]);
        }

        return $data;
    }

    public function setMessage($message)
    {
        if(is_string($message) && strlen($message)){
            $this->message = $message;
        }
    }
}