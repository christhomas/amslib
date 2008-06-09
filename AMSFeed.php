<?php
require_once("amslib/rsswriter.php");

/**
 * class:	AMSFeed
 * 
 * A class to process Feeds which originate from Antimatter Studios websites
 * which are built in a particular way to facilitate sharing and data abstraction
 */
class AMSFeed{
	
	/**
	 * reference:	rss
	 * 
	 * A RSSWriter object used to process the Feed
	 */
	protected $rss;
	
	/**
	 * string:	The name of the project being processed
	 */
	protected $project;
	
	/**
	 * string:	The site root for this website feed
	 */
	protected $siteRoot;
	
	/**
	 * string:	The site root for this project being described in the feed
	 */
	protected $server;
	
	/**
	 * method:	addDescription
	 * 
	 * Add a descriptive summary to the feed information
	 */
	protected function addDescription()
	{
		$description = amslib::getIncludeContents("content/{$this->project}_desc.php");
		
		if($description){
			$this->addItem(	$this->siteRoot,
											$this->project,
											array("ams:description"=>$description,"ams:category"=>"summary"));																		
		}		
	}
	
	/**
	 * method:	addInstallation
	 * 
	 * Add some project installation instructions to the feed
	 */
	protected function addInstallation()
	{
		$install = amslib::getIncludeContents("content/{$this->project}_install.php");
		
		if($install){
			$this->addItem(	$this->siteRoot,
											$this->project,
											array("ams:installation"=>$install,"ams:category"=>"installation"));
		}																		
	}	
	
	/**
	 * method:	constructor
	 * 
	 * Build the feed object and set some primary variables
	 * 
	 * parameters:
	 * 	server		-	The string denoting the server base URL
	 * 	project		-	The name of the project
	 * 	siteRoot	-	The site root for this project
	 */
	public function __construct($server,$project=NULL,$siteRoot=NULL)
	{
		$this->project	= $project;
		$this->siteRoot	= $server.$siteRoot.$project;
		$this->server		=	$server;
	}
	
	/**
	 * method:	init
	 * 
	 * Initialise and set all the parameters necessary to operate
	 * 
	 * parameters:
	 * 	desc				-	A description for this feed
	 * 	fileCount		-	The number of file chunks in the feed
	 * 	imageCount	-	The number of image chunks in the feed
	 * 	newsCount		-	The number of news chunks in the feed
	 */
	public function init($desc,$fileCount=0,$imageCount=0,$newsCount=0)
	{
		$this->rss = new RSSWriter(	$this->siteRoot,
																"RSS Site Feed Linker",
																$desc,
																array(	"ams:publisher" => "Antimatter Studios", 
																				"ams:creator" => "Christopher Alexander Thomas",
																				"ams:filecount" => $fileCount,
																				"ams:imagecount" => $imageCount,
																				"ams:newscount" => $newsCount)
																				);
		$this->rss->useModule("ams", $this->server."/AMStudiosRSSModule/");
		
		$this->addDescription();
		$this->addInstallation();
	}
	
	/**
	 * method:	addItem
	 * 
	 * Add an item to the feed, with optional options (lol)
	 * 
	 * parameters:
	 * 	url			-	The URL for the item link
	 * 	title		-	The title for the item
	 * 	options	-	An optional array of items for other data in this feed item
	 */
	public function addItem($url,$title,$options=NULL)
	{
		if($options == NULL) $options = array();
		
		$this->rss->addItem($url,$title,$options);		
	}
	
	/**
	 * method:	addScreenShot
	 * 
	 * Add a screenshot item to the feed
	 * 
	 * parameters:
	 * 	project	-	The name of the project
	 * 	image		-	The filename image to add to the feed
	 * 	url			-	The URL for the screenshot
	 * 
	 * notes:
	 * 	-	We don't use the project member variable here on purpose
	 * 		because it's not guarenteed that the project named will be the
	 * 		project the screenshot is from
	 */
	public function addScreenShot($project,$image,$url)
	{
		$base = $this->server."/images/scr_websites_";
		
		$this->addItem(	$base.$image,
										"$project screenshot",
										array(	"ams:category" => "screenshot", 
														"ams:full" => $url));
		
	}
	
	/**
	 * method:	addFile
	 * 
	 * Add a file item to the feed
	 * 
	 * parameters:
	 * 	file	-	The filename to add to the feed
	 * 	date	-	The date of this item being added
	 * 	type	-	The type of file that is being added (binary,text)
	 */
	public function addFile($file,$date,$type)
	{
		$name = substr($file,0,strrpos($file,"/"));
		
		$this->addItem(	$this->server.$file,
										$name, 
										array(	"ams:category" => "file",
														"ams:count" => 1, 
														"ams:size" => 0,
														"ams:date" => $date,
														"ams:type" => $type));
	}
	
	/**
	 * method:	render
	 * 
	 * Render the feed
	 */
	public function render()
	{
		$this->rss->serialize();
	}
}

?>