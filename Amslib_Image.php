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

	public function __construct()
	{
		if(!function_exists('imagecreatetruecolor')) {
			$this->error(self::ERROR_PHPGD_NOT_FOUND);
		}

		$this->cache		=	false;
		$this->images		=	array();
		$this->allowedTypes	=	array("jpeg","jpg","gif","png");
	}

	public function error($error)
	{
		if(is_string($error)){
			$this->error = "ERROR: $error\n";
		}
	}

	public function enableCache($location)
	{
		$this->cache = $location;
	}

	public function create($filename,$overwrite=false)
	{
		if($overwrite == false && isset($this->images[$filename])) return false;

		$filename	=	Amslib_Filesystem::documentRoot().strtolower($filename);
		$extension	=	end(explode(".",$filename));

		if(in_array($extension,$this->allowedTypes) && file_exists($filename))
		{
			$image	=	false;
			$mime	=	false;

			switch($extension){
				case "jpeg":
				case "jpg":{
					$image	=	imagecreatefromjpeg($filename);
					$mime	=	"image/jpeg";
				}break;

				case "png":{
					$image	=	imagecreatefrompng($filename);
					$mime	=	"image/png";
				}break;

				case "gif":{
					$image	=	imagecreatefromgif($filename);
					$mime	=	"image/gif";
				}break;
			}

			if($image){
				$this->images[$filename] = array(
					"handle"	=>	$image,
					"file"		=>	$filename,
					"width"		=>	imagesx($image),
					"height"	=>	imagesy($image),
					"mime"		=>	$mime,
				);

				return $filename;
			}else{
				$this->error(self::ERROR_CREATE_IMAGE_OBJECT);
			}
		}else{
			$this->error(self::ERROR_FILE_EXTENSION_INVALID);
		}

		return false;
	}

	public function resize($filename,$width,$height)
	{
		if(!isset($this->images[$filename])) return false;

		$image = $this->images[$filename];

		$tmp_image = imagecreatetruecolor($width, $height);

		if($tmp_image){
			imagecopyresampled(
				$tmp_image, $image["handle"],
				0, 0,
				0, 0,
				$width, $height,
				$image["width"],$image["height"]
			);

			$image["handle"]	=	$tmp_image;
			$image["width"]		=	$width;
			$image["height"]	=	$height;

			$filename = "{$image["file"]}?width=$width&height=$height";
			unset($this->images[$filename]);
			$this->images[$filename] = $image;
		}else $this->error(self::ERROR_CREATE_IMAGE_OBJECT);

		return $filename;
	}

	public function crop($filename,$x,$y,$width,$height)
	{
		if(!isset($this->images[$filename])) return false;

		$image = $this->image[$filename];

		$tmp_image = imagecreatetruecolor($width,$height);

		if($tmp_image){
			imagecopyresampled(
				$tmp_image,$image["handle"],
				0, 0,
				$x, $y,
				$width, $height,
				$width, $height
			);

			$image["handle"]	=	$tmp_image;
			$image["width"]		=	$width;
			$image["height"]	=	$height;

			$filename = "{$image["file"]}?width=$width&height=$height";
			unset($this->images[$filename]);
			$this->images[$filename] = $image;
		}else $this->error(self::ERROR_CREATE_IMAGE_OBJECT);

		return $filename;
	}

	public function resizeToWidth($filename,$width)
	{
		if(!isset($this->images[$filename])) return false;

		$image	=	$this->images[$filename];

		$ratio	=	$width / $image["width"];
		$height	=	$image["height"] * $ratio;

		return $this->resize($filename,$width,$height);
	}

	public function resizeToHeight($filename,$height)
	{
		if(!isset($this->images[$filename])) return false;

		$image	=	$this->images[$filename];

		$ratio	=	$height / $image["height"];
		$width	=	$image["width"] * $ratio;

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

	public function writeFile($filename,$destination=NULL)
	{
		if(!isset($this->images[$filename])) return false;

		$image = $this->images[$filename];

		if($destination !== NULL){
			$directory = dirname($destination);

			if(!is_dir($directory)){
				$this->error(self::ERROR_WRITE_DIRECTORY_NOT_EXIST);
				return false;
			}

			if(file_exists($destination)){
				$this->error(self::ERROR_WRITE_FILE_EXIST);
				return false;
			}

			$fn = $destination;
		}else{
			$fn = $image["file"];
		}

		$extension = end(explode(".",$fn));

		if(in_array($extension,$this->allowedTypes)){
			switch($extension){
				case "jpeg":
				case "jpg":{
					imagejpeg($image["handle"],$destination);
				}break;

				case "png":{
					imagepng($image["handle"],$destination);
				}break;

				case "gif":{
					imagegif($image["handle"],$destination);
				}break;
			}
		}

		$this->error(self::ERROR_FILE_EXTENSION_INVALID);

		return false;
	}

	public function getMime($filename)
	{
		if(!isset($this->images[$filename])) return false;

		return $this->image[$filename]["mime"];
	}

	public function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Image();

		return $instance;
	}
}