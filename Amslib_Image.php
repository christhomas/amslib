<?php
/*******************************************************************************
 * Copyright (c) {15/03/2008} {Christopher Thomas}
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
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *
 *******************************************************************************/

/**
 * 	class:	Amslib_Image
 *
 *	group:	core
 *
 *	file:	Amslib_Image.php
 *
 *	description: Amslib Image manipulation object
 *
 * 	todo: write documentation
 *
 */
class Amslib_Image
{
	protected $commands		=	false;
	protected $cache_dir	=	false;
	protected $cache_params	=	false;
	protected $image_params	=	false;
	protected $permit_ext	=	false;

	protected $error		=	false;
	protected $errorState	=	false;

	const COMMAND_SEQUENCE_INVALID			= "The sequence of commands given was not acceptable";
	const ERROR_PHPGD_NOT_FOUND				= "PHP GD not found, cannot continue";
	const ERROR_NO_CACHE_DIR				= "There was no cache directory set";
	const ERROR_CREATE_IMAGE_OBJECT			= "The system could not create an image object to handle your request";
	const ERROR_IMAGE_OBJECT_INVALID		= "The PHP GD Image Object was invalid";
	const ERROR_IMAGE_OBJECT_CLOSE			= "It was not possible to close the image object";
	const ERROR_IMAGE_DIMENSIONS_INVALID	= "The dimensions of this image were not valid";
	const ERROR_RESIZE_IMAGE_OBJECT_FAILED	= "Resizing the image has failed";
	const ERROR_FILE_EXTENSION_INVALID		= "extension was invalid, permitted extensions are jpeg,jpg.png,gif";
	const ERROR_FILE_NOT_EXIST				= "The file requested cannot be found (doesn't exist?)";
	const ERROR_WRITE_DIRECTORY_NOT_EXIST	= "destination directory does not exist";
	const ERROR_WRITE_FILE_EXIST			= "file destination exists, delete original file first";
	const ERROR_CACHE_NOT_FOUND				= "The image could not be found in the cache";
	const ERROR_MAXDIM_FAILED				= "maxdim method failed to process correctly";

	/**
	 * 	method:	getMIMEType
	 *
	 *	Obtain the mime type of a file based on the extension the file has, assuming it's not lying
	 *
	 *	parameters:
	 *		$filename - The filename to look for the file extension to determine the mime type to use
	 *
	 *	returns:
	 *		Boolean false if the file extension was not recognised, or a mime string for the file extension given
	 *
	 *	notes:
	 *		If the file is a GIF and the extension says .jpg, this function will not detect that
	 */
	protected function getMIMEType($filename)
	{
		$extension = end(explode(".",$filename));

		switch($extension){
			case "jpeg":
			case "jpg":{	return "image/jpeg";	}break;
			case "png":{	return "image/png";		}break;
			case "gif":{	return "image/gif";		}break;
		}

		return false;
	}

	/**
	 * 	method:	getFileExtension
	 *
	 *	Obtain the file extension from a given filename and if found, store the mime type in the cache array
	 *
	 *	parameters:
	 *		$filename - The filename to obtain the extension from
	 *
	 *	returns:
	 *		Boolean false if the extension was not found, or the extension if it was found
	 *
	 *	notes:
	 *		It's inconsistent to set the cache param for the mime type here, but not the file extension
	 *		As where this function is used, it's almost always used when setting the file extension, perhaps
	 *		This function should be called setFileExtension instead and just return the extension afterwards
	 *		Then we could hide the set cache param for the file extension inside here too and clean up the code
	 */
	protected function getFileExtension($filename)
	{
		$extension = Amslib_File::getFileExtension($filename);

		$extension = (in_array($extension,$this->permit_ext)) ? $extension : false;

		if($extension !== false){
			$this->setCacheParam("mime_type",$this->getMIMEType($filename));
		}

		return $extension;
	}

	/**
	 * 	method:	setCacheParam
	 *
	 *	Set a parameter in the cache array, which is an object of valid data used when processing images
	 *
	 *	parameters:
	 *		$key - The key to set in the cache array
	 *		$value - The value to set to the key
	 *
	 *	returns:
	 *		If the key was not a string, this function returns NULL, or the value that was set
	 */
	protected function setCacheParam($key,$value=NULL)
	{
		if(!is_string($key)) return NULL;

		$this->cache_params[$key] = $value;

		return $value;
	}

	/**
	 * 	method:	getCacheParam
	 *
	 *	Get a parameter from the cache array
	 *
	 *	parameters:
	 *		$key - The key to obtain
	 *		$default - The default value to use if the key was not present
	 *
	 *	returns:
	 *		If the value is not present, it'll return the $default value, otherwise,
	 *		the value from the cache array
	 */
	protected function getCacheParam($key,$default=NULL)
	{
		return array_key_exists($key,$this->cache_params) ? $this->cache_params[$key] : $default;
	}

	/**
	 * 	method:	resetCacheParam
	 *
	 *	Resets the cache array to completely empty, ready to fill again
	 */
	protected function resetCacheParam()
	{
		$this->cache_params = array();
	}

	/**
	 * 	method:	setImageParam
	 *
	 *	todo: write documentation
	 */
	protected function setImageParam($key,$value)
	{
		if(!is_string($key)) return NULL;

		$this->image_params[$key] = $value;

		return $value;
	}

	/**
	 * 	method:	getImageParam
	 *
	 * 	todo: write documentation
	 */
	protected function getImageParam($key,$default=NULL)
	{
		return array_key_exists($key,$this->image_params) ? $this->image_params[$key] : $default;
	}

	/**
	 * 	method:	resetImageParam
	 *
	 * 	todo: write documentation
	 */
	protected function resetImageParam()
	{
		$this->image_params = Amslib_Array::removeKeys($this->image_params,array("handle","width","height"));
	}

	/**
	 * 	method:	getCacheKey
	 *
	 * 	todo: write documentation
	 */
	protected function getCacheKey()
	{
		return sha1(http_build_query($this->cache_params)).".".$this->cache_params["extension"];
	}

	/**
	 * 	method:	getCacheFilename
	 *
	 * 	todo: write documentation
	 */
	protected function getCacheFilename()
	{
		return Amslib_File::reduceSlashes($this->cache_dir."/".$this->getCacheKey());
	}

	/**
	 * 	method:	getDimensions
	 *
	 * 	todo: write documentation
	 */
	protected function getDimensions()
	{
		if(!$this->getImageParam("handle")){
			return $this->setError(self::ERROR_IMAGE_OBJECT_INVALID);
		}

		return array(
			"width"		=>	intval($this->getImageParam("width")),
			"height"	=>	intval($this->getImageParam("height"))
		);
	}

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		if(!function_exists('imagecreatetruecolor')) {
			$this->setError(self::ERROR_PHPGD_NOT_FOUND);
		}

		$this->permit_ext = array("jpeg","jpg","gif","png");

		$this->resetCacheParam();
		$this->resetImageParam();
	}

	/**
	 * 	method:	setCacheDirectory
	 *
	 * 	todo: write documentation
	 */
	public function setCacheDirectory($directory)
	{
		$directory = Amslib_File::reduceSlashes($directory);

		if(!is_dir($directory) && !file_exists($directory)){
			if(!Amslib_File::mkdir($directory)) return false;
		}

		$this->cache_dir = $directory;
	}

	/**
	 * 	method:	setError
	 *
	 * 	todo: write documentation
	 */
	public function setError($error)
	{
		$this->error = $error;

		return false;
	}

	/**
	 * 	method:	getError
	 *
	 * 	todo: write documentation
	 */
	public function getError()
	{
		return is_array($this->error)
			? Amslib::var_dump($this->error)
			: $this->error;
	}

	/**
	 * 	method:	setErrorState
	 *
	 * 	todo: write documentation
	 */
	public function setErrorState($state)
	{
		$this->errorState = $state;
	}

	/**
	 * 	method:	getErrorState
	 *
	 * 	todo: write documentation
	 */
	public function getErrorState()
	{
		return $this->errorState;
	}

	/**
	 * 	method:	clearCache
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	setCommands
	 *
	 * 	todo: write documentation
	 */
	public function setCommands($source)
	{
		if(is_array($source)){
			$this->commands = $source;
		}elseif(is_string($source)){
			$this->commands = explode("/",trim(Amslib::rchop($source,"?"),"/"));
		}else{
			return $this->setError(self::COMMAND_SEQUENCE_INVALID);
		}

		return true;
	}

	/**
	 * 	method:	processCommands
	 *
	 * 	todo: write documentation
	 */
	public function processCommands($commands=NULL)
	{
		if(!$commands) $commands = $this->commands;

		$cache = array_shift($commands);

		if($cache != "cache") return false;

		$filename = Amslib_Website::abs();
		$filename = rtrim($filename,"/");

		$search_file = true;

		while($commands){
			$c = array_shift($commands);

			if($search_file){
				$filename .= "/$c";

				if(is_file($filename)){
					$this->open($filename);

					$search_file = false;
				}
			}else{
				switch($c){
					case "regenerate":{

					}break;

					case "maxdim":{
						$width	= array_shift($commands);
						$height	= array_shift($commands);

						$state = $this->maxdim($width,$height);

						if(!$state) return $state;
					}break;

					case "crop":{
						$x1 = array_shift($commands);
						$y1 = array_shift($commands);
						$x2 = array_shift($commands);
						$y2 = array_shift($commands);

						$state = $this->crop($x1,$y1,$x2,$y2);

						if(!$state) return $state;
					}break;

					case "show_errors":{
						$this->setErrorState(true);
					}break;
				};
			}
		}

		return true;
	}

	/**
	 * 	method:	open
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	close
	 *
	 * 	todo: write documentation
	 */
	public function close()
	{
		$handle = $this->getImageParam("handle");

		if(!$handle) return $this->setError(self::ERROR_IMAGE_OBJECT_INVALID);

		imagedestroy($handle);

		$this->resetImageParam();

		return true;
	}

	/**
	 * 	method:	maxdim
	 *
	 * 	todo: write documentation
	 */
	public function maxdim($width,$height)
	{
		if(!$this->getImageParam("handle")){
			return $this->setError(self::ERROR_IMAGE_OBJECT_INVALID);
		}

		$d = $this->getDimensions();

		$width	=	intval($width);
		$height	=	intval($height);

		//	If the width and height passed are empty, we can default to the original size, so it at least works, but not optimally
		if(!$width)		$width = $d["width"];
		if(!$height)	$height = $d["height"];

		//	if failed, ERROR_IMAGE_DIMENSIONS_INVALID
		if(!$d || !$width || !$height){
			return $this->setError(self::ERROR_IMAGE_DIMENSIONS_INVALID.", w($width):h($height)");
		}

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

	/**
	 * 	method:	crop
	 *
	 * 	todo: write documentation
	 */
	public function crop($x1,$y1,$x2,$y2)
	{
		$handle = $this->getImageParam("handle");

		if(!$handle){
			return $this->setError(self::ERROR_IMAGE_OBJECT_INVALID);
		}

		$x1 = intval($x1);
		$y1 = intval($y1);
		$x2 = intval($x2);
		$y2 = intval($y2);
		$dx = intval($x2-$x1);
		$dy = intval($y2-$y1);

		if($dx < 1 || $dy < 1){
			return $this->setError(self::ERROR_IMAGE_DIMENSIONS_INVALID);
		}

		$tmp_image	=	imagecreatetruecolor($dx,$dy);

		//	if failed, ERROR_CREATE_IMAGE_OBJECT
		if(!$tmp_image) return $this->setError(self::ERROR_CREATE_IMAGE_OBJECT);
		//imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
		$success = imagecopyresampled(
				$tmp_image,
				$handle,
				0, 0,
				$x1, $y1,
				$dx, $dy,
				$dx, $dy
		);

		//	if failed, ERROR_RESIZE_IMAGE_OBJECT_FAILED
		if(!$success) return $this->setError(self::ERROR_RESIZE_IMAGE_OBJECT_FAILED);

		//	if failed, ERROR_CLOSE_IMAGE_OBJECT
		if(!$this->close()) return $this->setError(self::ERROR_IMAGE_OBJECT_CLOSE);

		$this->setImageParam("handle",$tmp_image);
		$this->setImageParam("width",$dx);
		$this->setImageParam("height",$dy);

		return true;
	}

	/**
	 * 	method:	resize
	 *
	 * 	todo: write documentation
	 */
	public function resize($width,$height)
	{
		$handle = $this->getImageParam("handle");

		if(!$handle){
			return $this->setError(self::ERROR_IMAGE_OBJECT_INVALID);
		}

		$width		=	intval($width);
		$height		=	intval($height);

		if(!$width || !$height){
			return $this->setError(self::ERROR_IMAGE_DIMENSIONS_INVALID);
		}

		$tmp_image	=	imagecreatetruecolor($width,$height);

		//	if failed, ERROR_CREATE_IMAGE_OBJECT
		if(!$tmp_image) return $this->setError(self::ERROR_CREATE_IMAGE_OBJECT);

		/**	FUTURE IMPROVEMENT, NEEDS TESTING
		 * // preserve transparency
			if($type == "gif" or $type == "png"){
				imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
				imagealphablending($new, false);
				imagesavealpha($new, true);
			}
		 */

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

		$this->sharpen();

		return true;
	}

	/**
	 * 	method:	sharpen
	 *
	 * 	todo: write documentation
	 */
	public function sharpen()
	{
		$handle = $this->getImageParam("handle");

		if(!$handle){
			return $this->setError(self::ERROR_IMAGE_OBJECT_INVALID);
		}

		$matrix = array(
				array(-1, -1, -1),
				array(-1, 16, -1),
				array(-1, -1, -1),
		);

		$divisor = array_sum(array_map('array_sum', $matrix));
		$offset = 0;
		imageconvolution($handle, $matrix, $divisor, $offset);
	}

	/**
	 * 	method:	readCache
	 *
	 * 	todo: write documentation
	 */
	public function readCache($filename=NULL,$returnData=false)
	{
		//	If the filename was not given, attempt to get it from the cache
		//	but only if it appears to be a processed image
		//	NOTE:	if we cannot find the information inside the image params, then we didnt process it
		//			we are only really interested in working with files we've already processed
		if($filename == NULL && $this->getCacheParam("filename") == $this->getImageParam("filename")){
			$filename = $this->getCacheParam("filename");
		}

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

			//die($cache_filename);
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

	/**
	 * 	method:	writeCacheFromFile
	 *
	 * 	todo: write documentation
	 */
	public function writeCacheFromFile($filename)
	{
		$filename	= Amslib_Website::abs($filename);
		$cache		= $this->getCacheFilename();

		return copy($filename,$cache) && chmod($cache,0755);
	}

	/**
	 * 	method:	writeCacheFromImage
	 *
	 * 	todo: write documentation
	 */
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

	/**
	 * 	method:	imageCopyResampleBicubic
	 *
	 * 	todo: write documentation
	 */
	public function imageCopyResampleBicubic($dst_img, $src_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
	{
		$scaleX = ($src_w - 1) / $dst_w;
		$scaleY = ($src_h - 1) / $dst_h;

		$scaleX2 = $scaleX / 2.0;
		$scaleY2 = $scaleY / 2.0;

		$tc = imageistruecolor($src_img);

		for ($y = $src_y; $y < $src_y + $dst_h; $y++)
		{
			$sY   = $y * $scaleY;
			$siY  = (int) $sY;
			$siY2 = (int) $sY + $scaleY2;

			for ($x = $src_x; $x < $src_x + $dst_w; $x++)
			{
				$sX   = $x * $scaleX;
				$siX  = (int) $sX;
				$siX2 = (int) $sX + $scaleX2;

				if ($tc)
				{
					$c1 = imagecolorat($src_img, $siX, $siY2);
					$c2 = imagecolorat($src_img, $siX, $siY);
					$c3 = imagecolorat($src_img, $siX2, $siY2);
					$c4 = imagecolorat($src_img, $siX2, $siY);

					$r = (($c1 + $c2 + $c3 + $c4) >> 2) & 0xFF0000;
					$g = ((($c1 & 0xFF00) + ($c2 & 0xFF00) + ($c3 & 0xFF00) + ($c4 & 0xFF00)) >> 2) & 0xFF00;
					$b = ((($c1 & 0xFF)   + ($c2 & 0xFF)   + ($c3 & 0xFF)   + ($c4 & 0xFF))   >> 2);

					imagesetpixel($dst_img, $dst_x + $x - $src_x, $dst_y + $y - $src_y, $r+$g+$b);
				}
				else
				{
					$c1 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX, $siY2));
					$c2 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX, $siY));
					$c3 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX2, $siY2));
					$c4 = imagecolorsforindex($src_img, imagecolorat($src_img, $siX2, $siY));

					$r = ($c1['red']   + $c2['red']   + $c3['red']   + $c4['red']  ) << 14;
					$g = ($c1['green'] + $c2['green'] + $c3['green'] + $c4['green']) << 6;
					$b = ($c1['blue']  + $c2['blue']  + $c3['blue']  + $c4['blue'] ) >> 2;

					imagesetpixel($dst_img, $dst_x + $x - $src_x, $dst_y + $y - $src_y, $r+$g+$b);
				}
			}
		}

		return true;
	}
}