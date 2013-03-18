<?php 
/*******************************************************************************
 * Copyright (c) {15/03/2013} {Christopher Thomas}
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
* File: Amslib_Image.php
* Title: Amslib Image manipulation object
* Version: 2+
* Project: Amslib (antimatter studios library)
*
* Contributors/Author:
*    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
*******************************************************************************/

class Amslib_Image2
{
	protected $cache_dir		=	false;
	protected $cache_params	=	false;
	protected $image_params	=	false;
	protected $permit_ext		=	false;
	
	const ERROR_PHPGD_NOT_FOUND				= "PHP GD not found, cannot continue";
	const ERROR_NO_CACHE_DIR				= "There was no cache directory set";
	const ERROR_INVALID_IMAGE_OBJECT		= "The PHP GD Image Object was invalid";
	const ERROR_CREATE_IMAGE_OBJECT			= "The system could not create an image object to handle your request";
	const ERROR_IMAGE_OBJECT_CLOSE			= "It was not possible to close the image object";
	const ERROR_IMAGE_DIMENSIONS_INVALID	= "The dimensions of this image were not valid";
	const ERROR_RESIZE_IMAGE_OBJECT_FAILED	= "Resizing the image has failed";
	const ERROR_FILE_EXTENSION_INVALID		= "extension was invalid, permitted extensions are jpeg,jpg.png,gif";
	const ERROR_FILE_NOT_EXIST				= "The file requested cannot be found (doesn't exist?)";
	const ERROR_WRITE_DIRECTORY_NOT_EXIST	= "destination directory does not exist";
	const ERROR_WRITE_FILE_EXIST			= "file destination exists, delete original file first";
	const ERROR_CACHE_NOT_FOUND				= "The image could not be found in the cache";
	
	protected function getMIMEType($filename)
	{
		$extension = end(explode(".",$filename));
	
		switch($extension){
			case "jpeg":
			case "jpg":{	return "image/jpeg";	}break;
			case "png":{	return "image/png";	}break;
			case "gif":{	return "image/gif";	}break;
		}
	
		return false;
	}
	
	protected function getFileExtension($filename)
	{
		$extension = Amslib_File::getFileExtension($filename);
		$extension = (in_array($extension,$this->permit_ext)) ? $extension : false;
	
		if($extension !== false){
			$this->setCacheParam("mime_type",$this->getMIMEType($filename));
		}
	
		return $extension;
	}
	
	protected function setCacheParam($key,$value=NULL)
	{
		if(!is_string($key)) return NULL;
		
		$this->cache_params[$key] = $value;
	
		return $value;
	}
	
	protected function getCacheParam($key,$default=NULL)
	{
		return array_key_exists($key,$this->cache_params) ? $this->cache_params[$key] : $default;
	}
	
	protected function resetCacheParam()
	{
		$this->cache_params = array();
	}
	
	protected function setImageParam($key,$value)
	{
		if(!is_string($key)) return NULL;
		
		$this->image_params[$key] = $value;
	
		return $value;
	}
	
	protected function getImageParam($key,$default=NULL)
	{
		return array_key_exists($key,$this->image_params) ? $this->image_params[$key] : $default;
	}
	
	protected function resetImageParam()
	{
		$this->image_params = Amslib_Array::removeKeys($this->image_params,array("handle","width","height"));
	}
	
	protected function getCacheKey()
	{
		return sha1(http_build_query($this->cache_params)).".".$this->cache_params["extension"];
	}
	
	protected function getCacheFilename()
	{
		return Amslib_File::reduceSlashes($this->cache_dir."/".$this->getCacheKey());
	}
	
	protected function getDimensions()
	{
		if(!$this->getImageParam("handle")){
			return $this->setError(self::ERROR_INVALID_IMAGE_OBJECT);
		}
	
		return array(
			"width"		=>	intval($this->getImageParam("width")),
			"height"	=>	intval($this->getImageParam("height"))
		);
	}
	
	public function __construct()
	{
		if(!function_exists('imagecreatetruecolor')) {
			$this->setError(self::ERROR_PHPGD_NOT_FOUND);
		}
		
		$this->permit_ext = array("jpeg","jpg","gif","png");
		
		$this->resetCacheParam();
		$this->resetImageParam();
	}	
	
	public function setCacheDirectory($directory)
	{
		$directory = Amslib_File::reduceSlashes($directory);
		
		if(!is_dir($directory) && !file_exists($directory)){
			if(!Amslib_File::mkdir($directory)) return false;
		}
		
		$this->cache_dir = $directory;
	}
	
	public function setError($error)
	{
		$this->error = $error;
		
		return false;
	}
	
	public function getError()
	{
		return Amslib::var_dump($this->error);
	}
	
	public function clearCache()
	{
		if(!is_dir($this->cache_dir)){
			return $this->setError(self::ERROR_NO_CACHE_DIR);
		}
		
		$list = glob("$this->cache_dir/*");
		
		foreach($list as $i){
			//	Skip directories of files with .. in the filename (PREVENT HACKS)
			if(is_dir($i)) continue;
			if(strpos($i,"..")) continue;
				
			//	Just in case someone managed to write a file with .. in it (back directory)
			@unlink($f);
		}
		
		return true;
	}
	
	public function open($filename)
	{
		//	get file extension and if not valid, return false
		$ext = $this->setImageParam("extension",$this->getFileExtension($filename));
		//	if false, ERROR_FILE_EXTENSION_INVALID
		if(!$ext) return $this->setError(self::ERROR_FILE_EXTENSION_INVALID);
		
		$filename = $this->setImageParam("filename",$filename);
		
		//	By default, set the handle to false (invalid)
		$this->setImageParam("handle",false);
		
		//	If false, ERROR_FILE_NOT_EXIST
		if(!file_exists($filename)) return $this->setError(self::ERROR_FILE_NOT_EXIST);
		
		$handle = false;
		
		switch($ext){
			case "jpeg":
			case "jpg":{	$handle	=	imagecreatefromjpeg($filename);	}break;
			case "png":{	$handle	=	imagecreatefrompng($filename);	}break;
			case "gif":{	$handle	=	imagecreatefromgif($filename);	}break;
		}
		
		//	If false, ERROR_CREATE_IMAGE_OBJECT
		if(!$handle) return $this->setError(self::ERROR_CREATE_IMAGE_OBJECT);
		
		$this->setImageParam("handle",$handle);
		$this->setImageParam("width",intval(imagesx($handle)));
		$this->setImageParam("height",intval(imagesy($handle)));
		
		return true;
	}
	
	public function close()
	{
		$handle = $this->getImageParam("handle");
		
		if(!$handle) return $this->setError(self::ERROR_INVALID_IMAGE_OBJECT);
		
		imagedestroy($handle);
		
		$this->resetImageParam();
		
		return true;
	}
	
	public function maxdim($width,$height)
	{
		if(!$this->getImageParam("handle")){
			return $this->setError(self::ERROR_INVALID_IMAGE_OBJECT);
		}
		
		$d = $this->getDimensions();
		
		$width	=	intval($width);
		$height	=	intval($height);
		
		//	if failed, ERROR_IMAGE_DIMENSIONS_INVALID
		if(!$d) return $this->setError(self::ERROR_IMAGE_DIMENSIONS_INVALID);
		
		$a = $d["width"] / $d["height"];
		
		if($a > 1){
			//	wider image
			if($d["width"] != $width){
				$ratio			= $width / $d["width"];
				$d["width"]		= $d["width"] * $ratio;
				$d["height"]	= $d["height"] * $ratio;
			}
		
			if($d["height"] > $height){
				$ratio			= $height / $d["height"];
				$d["width"]		= $d["width"] * $ratio;
				$d["height"]	= $d["height"] * $ratio;
			}
		}else{
			//	taller image
		
			//	wider image
			if($d["height"] != $height){
				$ratio			= $height / $d["height"];
				$d["width"]		= $d["width"] * $ratio;
				$d["height"]	= $d["height"] * $ratio;
			}
		
			if($d["width"] > $width){
				$ratio			= $width / $d["width"];
				$d["width"]		= $d["width"] * $ratio;
				$d["height"]	= $d["height"] * $ratio;
			}
		}

		return $this->resize($d["width"],$d["height"]);
	}
	
	public function resize($width,$height)
	{
		$handle = $this->getImageParam("handle");
		
		if(!$handle){
			return $this->setError(self::ERROR_INVALID_IMAGE_OBJECT);
		}

		$width		=	intval($width);
		$height		=	intval($height);
		$tmp_image	=	imagecreatetruecolor($width,$height);
		
		//	if failed, ERROR_CREATE_IMAGE_OBJECT
		if(!$tmp_image) return $this->setError(self::ERROR_CREATE_IMAGE_OBJECT);
		
		$success = imagecopyresampled(
			$tmp_image, 
			$handle,
			0, 0,
			0, 0,
			$width, $height,
			$this->getImageParam("width"), $this->getImageParam("height")
		);
	
		//	if failed, ERROR_RESIZE_IMAGE_OBJECT_FAILED
		if(!$success) return $this->setError(self::ERROR_RESIZE_IMAGE_OBJECT_FAILED);
		
		//	if failed, ERROR_CLOSE_IMAGE_OBJECT
		if(!$this->close()) return $this->setError(self::ERROR_IMAGE_OBJECT_CLOSE);
		
		$this->setImageParam("handle",$tmp_image);
		$this->setImageParam("width",$width);
		$this->setImageParam("height",$height);
	
		return true;
	}
	
	public function readCache($filename,$returnData=false)
	{
		//	If cache is not enabled, return false
		if(!$this->cache_dir) return $this->setError(self::ERROR_NO_CACHE_DIR);
		
		//	get file extension and if not valid, return false
		$ext = $this->setCacheParam("extension",$this->getFileExtension($filename));
		if(!$ext) return $this->setError(self::ERROR_FILE_EXTENSION_INVALID);
		
		$this->setCacheParam("filename",$filename);
		$cache_filename = $this->getCacheFilename();
		
		//	does the file exist in the cache		
		if(file_exists($cache_filename)){
			//	if yes, readfile($cache) or return data
			if($returnData){
				return file_get_contents($cache_filename);
			}
			
			header("Content-Type: ".$this->getCacheParam("mime_type"));
			readfile($cache_filename);
					
			return true;
		}

		//	if no, read original file into cache, then attempt to get from cache again
		if($this->writeCacheFromFile($filename)){
			return $this->readCache($filename,$returnData);
		}
		
		//	oh well, on images for you little jimmy...go cry to your mother...
		return $this->setError(self::ERROR_CACHE_NOT_FOUND);
	}
	
	public function writeCacheFromFile($filename)
	{
		$filename	= Amslib_Website::abs($filename);
		$cache		= $this->getCacheFilename();
		
		return copy($filename,$cache) && chmod($cache,0755);
	}
	
	public function writeCacheFromImage()
	{
		$handle		= $this->getImageParam("handle");
		$filename	= $this->getImageParam("filename");
		$ext		= $this->getImageParam("extension");
		
		$this->setCacheParam("extension",$ext);
		$this->setCacheParam("filename",$filename);
		$this->setCacheParam("width",$this->getImageParam("width"));
		$this->setCacheParam("height",$this->getImageParam("height"));
		
		$cache = $this->getCacheFilename();
		
		switch($ext){
			case "jpeg":
			case "jpg":{	imagejpeg($handle,$cache);	}break;
			case "png":{	imagepng($handle,$cache);	}break;
			case "gif":{	imagegif($handle,$cache);	}break;
		};
		
		//	Write the file to a new destination, or to the browser
		return file_exists($cache) && chmod($cache,0755);
	}
}