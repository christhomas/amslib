<?php
class Amslib_Router_Source_XML
{
	protected $document;
	protected $xpath;
	protected $routes;
	protected $url;

	//	FIXME: This should move down a layer to a generic part
	protected function extractParameters($url,$route)
	{
		$params = Amslib::lchop($url,$route);
		
		return (!empty($params)) ? explode("/",$params) : array();
	}
	
	protected function findNodes($name,$parent)
	{
		if(!$parent || !$parent->hasChildNodes()) return array();

		$results = array();
		
		foreach($parent->childNodes as $p){
			if($p->nodeName == $name) $results[] = $p;
		}
		
		return $results;
	}
	
	protected function decodeParameters($parameters)
	{
		$parameter_list = array();
		
		foreach($parameters as $p){
			$id = $p->getAttribute("id");
			if(!$id) continue;
			
			$parameter_list[$id] = $p->nodeValue;
		}
		
		return $parameter_list;
	}
	
	protected function decodeSources($src)
	{
		$src_list = array();
		
		foreach($src as $s){
			$version = $s->getAttribute("version");
			if(!$version) $version = "default";
			
			$lang = $s->getAttribute("lang");
			if(!$lang) $lang = "all";
			
			$src_list[$version][$lang] = $s->nodeValue;
		}

		return $src_list;
	}
	
	protected function decodeResource($resource)
	{
		if($resource && count($resource) > 0){
			$resource = current($resource);

			return $resource->nodeValue;
		}
		
		return false;
	}

	public function __construct()
	{
		$this->routes	=	array();
		$this->url		=	array();
	}

	public function load($source)
	{
		$source = Amslib_Filesystem::find($source,true);
		
		if(!file_exists($source)){
			//	TODO: Should move to using Amslib_Keystore("error") instead
			die("Amslib_Router_Source_XML::load(), source file does not exist [$source]");	
		}
		
		$this->document = new DOMDocument('1.0', 'UTF-8');
		if($this->document->load($source)){
			$this->xpath = new DOMXPath($this->document);

			$paths = $this->xpath->query("//router/path");
			
			foreach($paths as $p) $this->addPath($p);
		}else{
			//	TODO: Should move to using Amslib_Keystore("error") instead
			print("XML ROUTER DATABASE: '$source' FAILED TO OPEN<br/>");
		}
	}
		
	/**
	 * method: addPath
	 * 
	 * A method to add a path to the routes configured by the system, it finds and decodes
	 * all the appropriate nodes in the xml to find all the configuration information
	 * 
	 * parameters:
	 * 	path	-	The XML Node in the amslib_router.xml called "path"
	 * 
	 * notes:
	 * 	This method is exposed as public because it is useful sometimes to store the xml configuration
	 * 	in another document, but "graft" the route onto the main router configuration as if there 
	 * 	was no difference between them.  The administration panel project is a good example of this,
	 * 	The admin_panel.xml file stores each plugin, plus the router configuration, in this case, we need
	 * 	to decode that config, but we dont want to duplicate the decoding mechanism.
	 * 
	 *  IMPORTANT:	Not entirely sure whether this is a good idea, or just exposing a security hole
	 *  
	 *  TODO:		I actually need this for dynamically allowing widgets to insert their own routes by being installed
	 *  			you can't expect the installer to know how to update the router xml, so they have to supply their own routes
	 *  			and be loaded here, so instead, we need to SUPER VALIDATE everything that passes through this method.
	 *  			Because it's ALL user defined and therefore bullshit, broken and mischevious :)
	 *  NOTE:		actually, the router xml is defined by the user too, so it should be protected in any situation
	 */
	public function addPath($path)
	{
		$name			=	$path->getAttribute("name");
		
		$src			=	$this->findNodes("src",$path);
		$resource		=	$this->findNodes("resource",$path);
		$parameter		=	$this->findNodes("parameter",$path);

		$src_list 		=	$this->decodeSources($src);
		$resource		=	$this->decodeResource($resource);
		$parameter_list	=	$this->decodeParameters($parameter);
		
		$this->routes[$name] = array(
			"src"		=>	$src_list,
			"resource"	=>	$resource,
			"data"		=>	$parameter_list
		);
		
		$this->addInversePath($path);
		
		return $this->routes[$name];
	}
	
	public function addInversePath($path)
	{
		$name	=	$path->getAttribute("name");
		$route	=	$this->routes[$name];

		foreach($route["src"] as $version=>$src){
			foreach($src as $lang=>$url){
				$this->url[$url] = array(
					"version"	=>	$version,
					"name"		=>	$name,
					"resource"	=>	$route["resource"],
					"route"		=>	$url,
					"lang"		=>	$lang,
					"data"		=>	$route["data"]
				);
			}
		}
	}

	public function getURL($url)
	{
		return (isset($this->url[$url])) ? $this->url[$url] : false;
	}

	public function getRoute($name,$version,$lang="all")
	{
		if(	isset($this->routes[$name]) &&
			isset($this->routes[$name]["src"][$version]))
		{
			$v = $this->routes[$name]["src"][$version];
			
			if(!empty($v)){
				return (isset($v[$lang])) ? $v[$lang] : current($v);
			}
		}

		return false;
	}

	//	TODO: Move this to a generic shared layer
	public function getRouteData($url)
	{
		$route = false;

		if(isset($this->url[$url])){
			$route				=	$this->url[$url];
			$route["params"]	=	array();
		}else{
			$key = array_keys($this->url);

			//	Find the longest route that matches against the requested path
			$match = "";
			foreach($key as $k){
				if(strpos($url,$k) !== false){
					if(strlen($k) > strlen($match)) $match = $k;
				}
			}

			//	if a match was found, this will be a non-empty string
			if(strlen($match)){
				$route				=	$this->url[$match];
				$route["params"]	=	$this->extractParameters($url,$route["route"]);
			}
		}

		return $route;
	}

	public function &getInstance($source=NULL)
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Router_Source_XML();
		
		if($instance && $source) $instance->load($source);

		return $instance;
	}
}