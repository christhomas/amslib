<?php 
class Amslib_Plugin
{
	protected $xpath;
	protected $name;
	protected $packageLocation;
	protected $packageXML;
	protected $api;
	protected $routes;
	
	protected function createAPI()
	{
		$list = $this->xpath->query("//package/object/api");
		
		$api = false;

		if($list->length == 1){
			$node = $list->item(0);
			if($node){
				$object = $node->nodeValue;
				
				Amslib::requireFile("$this->packageLocation/objects/{$object}.php");
				
				$error = "FATAL ERROR(Amslib_Plugin::createAPI) Could not __ERROR__ for plugin '$this->name'<br/>";
				
				if(!class_exists($object)){
					//	class does not exist
					die(str_replace("__ERROR__","find class '{$object}'",$error));
				}
				
				if(!method_exists($object,"getInstance")){
					//	class exists, but method does not
					die(str_replace("__ERROR__","find the getInstance method in the API object '{$object}'",$error));
				}

				$api = call_user_func(array($object,"getInstance"));
			}else{
				//	ERROR: XML Node was invalid?
			}
		}else{
			//	ERROR: More than one API object is not allowed
		}

		//	An API Object was not created, so create a default Amslib_MVC3 object instead
		if($api == false) $api = new Amslib_MVC3();
		
		//	Set the MVC base location
		$api->setLocation($this->getLocation());
		$api->setName($this->getName());
		
		//	Load all the routes from the router system into the mvc layout
		foreach($this->routes as $name=>$route) $api->setRoute($name,$route);
		
		return $api;
	}
	
	protected function findResource($plugin,$node)
	{
		//	If a resource is absolute, then it doesnt need to be "found", just return it
		if($node->getAttribute("absolute")) return $node->nodeValue;
		
		$file = $node->nodeValue;

		//	First, just look for the file in the package directory directly
		$path = str_replace("//","/","$this->packageLocation/$file");
		
		//	If the file doesnt exist, then search for it in the include path
		if(!file_exists($path)){
			$path = Amslib_Filesystem::find($file,true);
		}
		
		//	If it was found, return the relative path for the file, or false if you didnt find it
		return ($path) ? Amslib_Website::rel($path) : false;
	}
	
	protected function openPackage()
	{
		$document = new DOMDocument('1.0', 'UTF-8');
		if(@$document->load($this->packageXML)){
			$document->preserveWhiteSpace = false;
			$this->xpath = new DOMXPath($document);
			
			return true;
		}
		
		//	TODO: This needs to be better than "OMG WE ARE ALL GONNA DIE!!!"
		print("Amslib_Plugin::openPackage(): PACKAGE FAILED TO OPEN: file[$this->packageXML]<br/>");

		return false;
	}
	
	protected function loadDependencies()
	{
		$deps = $this->xpath->query("//package/requires/plugin");
		
		for($a=0;$a<$deps->length;$a++){
			$name = $deps->item($a)->nodeValue;
			
			Amslib_Plugin_Manager::add($name);
		}
	}
	
	protected function loadConfiguration()
	{
		$this->api = $this->createAPI();
		
		//	If the API is not valid, return false to trigger an error.
		if(!$this->api) return false;

		$controllers = $this->xpath->query("//package/controllers/name");
		for($a=0;$a<$controllers->length;$a++){
			$c		=	$controllers->item($a);
			$id		=	$c->getAttribute("id");
			$name	=	$c->nodeValue;
			$this->api->setController($id,$name);
		}

		$layouts = $this->xpath->query("//package/layout/name");
		for($a=0;$a<$layouts->length;$a++){
			$l		=	$layouts->item($a);
			$id		=	$l->getAttribute("id");
			$name	=	$l->nodeValue;
			$this->api->setLayout($id,$name);
		}

		$views = $this->xpath->query("//package/view/name");
		for($a=0;$a<$views->length;$a++){
			$v		=	$views->item($a);
			$id		=	$v->getAttribute("id");
			$name	=	$v->nodeValue;
			$this->api->setView($id,$name);
		}

		$objects = $this->xpath->query("//package/object/name");
		for($a=0;$a<$objects->length;$a++){
			$o		=	$objects->item($a);
			$id		=	$o->getAttribute("id");
			$name	=	$o->nodeValue;
			$this->api->setObject($id,$name);
		}

		//	FIXME: why are services treated differently then other parts of the MVC system?
		//	FIXME: suggestion: Sv_Service_Name
		$services = $this->xpath->query("//package/service/file");
		for($a=0;$a<$services->length;$a++){
			$s		=	$services->item($a);
			$id		=	$s->getAttribute("id");
			$file	=	$s->nodeValue;
			$this->api->setService($id,$file);
		}

		$images = $this->xpath->query("//package/image/file");
		for($a=0;$a<$images->length;$a++){
			$i		=	$images->item($a);
			$id 	=	$i->getAttribute("id");
			$file	=	$this->findResource($this->name,$i);
			$this->api->setImage($id,$file);
		}

		$javascript = $this->xpath->query("//package/javascript/file");
		for($a=0;$a<$javascript->length;$a++){
			$j		=	$javascript->item($a);
			$id		=	$j->getAttribute("id");
			$cond	=	$j->getAttribute("cond");
			$file	=	$this->findResource($this->name,$j);
			$this->api->setJavascript($id,$file,$cond);
		}

		$stylesheet = $this->xpath->query("//package/stylesheet/file");
		for($a=0;$a<$stylesheet->length;$a++){
			$s		=	$stylesheet->item($a);
			$id		=	$s->getAttribute("id");
			$cond	=	$s->getAttribute("cond");
			$file	=	$this->findResource($this->name,$s);
			$this->api->setStylesheet($id,$file,$cond);
		}

		$this->api->initialise();
	}
	
	protected function loadRouter()
	{
		$source = Amslib_Router3::getObject("xml");
		$this->routes = $source->load($this->packageXML);
	}
	
	public function __construct(){}
	
	public function load($name,$location)
	{
		$this->name				=	$name;
		$this->packageLocation	=	$location.$name;
		$this->packageXML		=	$location.$name."/package.xml";
		
		if($this->openPackage()){
			$this->loadDependencies();
			$this->loadRouter();
			$this->loadConfiguration();

			return $this->api;
		}
		
		return false;
	}
	
	public function getLocation()
	{
		return $this->packageLocation;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getAPI()
	{
		return $this->api;
	}
	
	public function setAPI($api)
	{
		$this->api = $api;
	}
	
	public function &getInstance()
	{
		static $instance = NULL;
		
		if($instance === NULL) $instance = new self();
		
		return $instance;
	}
}