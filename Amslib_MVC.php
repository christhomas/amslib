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
	
	protected $widgetManager;
	
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
	}
	
	public function setDatabase($database)
	{
		$this->database = $database;
	}
	
	public function setWidgetManager($widgetManager)
	{
		$this->widgetManager = $widgetManager;
		
		$this->initialise();
	}
	
	public function getWidgetManager()
	{
		return $this->widgetManager;
	}
	
	public function setPath($path)
	{
		$this->path = $path;
	}
	
	public function getPath()
	{
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
	
	public function setController($name,$file)
	{
		$this->controllers[$name] = $file;
	}
	
	public function getController($name)
	{
		return $this->controllers[$name];
	}
	
	public function setLayout($name,$file)
	{
		$this->layout[$name] = $file;
	}
	
	public function getLayout($name=NULL)
	{
		if($name && isset($this->layout[$name])) return $this->layout[$name];
		
		return $this->layout;
	}
	
	public function setObject($name,$file)
	{
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
	}
	
	public function getService($name)
	{
		return (isset($this->service[$name])) ? $this->service[$name] : false;
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
	
	public function setView($name,$file)
	{
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
}