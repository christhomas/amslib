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
 * 	class:	Amslib_File_Raw
 *
 *	group:	file
 *
 *	file:	Amslib_File_Raw.php
 *
 *	title:	A php://input raw file reader for posted files
 *
 *	description:
 *		todo, write description
 *
 * 	todo:
 * 		write documentation
 *
 */
class Amslib_File_Raw
{
	protected $str_length;
	protected $pos_stream;
	protected $boundary;

	protected $input;
	protected $output;
	protected $chunkLength;

	protected $filename;
	protected $filetype;
	protected $fieldname;

	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
		$apache = new Amslib_Apache();

		$this->pos_stream	=	0;
		$this->str_length	=	$apache->getContentLength();
		$this->boundary		=	$apache->getContentBoundary();

		$this->filename		=	false;
		$this->filetype		=	false;

		$this->input		=	fopen("php://input","rb");
		$this->chunkLength	=	8192;
	}

	/**
	 * 	method:	isOpen
	 *
	 * 	todo: write documentation
	 */
	public function isOpen()
	{
		return $this->input ? true : false;
	}

	/**
	 * 	method:	hasData
	 *
	 * 	todo: write documentation
	 */
	public function hasData()
	{
		return $this->str_length && $this->boundary;
	}

	public function getLine()
	{
		$chunk = fgets($this->input, $this->chunkLength);

		$this->pos_stream += strlen($chunk);

		return $chunk;
	}

	public function getChunk()
	{
		$chunk = fread($this->input,$this->chunkLength);

		$this->pos_stream += strlen($chunk);

		return $chunk;
	}

	/**
	 * 	method:	getFilename
	 *
	 * 	todo: write documentation
	 */
	public function getFilename()
	{
		if($this->filename) return $this->filename;

		$hasBoundary = false;

		do{
			$line = trim($this->getLine());

			if(!strlen($line)){
				//	This empty line means we are ready and have the information we expect to find
				//	At this point, we know we have the filename correctly, it's not enough to just
				//	read the information and return, no, we need this blank line, so if we don't have it
				//	then it's an error
				return $this->filename;
			}else if(!$hasBoundary){
				if(strpos($line,$this->boundary) !== false){
					$hasBoundary = true;
				}
			}else if(!$this->filename){
				preg_match("/^Content-Disposition\:.*name=\"(.*)\".*filename=\"(.*)\"$/si",$line,$matches);

				if(isset($matches[1]) && strlen($matches[1])){
					$this->fieldname = $matches[1];
				}

				if(isset($matches[2]) && strlen($matches[2])){
					$this->filename = $matches[2];

					//	MSIE posts the full path of the file
					//	basename has a bug which prevents it from working with backslashes
					//	convert the \ -> / and then basename() that instead, it'll work like expected
					$this->filename = str_replace("\\","/",$this->filename);
					$this->filename = basename($this->filename);
				}
			}else if(!$this->filetype){
				preg_match("/^Content\-Type\:\s+(.*)?$/si",$line,$matches);

				if(isset($matches[1]) && strlen($matches[1])){
					$this->filetype = $matches[1];
				}
			}
			//	string(40) "------WebKitFormBoundaryAEPhbEPKjgdDaVxZ"
			//	string(73) "Content-Disposition: form-data; name="avatar"; filename="awesome-bus.jpg""
			//	string(24) "Content-Type: image/jpeg"
		}while(strlen($line));

		return false;
	}

	public function getFieldname()
	{
		return $this->fieldname;
	}

	public function getFiletype()
	{
		return $this->filetype;
	}

	/**
	 * 	method:	save
	 *
	 * 	todo: write documentation
	 */
	public function save($filename,$callback=NULL)
	{
		$this->output = fopen($filename,"w+b");

		//	FIXME: this 777 permission might not work in all cases where it's not allowed
		$s = chmod($filename,0777);

		//	NOTE: what happens when it fails? we need to have fallback code
		while(feof($this->input) == false)
		{
			//	Reset the script running time limit back to 30 seconds
			set_time_limit(30);

			$chunk	=	$this->getChunk();
			$length	=	strlen($chunk);

			if($length){
				$v = fwrite($this->output,$chunk,$length);
			}

			if($callback && is_callable($callback)){
				call_user_func($callback,$this->str_length,$this->pos_stream);
			}
		}

		//	we have to remove the boundary from the file
		$terminator = "\r\n--{$this->boundary}--\r\n";
		$this->str_written = ftell($this->output);
		ftruncate($this->output, $this->str_written - strlen($terminator));

		fclose($this->output);

		//	Both sides should equal to zero and both answers should be identical (0 == 0)
		return ($this->str_length - $this->pos_stream) == ($this->str_written - $this->pos_stream);
	}
}