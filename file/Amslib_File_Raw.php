<?php 
class Amslib_File_Raw
{
	protected $input;
	protected $output;
	protected $chunkLength;
	
	public function __construct()
	{
		$this->input		=	fopen("php://input","rb");
		$this->chunkLength	=	8192;	
	}
	
	public function isOpen()
	{
		return $this->input ? true : false;
	}
	
	public function hasData()
	{
		//	NOTE: not sure how to proceed with this yet.  perhaps I have to read the headers
		return true;	
	}
	
	public function getFilename()
	{
		//	TODO: need to extract it from the raw data like in mp3?
	}
	
	public function save($filename)
	{
		$this->output = fopen($filename,"w+b");

		//	FIXME: this 777 permission might not work in all cases where it's not allowed
		$s = chmod($filename,0777);
		
		while(feof($this->input) == false)
		{
			//	Reset the script running time limit back to 30 seconds
			set_time_limit(30);	
			
			$chunk	=	fread($this->input,$this->chunkLength);
			$length	=	strlen($chunk);
			
			if($length > 0){
				fwrite($this->output,$chunk);
			}
		} 
	}
}