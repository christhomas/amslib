<?php 
class Amslib_MVC
{
	/*********************************
	 * string: $path
	 * 
	 * The path to the MVC base path within the website filesystem
	 */
	protected $path;
	
	protected $database;
	protected $controller;
	protected $layout;
	protected $object;
	protected $view;
	protected $images;
	protected $service;
	protected $value;
	protected $hidden;
	
	//	MVC Configuration
	protected $controllerDir	=	"controllers";
	protected $controllerPrefix	=	"Ct_";
	protected $layoutDir		=	"layouts";
	protected $layoutPrefix		=	"La_";	
	protected $viewDir			=	"views";
	protected $viewPrefix		=	"Vi_";
	protected $themeDir			=	"themes";
	protected $themePrefix		=	"Th_";
	protected $objectDir		=	"objects";
	protected $objectPrefix		=	"";
	protected $serviceDir		=	"services";
	protected $servicePrefix	=	"Sv_";
	
	protected $widgetManager;
	protected $widgetName;
	protected $widgetPath;
	
	protected function initialise(){}
	
	public function __construct()
	{
		$this->path				=	"";
		$this->controller		=	array();
		$this->layout			=	array();
		$this->object			=	array();
		$this->view				=	array();
		$this->service			=	array();
		$this->value			=	array();
		$this->widgetManager	=	NULL;
		$this->widgetPath		=	NULL;
		$this->widgetName		=	NULL;
	}
	
	public function setupMVC($type,$dir,$prefix)
	{
		switch($type){
			case "controllers":{
				$this->controllerDir	=	$dir;
				$this->controllerPrefix	=	$prefix;
			}break;
			
			case "layouts":{
				$this->layoutDir		=	$dir;
				$this->layoutPrefix		=	$prefix;
			}break;
			
			case "views":{
				$this->viewDir			=	$dir;
				$this->viewPrefix		=	$prefix;
			}break;
			
			case "themes":{
				$this->themeDir			=	$dir;
				$this->themePrefix		=	$prefix;
			}
			
			case "objects":{
				$this->objectDir		=	$dir;
				$this->objectPrefix		=	$prefix;
			}break;
			
			case "services":{
				$this->serviceDir		=	$dir;
				$this->servicePrefix	=	$prefix;
			}break;
		}
	}
	
	public function setDatabase($database)
	{
		$this->database = $database;
	}
	
	public function setWidgetManager($widgetManager)
	{
		$this->widgetManager	=	$widgetManager;
		$this->widgetPath		=	$widgetManager->getWidgetPath();
		
		$this->initialise();
	}
	
	public function getWidgetManager()
	{
		return $this->widgetManager;
	}
	
	public function setWidgetName($name)
	{
		$this->widgetName	=	$name;
		$this->widgetPath	=	"{$this->widgetPath}/{$this->widgetName}";
	}
	
	public function setPath($path)
	{
		//	NOTE: What is this for?
		$this->path = $path;
	}
	
	public function getPath()
	{
		//	NOTE: What is this for?
		return $this->path;
	}
	
	public function setValue($name,$value)
	{
		$this->value[$name] = $value;
	}
	
	public function getValue($name)
	{
		return (isset($this->value[$name])) ? $this->value[$name] : NULL;	
	}
	
	public function setController($name,$file=NULL)
	{
		if($file === NULL){
			$file = "{$this->widgetPath}/{$this->controllerDir}/{$this->controllerPrefix}{$name}.php";
		}
		
		$this->controllers[$name] = $file;
	}
	
	public function getController($name)
	{
		return $this->controllers[$name];
	}
	
	public function setLayout($name,$file=NULL)
	{
		if($file === NULL){
			$file = "{$this->widgetPath}/{$this->layoutDir}/{$this->layoutPrefix}{$name}.php";
		}

		$this->layout[$name] = $file;
	}
	
	public function getLayout($name=NULL)
	{
		if($name && isset($this->layout[$name])) return $this->layout[$name];
		
		return $this->layout;
	}
	
	public function setObject($name,$file=NULL)
	{
		if($file === NULL){
			$file = "{$this->widgetPath}/{$this->objectDir}/{$this->objectPrefix}{$name}.php";
		}
		
		$this->object[$name] = $file;
	}
	
	public function getObject($name,$singleton=false)
	{
		if(isset($this->object[$name]) && Amslib::requireFile($this->object[$name]))
		{
			if($singleton){
				return call_user_func(array($name,"getInstance"));
			}
			
			return new $name;
		}
		
		return false;
	}
	
	public function setService($name,$file)
	{
		$this->service[$name] = $file;
		
		//	Set this as a service url for the javascript to acquire
		$this->hidden["service:$name"] = $file;
	}
	
	public function getService($name)
	{
		return (isset($this->service[$name])) ? $this->service[$name] : false;
	}
	
	public function getHiddenParameters()
	{
		$list = "";
		
		foreach($this->hidden as $k=>$v){
			if(is_bool($v)) $v = ($v) ? "true" : "false";
			
			$list.="<input type='hidden' name='$k' value='$v' />";
		}
		
		return "<div class='widget_parameters'>$list</div>";
	}
	
	public function copyService($src,$name,$copyAs=NULL)
	{
		if($copyAs === NULL) $copyAs = $name;
		
		$api = $this->widgetManager->getAPI($src);
		$this->setService($copyAs,$api->getService($name));
	}
	
	public function setImage($name,$file)
	{
		$this->images[$name] = $file;
	}
	
	public function getImage($name)
	{
		return (isset($this->images[$name])) ? $this->images[$name] : false;
	}
	
	public function setView($name,$file=NULL)
	{
		if($file === NULL){
			$file = "{$this->widgetPath}/{$this->viewDir}/{$this->viewPrefix}{$name}.php";
		}
		
		$this->view[$name] = $file;
	}
	
	public function getView($name,$parameters=array())
	{
		if(isset($this->view[$name])){
			$view = $this->view[$name];
			
			$parameters["widget_manager"]	=	$this->widgetManager;
			$parameters["api"]				=	$this;
			
			ob_start();
			Amslib::requireFile($view,$parameters);
			return ob_get_clean();
		}
		
		return "";
	}
	
	/**************************************************************************
	 * method: render
	 * 
	 * render the output from this MVC, the basic version just renders the 
	 * first layout as defined in the XML, without a controller.
	 * 
	 * returns:
	 * 	A string of HTML or empty string which represents the first layout
	 * 
	 * notes:
	 * 	we only render the first layout in the widget, what happens if there are 10 layouts?
	 */
	public function render($parameters=array())
	{
		$layout = reset($this->layout);
		
		$parameters["widget_manager"]	=	$this->widgetManager;
		$parameters["api"]				=	$this;
		
		ob_start();
		Amslib::requireFile($layout,$parameters);
		return ob_get_clean();
	}
	
	public function replyJSON($response)
	{
		header("Content-Type: application/json");
		$response = json_encode($response);
		die($response);
	}
}