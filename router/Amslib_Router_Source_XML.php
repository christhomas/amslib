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
 * File: Amslib_Router_Source_XML.php
 * Title: The XML router source reader, version 4.0 of the api/object
 * Version: 4.0
 * Project: Amslib/Router/Source
 *
 * Contributors/Author:
 *    {Christopher Thomas} - Creator - chris.thomas@antimatter-studios.com
 *******************************************************************************/
class Amslib_Router_Source_XML
{
	protected $route ;
	protected $import;

	protected function toArray($node,$recursive=true)
	{
		if(!$node || $node->count() == 0) return false;

		$data			=	array();
		$data["attr"]	=	$node->attr();
		$data["tag"]	=	$node->tag();
		$childNodes		=	$node->branch()->children();

		//	recurse to decode the child or merely store the child to process later
		foreach($childNodes as $c){
			$data["child"][] = $recursive
				? $this->toArray($c,$recursive)
				: array("tag"=>$c->tag(),"child"=>$c);
		}

		//	If the node doesn't have children, obtain the text and store as it's value
		if(count($childNodes) == 0) $data["value"] = $node->text();

		return $data;
	}

	protected function processRoute($node)
	{
		$path	=	$this->toArray($node);
		$child	=	isset($path["child"]) ? $path["child"] : false;

		if(!$path) return false;

		$data = array("javascript"=>array(),"stylesheet"=>array());
		$data["name"] = $path["attr"]["name"];
		$data["type"] = $path["tag"];

		foreach(Amslib_Array::valid($child) as $c){
			//	we array_merge the tag with the attributes here because they don't collide, plus if they do
			//	it's probably because the attribute is to override the tag information anyway
			$c = array_merge($c,$c["attr"]);
			$t = $c["tag"];
			//	remove unwanted indexes so we can assign $c directly if we want to
			unset($c["attr"],$c["tag"]);

			switch($t){
				case "src":{
					$lang = isset($c["lang"]) ? $c["lang"] : "default";
					$data[$t][$lang] = $c["value"];

					//	if there is no default, create one, all routers require a "default source"
					if(!isset($data[$t]["default"])) $data[$t]["default"] = $c["value"];
				}break;

				case "resource":{
					$data[$t] = $c["value"];
				}break;

				case "parameter":{
					if(!isset($c["id"])) continue;

					$data["route_param"][$c["id"]] = $c["value"];
				}break;

				case "handler":{
					unset($c["value"]);

					$data[$t][] = $c;
				}break;

				case "stylesheet":
				case "javascript":{
					if(!isset($c["plugin"])) $c["plugin"] = "__CURRENT_PLUGIN__";

					$data[$t][] = $c;
				}break;
			}
		}

		return $data;
	}

	public function __construct($source)
	{
		try{
			$qp = Amslib_QueryPath::qp($f=Amslib_File::find(Amslib_Website::abs($source),true));

			//	If there is no router, prevent this source from processing anything
			$this->route = $qp->branch()->find("router > *[name]");

			//	Find any callback, if one is provided
			Amslib_Router::setCallback($qp->find("router")->attr("callback"));
			
			//	Find any imports and register them for processing later
			$this->import = $qp->branch()->find("router > import");

			if($this->route->length) return $this;
		}catch(Exception $e){}

		$this->route	=	false;
		$this->import	=	false;
	}

	public function getRoutes()
	{
		if(!$this->route) return false;

		$list = array();

		foreach($this->route as $r) $list[] = $this->processRoute($r);

		return $list;
	}
	
	public function getImports()
	{
		if(!$this->import) return false;

		$list = array();
		
		foreach($this->import as $i) $list[] = $this->toArray($i);
		
		return $list;
	}
}