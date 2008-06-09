<?php 

function createAMStudiosFooter()
{
	$site = "http://".$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"];
	$validateXHTML	= "http://validator.w3.org/check?uri=".$site;
	$validateCSS	= "http://jigsaw.w3.org/css-validator/validator?uri=".$site;
	
	$footer = new VBox("vbox");
	$hbox = new HBox("hbox",array("id"=>"footer"));
	
	$link = new Link($validateXHTML);
	$link->addWidget(new Image("http://www.w3.org/Icons/valid-html401",88,31,"Valid XHTML 1.0 Strict"));
	$hbox->addWidget($link);
	
	$link = new Link($validateCSS);
	$link->addWidget(new Image("http://jigsaw.w3.org/css-validator/images/vcss",88,31,"Valid CSS!"));
	$hbox->addWidget($link);
	
	$link = new Link($_SERVER["PHP_SELF"]);
	$link->addWidget(new Image("images/AntimatterLogoTiny.png",NULL,NULL,"Antimatter Studios Logo"));
	$hbox->addWidget($link);
	$link->getParent()->setAttribute("class","hbox-right");
	
	$container = new Container(NULL);
	$container->addWidget(new Widget(NULL,"Website is copyright: "));
	$container->addWidget(new Link($_SERVER["PHP_SELF"],"Antimatter Studios Ltd"));
	$hbox->addWidget($container);
	
	$footer->addWidget($hbox);
	$footer->addWidget(new Widget("div","The Lightwave logo is copyright of NewTek"));
	
	return $footer;	
}
?>