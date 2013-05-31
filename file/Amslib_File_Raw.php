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
	protected $input;
	protected $output;
	protected $chunkLength;
	
	/**
	 * 	method:	__construct
	 *
	 * 	todo: write documentation
	 */
	public function __construct()
	{
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
		//	NOTE: not sure how to proceed with this yet.  perhaps I have to read the headers
		return true;	
	}
	
	/**
	 * 	method:	getFilename
	 *
	 * 	todo: write documentation
	 */
	public function getFilename()
	{
		//	TODO: need to extract it from the raw data like in mp3?
	}
	
	/**
	 * 	method:	save
	 *
	 * 	todo: write documentation
	 */
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