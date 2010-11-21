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
		$params = substr($url,strlen($route));
		$params = trim($params,"/");

		return explode("/",$params);
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
		$this->routes = array();
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
			
			$this->calculateInverseRoutes();
		}else{
			//	TODO: Should move to using Amslib_Keystore("error") instead
			print("XML ROUTER DATABASE: '$source' FAILED TO OPEN<br/>");
		}
	}
	
	public function calculateInverseRoutes()
	{
		//	Process all the routes into the inverse, so you can do the lookup from the url as well
		$this->url = array();
		
		foreach($this->routes as $name=>$r)
		{
			foreach($r["src"] as $version=>$src){
				foreach($src as $lang=>$url){
					$this->url[$url] = array(
						"version"	=>	$version,
						"name"		=>	$name,
						"resource"	=>	$r["resource"],
						"route"		=>	$url,
						"lang"		=>	$lang,
						"data"		=>	$r["data"]
					);
				}
			}
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
	 *  IMPORTANT: not entirely sure whether this is a good idea, or just exposing a security hole
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

			return $route;
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

		//	if matches nothing, return false;
		return $route;
	}

	public function &getInstance($source=NULL)
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new Amslib_Router_Source_XML();
		
		if($source && $instance) $instance->load($source);

		return $instance;
	}
}