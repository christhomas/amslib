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
 * File: Amslib_WidgetManager.php
 * Title: Widget manager for component based development
 * Version: 1.1
 * Project: amslib
 * 
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/

class Amslib_WidgetManager
{
	protected $xdoc;
	protected $xpath;
	
	protected $defaultPath;
	protected $paths;
	
	protected $layouts;
	protected $styles;
	protected $scripts;
	
	public function __construct($path)
	{
		$this->defaultPath = $path;	
	}
	
	public function load($name,$overridePath=NULL)
	{
		if(is_array($name)){
			foreach($name as $w) $this->load($w);
		}else{
			$path = $this->defaultPath;
			if(is_string($overridePath) && is_dir($overridePath)) $path = $overridePath; 
			$xml = "$path/$name/package.xml";
			
			$this->xdoc = new DOMDocument('1.0', 'UTF-8');
			if(!$this->xdoc->load($xml)) die("XML FILE FAILED TO OPEN<br/>");
			$this->xdoc->preserveWhiteSpace = false;
			$this->xpath = new DOMXPath($this->xdoc);
			
			$this->paths[$name]		=	$path;
			$this->layouts[$name]	=	$this->xpath->query("//package/layout/file");
			$this->scripts[$name]	=	$this->xpath->query("//package/javascript/file");
			$this->styles[$name]	=	$this->xpath->query("//package/stylesheet/file");
		}
	}
	
	protected function getPath($name,$file)
	{
		$filename = $file->nodeValue;
		$path = "{$this->paths[$name]}/$name/$filename";
		if(!file_exists($path)) $path = amslib::findPath($filename)."/$filename"; 
		return $path;
	}
	
	public function getStylesheets()
	{
		$styles = "";
		foreach($this->styles as $name=>$s)
		{
			for($a=0;$a<$s->length;$a++){
				$path = $this->getPath($name,$s->item($a));
				if($path != false){
					$styles .= "<link rel='stylesheet' type='text/css' href='$path' />";
				}
			}
		}
		
		return $styles;
	}
	
	public function getJavascripts()
	{
		$scripts = "";
		foreach($this->scripts as $name=>$s)
		{
			for($a=0;$a<$s->length;$a++){
				$path = $this->getPath($name,$s->item($a));
				if($path != false){
					$scripts .= "<script type='text/javascript' src='$path'></script>";	
				}
			}
		}
		
		return $scripts;
	}
	
	public function render($name,$parameters=array())
	{
		if(isset($this->layouts[$name])){
			if($l = $this->layouts[$name]->item(0))
			{
				amslib::requireFile("{$this->paths[$name]}/$name/$l->nodeValue",$parameters);
			}
		}
	} 
}