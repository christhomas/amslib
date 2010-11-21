<?php 
class Amslib_XML
{
	private $rawData;
	
	protected $domdoc;
	protected $xpath;
	protected $documentLoaded;
	
	public function __construct()
	{
		$this->domdoc			=	false;
		$this->xpath			=	false;
		$this->documentLoaded	=	false;	
	}
	
	protected function readURL($url)
	{
		if($handle = @fopen($url,"r")){
			$this->rawData = "";
			
			while(!feof($handle)) $this->rawData .= fgets($handle,4096);
			
			fclose($handle);

			return true;
		}
		
		return false;
	}
	
	protected function getRawData()
	{
		return $this->rawData;
	}
}