<?php
require_once("Widget.php");

/**
 * class:	Gallery2
 * 
 * See Also: Container
 * 
 * Makes a simple gallery which can display multiple columns of images.
 * It is by no means complete, proper, only has basic functionality
 * 
 * notes:
 * 	-	I'm not entirely sure how to tell the new page to display
 * 		the comment for the screenshot, without passing it through the URL, or a session
 *  	variable
 */
class Gallery2 extends Container
{
/**	section:	protected	*/

	/**
	 * integer:	column
	 * 
	 * The current column being added to
	 */
	protected $column;
	
	/**
	 * integer:	maxColumn
	 * 
	 * The maximum number of columns that can be used
	 */
	protected $maxColumn;
	
	/**
	 * string:	url
	 * 
	 * The base url for all images in the gallery
	 */
	protected $url;
	
	/**
	 * string:	tag
	 * 
	 * The URL parameter which is used to identify the file being opened (the file being the image)
	 */
	protected $tag;
	
	/**
	 * integer:	width
	 * 
	 * The width of a gallery item in pixels
	 */
	protected $width;
	
	/**
	 * integer:	padding
	 * 
	 * The padding of a gallery item in pixels
	 */
	protected $padding;
	
/**	section:	public	*/

	/**
	 * method:	constructor
	 * 
	 * The Gallery2 constructor, here we set all the values to their default
	 */
	public function __construct(){
		parent::__construct("div",array("class"=>"floatFix,floatFix-left"));

		$this->column = 0;
		$this->maxColumn = 0;

		$this->url = "#";
		$this->tag = "";

		$this->width = 0;
		$this->padding = 0;
	}
	
	/**
	 * method:	setMaxColumns
	 * 
	 * Set the maximum number of columns which can be used
	 * 
	 * parameters:
	 * 	maxColumn	-	The number of columns to use at most
	 */
	public function setMaxColumns($maxColumn){
		$this->maxColumn = $maxColumn;
	}
	
	/**
	 * method:	getMaxColumns
	 * 
	 * Retrieve the maximum number of columns
	 * 
	 * returns:
	 * 	An integer for the maximum number of columns
	 */
	public function getMaxColumns()
	{
		return $this->maxColumns;
	}
	
	/**
	 * method:	setWidth
	 * 
	 * Set the width of a gallery item
	 * 
	 * parameters:
	 * 	width	-	The width of a gallery item, in pixels
	 */
	public function setWidth($width)
	{
		$this->width = $width;
	}
	
	/**
	 * method:	getWidget
	 * 
	 * Retrieve the width of a gallery item
	 * 
	 * returns:
	 * 	An integer for the width of the gallery item in pixels
	 */
	public function getWidget()
	{
		return $this->width;
	}
	
	/**
	 * method:	setPadding
	 * 
	 * Set the padding of a gallery item
	 * 
	 * parameters:
	 * 	padding	-	The padding of a gallery item, in pixels
	 */
	public function setPadding($padding)
	{
		$this->padding = $padding;
	}
	
	/**
	 * method:	getPadding
	 * 
	 * Retrieve the number of pixels padding for a gallery item
	 * 
	 * returns:
	 * 	An integer number of pixels padding
	 */
	public function getPadding()
	{
		return $this->padding;
	}
	
	/**
	 * method:	setURL
	 * 
	 * Set the base URL to call when a gallery item is clicked
	 * 
	 * parameters:
	 * 	url	-	The URL to open
	 * 	tag	-	The URL parameter tag to use in the final constructed URL
	 */
	public function setURL($url,$tag="")
	{
		$this->url = $url;
		$this->tag = $tag;
	}
	
	/**
	 * method:	getURL
	 * 
	 * Retrieve the base URL which is called when the gallery item is clicked
	 * 
	 * returns:
	 * 	A URL string for the base of every gallery item link
	 */
	public function getURL()
	{
		return $this->url;
	}
	
	/**
	 * method:	getTag
	 * 
	 * Retrieve the URL parameter tag which is used in every gallery item link
	 * the tag is so the opened page can see the full sized image which is supposed
	 * to display or render in some way.  This requires the URL script to process
	 * this value and do something with it, otherwise, it's kinda useless
	 * 
	 * returns:
	 * 	A string for the tag which is used in the URL
	 */
	public function getTag()
	{
		return $this->tag;
	}
	
	/**
	 * method:	getRow
	 * 
	 * Get a new row, or re-use an existing row
	 * 
	 * Operations:
	 * 	- if the column number is 0, create a new HBox and add it to the Gallery
	 * 	-	if the column number is not zero, reuse the existing HBox
	 * 	-	return either of the above HBox widgets
	 * 
	 * returns:
	 * 	A HBox widget, either new, or existing
	 */
	protected function getRow()
	{
		static $hbox = NULL;
		
		if($this->column == 0){
			$hbox = new HBox("hbox",array("class"=>"floatFix,floatFix-left"));
			$this->addWidget($hbox);	
		}
		
		return $hbox;
	}
	
	/**
	 * method:	addImage
	 * 
	 * Add an image to the gallery
	 * 
	 * parameters:
	 * 	img		-	The image to display in the gallery
	 * 	file	-	The file to open when the gallery item is clicked (most likely a full size image)
	 * 	desc	-	The description to show under the gallery image
	 * 	url		-	The URL to open when the gallery item is clicked
	 * 
	 * Operations:
	 * 	-	Get a row to store the gallery image in
	 * 	-	Figure out how to append the file tag onto the base URL (either with ? or &)
	 * 	-	Create a new link, combining the base url, the appendage and the tag URL parameter, set to the file to open
	 * 	-	Add this link to the container for the gallery image
	 * 	-	Add the container to the row
	 * 	-	Obtain the gallery image's parent and modify the classes to add the required padding
	 * 	-	Set the galleryImage CSS style to the image's parent
	 * 	-	Update the column being updated
	 */ 
	public function addImage($img,$file,$desc,$url="#")
	{	
		$hbox = $this->getRow();
			
		$app = (!strpos($this->url,"?")) ? "?" : "&";

		$link = new Link("$this->url{$app}$this->tag=$file");
		$link->addWidget(new Image($img,$this->width,NULL,"screenshot"));

		$container = new Container(NULL);
		$container->addWidget($link);
		$container->addWidget(new Widget("div",$desc));

		$hbox->addWidget($container);

		$parent = $container->getParent();
		$parent->addAttribute("class","galleryImage");
		$parent->setAttribute("style","margin: {$this->padding}px");
		
		$this->column = ($this->column > $this->maxColumn) ? 0 : $this->column+1;
	}
	
	/**
	 * method:	showBackButton
	 * 
	 * Show a back button for each gallery item which is opened
	 * 
	 * parameters:
	 * 	img	-	The image to show as the back button
	 * 	url	-	The url to open when the button is clicked
	 * 
	 * operations:
	 * 	-	create a new link with the URL and galleryBack CSS class
	 * 	-	Insert an image into the link using the img parameter
	 * 	-	Create a container for the link to sit inside, then add the link
	 * 	-	Add the container to the Gallery parent
	 * 
	 * notes:
	 * 	-	This is probably too customised for a generic "Gallery" class
	 * 		I will leave it here for now, but I think it'll get removed
	 * 		in the end
	 */
	public function showBackButton($img,$url){
		$link = new Link($url,NULL,NULL,array("class"=>"galleryBack"));
		$link->addWidget(new Image($img,NULL,NULL,"Back Button"));

		$container = new Container("div",array("class"=>"nav"));
		$container->addWidget($link);
		
		$this->addWidget($container);
	}
}
?>