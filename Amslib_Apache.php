<?php 
class Amslib_Apache
{
	public function __construct()
	{
		//	Request apache headers, so you can find the content length
		$headers = apache_request_headers();
		$cl = "Content-Length";
		$ct = "Content-Type";

		//	return either the content length, or false
		if(isset($headers[$cl]) && isset($headers[$ct])){
			$totalBytes = $headers[$cl];
			
			//	Extract boundary from content type header
			$boundary = $headers[$ct];
			$boundary = explode(";",$boundary);
			$boundary = $boundary[1];
			$boundary = explode("=",$boundary);
			$boundary = $boundary[1];
			
			print("boundary length = ".strlen($boundary)."<br/>");
			print("boundary = ".$boundary."<br/>");
			
			return true;
		}
				
		return false;
	}
}