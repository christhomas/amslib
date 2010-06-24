<?php
class Amslib_Image
{
	protected $images;
	protected $allowedTypes;
	protected $error;

	const ERROR_PHPGD_NOT_FOUND = "PHP GD not found, cannot continue";
	const ERROR_CREATE_IMAGE_OBJECT = "The system could not create an image object to handle your request";
	const ERROR_FILE_EXTENSION_INVALID = "extension was invalid, permitted extensions are jpeg,jpg.png,gif";
	const ERROR_WRITE_DIRECTORY_NOT_EXIST = "destination directory does not exist";
	const ERROR_WRITE_FILE_EXIST = "file destination exists, delete original file first";

	protected function setError($error)
	{
		if(is_string($error)){
			$this->error = "ERROR: $error\n";
		}
	}

	protected function setMIMEType($filename)
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

	protected function testWriteLocation($destination)
	{
		$directory = dirname($destination);

		if(!is_dir($directory)){
			$this->setError(self::ERROR_WRITE_DIRECTORY_NOT_EXIST);
			return false;
		}

		if(file_exists($destination)){
			$this->setError(self::ERROR_WRITE_FILE_EXIST);
			return false;
		}

		return $destination;
	}

	protected function scaleDimension($d1,$d2,$d3)
	{
		return $d3 * ($d1 / $d2);
	}

	public function __construct()
	{
		if(!function_exists('imagecreatetruecolor')) {
			$this->setError(self::ERROR_PHPGD_NOT_FOUND);
		}

		$this->cache		=	false;
		$this->images		=	array();
		$this->allowedTypes	=	array("jpeg","jpg","gif","png");
	}

	public function enableCache($location)
	{
		$this->cache = $this->getAbsoluteLocation($location);
	}

	public function clearCache()
	{
		$files = glob("$location/*");

		foreach($files as $f){
			//	Just in case someone managed to write a file with .. in it (back directory)
			@unlink(str_replace("..","",$f));
		}
	}

	public function getFromCache($parameters)
	{
		//	If caching is disabled, or the source file is not found, return false
		if(!$this->cache) return false;
		if(!isset($parameters["image"])) return false;

		$parameters["cache"]	=	true;
		$filename				=	$this->create($parameters,false);

		//	check cache file exists
		if($filename){
			$data			=	$this->images[$filename];
			$data["cache"]	=	true;

			return $filename;
		}

		//	The attempt to open the cache file failed, so close off any used resources
		$this->close($filename);

		return false;
	}

	public function getCacheFilename($filename)
	{
		return str_replace("//","/",$this->cache."/".$filename);
	}

	public function getAbsoluteLocation($filename)
	{
		$root		=	Amslib_Filesystem::documentRoot();
		$filename	=	str_replace($root,"",$filename);
		return str_replace("//","/","$root/$filename");
	}

	public function create($parameters,$overwrite=false)
	{
		//	Make the path absolute and obtain a unique name for this requested file
		$parameters["image"]	=	$this->getAbsoluteLocation($parameters["image"]);
		//	Get the extension for this file
		$extension				=	end(explode(".",strtolower($parameters["image"])));
		$uniqueName				=	sha1(http_build_query($parameters)).".$extension";

		//	Obtain the cache name, or the disk filename
		//	The cache name is the sha1 of the unique name, stored inside the cache directory
		if(isset($parameters["cache"])){
			$filename = $this->getCacheFilename($uniqueName);
		}else{
			$filename = $parameters["image"];
		}

		if(!file_exists($filename)) return false;

		//	If the file exists and overwrite is false, you can't proceed
		if($overwrite == false && isset($this->images[$filename])) return false;

		//	Check the extension is valid and the file exists on disk
		if(in_array($extension,$this->allowedTypes))
		{
			$handle = false;

			//	Open and obtain a handle for the image
			switch($extension){
				case "jpeg":
				case "jpg":{	$handle	=	imagecreatefromjpeg($filename);		}break;
				case "png":{	$handle	=	imagecreatefrompng($filename);		}break;
				case "gif":{	$handle	=	imagecreatefromgif($filename);		}break;
			}

			//	If valid, construct a structure to hold the data about the opened file
			if($handle){
				$this->images[$uniqueName] = array(
					"handle"	=>	$handle,
					"file"		=>	$parameters["image"],
					"width"		=>	imagesx($handle),
					"height"	=>	imagesy($handle),
					"mime"		=>	$this->setMIMEType($filename),
					"cache"		=>	false
				);

				return $uniqueName;
			}else{
				$this->setError(self::ERROR_CREATE_IMAGE_OBJECT);
			}
		}else{
			$this->setError(self::ERROR_FILE_EXTENSION_INVALID);
		}

		return false;
	}

	public function maxdim($filename,$width,$height)
	{
		$d = $this->getDimensions($filename);

		if($d){
			if($d["width"] > $width){
				$ratio			=	$width / $d["width"];
				$d["width"]		*=	$ratio;
				$d["height"]	*=	$ratio;
			}

			if($d["height"] > $height){
				$ratio			=	$height / $d["height"];
				$d["width"]		*=	$ratio;
				$d["height"]	*=	$ratio;
			}
			return $this->resize($filename,$d["width"],$d["height"]);
		}
		return false;
	}

	public function resize($filename,$width,$height)
	{
		if(!isset($this->images[$filename])) return false;

		$width		=	(int)$width;
		$height		=	(int)$height;
		$image		=	$this->images[$filename];
		$tmp_image	=	imagecreatetruecolor($width,$height);

		if($tmp_image){
			$success = imagecopyresampled(
				$tmp_image, $image["handle"],
				0, 0,
				0, 0,
				$width, $height,
				$image["width"],$image["height"]
			);

			if($success){
				$this->close($filename);
				$image["handle"]	=	$tmp_image;
				$image["width"]		=	$width;
				$image["height"]	=	$height;
				$this->images[$filename] = $image;

				return $filename;
			}
		}

		$this->setError(self::ERROR_CREATE_IMAGE_OBJECT);

		return $filename;
	}

	public function crop($filename,$sx,$sy,$dx,$dy,$sw,$sh,$dw,$dh)
	{
		if(!isset($this->images[$filename])) return false;

		$sx			=	(int)$sx;
		$sy			=	(int)$sy;
		$dx			=	(int)$dx;
		$dy			=	(int)$dy;
		$sw			=	(int)$sw;
		$sh			=	(int)$sh;
		$dw			=	(int)$dw;
		$dh			=	(int)$dh;
		$image		=	$this->images[$filename];
		$tmp_image	=	imagecreatetruecolor($dw,$dh);

		if($tmp_image){
			$success = imagecopyresampled(
				$tmp_image,$image["handle"],
				$dx,$dy,$sx,$sy,
				$dw,$dh,$sw,$sh
			);

			if($success){
				$this->close($filename);
				$image["handle"]	=	$tmp_image;
				$image["width"]		=	$dw;
				$image["height"]	=	$dh;
				$this->images[$filename] = $image;

				return $filename;
			}
		}

		$this->setError(self::ERROR_CREATE_IMAGE_OBJECT);

		return false;
	}

	public function resizeToWidth($filename,$width)
	{
		if(!isset($this->images[$filename])) return false;

		$image	=	$this->images[$filename];
		$height	=	$this->scaleDimension($width,$image["width"],$image["height"]);

		return $this->resize($filename,$width,$height);
	}

	public function resizeToHeight($filename,$height)
	{
		if(!isset($this->images[$filename])) return false;

		$image	=	$this->images[$filename];
		$width	=	$this->scaleDimension($height,$image["height"],$image["width"]);

		return $this->resize($filename,$width,$height);
	}

	public function scale($filename,$scale)
	{
		if(!isset($this->images[$filename])) return false;

		$image	=	$this->images[$filename];

		$width	=	$image["width"]*$scale/100;
		$height	=	$image["height"]*$scale/100;

		return $this->resize($width,$height);
	}

	public function getDimensions($filename)
	{
		if(!isset($this->images[$filename])) return false;

		return array(
			"width"		=>	$this->images[$filename]["width"],
			"height"	=>	$this->images[$filename]["height"]
		);
	}

	public function cache($filename)
	{
		if(!$this->cache)						return false;
		if(!isset($this->images[$filename]))	return false;

		$image = $this->images[$filename];

		//	File is cached, just read it out and return
		if($image["cache"]) return true;

		//	Grab the extension and test whether it's valid or not
		$extension = end(explode(".",$image["file"]));
		if(in_array($extension,$this->allowedTypes))
		{
			//	If cache is enabled, set the output to be a file
			$destination = $this->getCacheFilename($filename);

			switch($extension){
				case "jpeg":
				case "jpg":{	imagejpeg($image["handle"],$destination);		}break;
				case "png":{	imagepng($image["handle"],$destination);		}break;
				case "gif":{	imagegif($image["handle"],$destination);		}break;
			};

			return true;
		}

		$this->setError(self::ERROR_FILE_EXTENSION_INVALID);

		return false;
	}

	public function write($filename)
	{
		if(!isset($this->images[$filename])) return false;

		$image = $this->images[$filename];

		//	File is cached, just read it out and return
		if($image["cache"]){
			readfile($filename);

			return true;
		}

		//	Grab the extension and test whether it's valid or not
		$extension = end(explode(".",$image["file"]));
		if(in_array($extension,$this->allowedTypes))
		{
			//	If cache is enabled, set the output to be a file
			$destination = ($this->cache) ? $this->getCacheFilename($filename) : NULL;

			switch($extension){
				case "jpeg":
				case "jpg":{	imagejpeg($image["handle"],$destination);		}break;
				case "png":{	imagepng($image["handle"],$destination);		}break;
				case "gif":{	imagegif($image["handle"],$destination);		}break;
			};

			//	If cache is enabled, you have to output a copy now to the browser
			if($this->cache){
				chmod($destination,0777);
				readfile($destination);
			}

			return true;
		}

		$this->setError(self::ERROR_FILE_EXTENSION_INVALID);

		return false;
	}

	public function writeToDisk($filename,$destination)
	{
		if(!isset($this->images[$filename])) return false;

		$image = $this->images[$filename];

		$destination = $this->testWriteLocation($destination);
		if(!$destination) return false;

		//	Image is cached, just copy it to the new location
		if($image["cache"]){
			copy($filename,$destination);
			chmod($destination,0777);

			return true;
		}

		//	Grab the extension and test whether it's valid or not
		$extension = end(explode(".",$destination));
		if(in_array($extension,$this->allowedTypes)){
			switch($extension){
				case "jpeg":
				case "jpg":{	imagejpeg($image["handle"],$destination);		}break;
				case "png":{	imagepng($image["handle"],$destination);		}break;
				case "gif":{	imagegif($image["handle"],$destination);		}break;
			};

			//	Write the file to a new destination, or to the browser
			if(file_exists($destination)){
				chmod($destination,0777);

				//	Write a copy of this file into the cache
				if($this->cache){
					$cacheName = $this->getCacheFilename($filename,$extension);
					copy($destination,$cacheName);
					chmod($cacheName,0777);
				}

				return true;
			}
		}

		$this->setError(self::ERROR_FILE_EXTENSION_INVALID);

		return false;
	}

	public function close($filename=NULL)
	{
		if(isset($this->images[$filename])){
			imagedestroy($this->images[$filename]["handle"]);
		}else{
			foreach($this->images as $i){
				imagedestroy($i["handle"]);
			}
		}
	}

	public function getMIMEType($filename)
	{
		if(!isset($this->images[$filename])) return false;

		return $this->images[$filename]["mime"];
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Image();

		return $instance;
	}
}