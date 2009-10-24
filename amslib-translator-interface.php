<?php
require_once("amslib.php");
amslib::addIncludePath(".");

amslib::include_file("amslib-translator.php");

class AntimatterTranslator_InterfaceData
{
/** Protected members */
	var $__databases;

	var $__baseDir;

	var $__translator;

	var $__updateKey = "update-expression-";
	var $__deleteKey = "delete-expression-";

	var $__defaultDatabase = NULL;

	function __createDatabase()
	{
		$name = amslib::postParam("database-name");

		$filename = "$this->__baseDir/$name.translation";

		$this->__loadDatabase($filename);

		return false;
	}

	function __createExpression()
	{
		$key	= amslib::postParam("expression-key");
		$text	= amslib::postParam("expression-text");

		if($key && $text){
			$this->__loadDatabase();
			$this->__translator->learn($key,$text);
		}
	}

	function __updateExpression($key)
	{
		$key = str_replace($this->__updateKey,"",$key);

		$text = amslib::postParam("expression-text-$key");

		$this->__loadDatabase();
		$this->__translator->learn($key,$text);
	}

	function __deleteExpression($key)
	{
		$key = str_replace($this->__deleteKey,"",$key);

		$this->__loadDatabase();
		$this->__translator->forget($key);
	}

	function __loadTranslations()
	{
		$selected = amslib::postParam("change-database");

		amslib::insertSessionParam("language",$selected);
	}

	function __loadDatabase($override=NULL)
	{
		if(!$this->__translator){
			// Select in order of importance, override, session, then default
			$selected = $override;
			if(!$selected) $selected = amslib::sessionParam("language");
			if(!$selected) $selected = $this->__loadDefaultDatabase();

			$this->__translator = &AntimatterTranslator::getInstance();
			$this->__translator->open($selected,true);
			$this->__translator->sync();
		}
	}

	function __loadDefaultDatabase()
	{
		$this->__listLocales();

		$selected = current($this->__databases);

		$this->__defaultDatabase = $selected;

		return $selected;
	}

	function __listLocales()
	{
		$this->__databases = array();

		if($this->__baseDir !== false){
			$this->__databases = glob($this->__baseDir."/*.translation");
		}

		return $this->__databases;
	}

	function __findLocales()
	{
		$cwd = getcwd();
		while(strlen($cwd)){
			$locale = $cwd."/locale";
			if(is_dir($locale)){
				$this->__baseDir = $locale;
				return $this->__baseDir;
			}else{
				$cwd = substr($cwd,0,strrpos($cwd,"/"));
			}
		}

		return false;
	}

	function __findPostParam($search)
	{
		foreach($_POST as $key=>$value){
			if(strpos($key,$search) !== false){
				return $key;
			}
		}

		return false;
	}

/** Public members */

	function AntimatterTranslator_InterfaceData()
	{
		@session_start();

		$this->__translator = false;

		$this->__findLocales();

		if(amslib::postParam("load-translations"))			$this->__loadTranslations();
		if(amslib::postParam("create-database"))			$this->__createDatabase();
		if(amslib::postParam("create-expression"))			$this->__createExpression();
		if($key=$this->__findPostParam($this->__updateKey))	$this->__updateExpression($key);
		if($key=$this->__findPostParam($this->__deleteKey))	$this->__deleteExpression($key);

		$this->__listLocales();
		$this->__loadDatabase();
	}

	static function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new AntimatterTranslator_InterfaceData();

		return $instance;
	}

	function getDefaultDatabase()
	{
		return $this->__defaultDatabase();
	}

	function getDatabaseList()
	{
		return $this->__databases;
	}

	function getTranslations()
	{
		$this->__loadDatabase();
		return $this->__translator->listAll();
	}
}

class AntimatterTranslator_InterfaceDisplay
{
	var $__data;

	function __getDatabaseName($database)
	{
		$name = substr($database,strrpos($database,"/")+1);
		$name = substr($name,0,strrpos($name,".translation"));

		return $name;
	}

	function __getDatabaseList()
	{
		$options = "";
		$current = amslib::sessionParam("language");

		if(!empty($this->__databases)){
			foreach($this->__databases as $db){
				$name = $this->__getDatabaseName($db);

				$selected = ($current == $db) ? "selected='selected'" : "";
				$options .= "<option value='$db' $selected>$name</option>";
			}
		}else{
			$options .= "<option value='-'>There are no databases present, please create one</option>";
		}

		return $options;
	}

	function AntimatterTranslator_InterfaceDisplay()
	{
		$this->__data = AntimatterTranslator_InterfaceData::getInstance();
	}

	static function &getInstance()
	{
		static $instance = NULL;

		if($instance === NULL) $instance = new AntimatterTranslator_InterfaceDisplay();

		return $instance;
	}

	function showDatabaseSelect()
	{
		$options		=	$this->__data->getDatabaseList();

		$default		=	$this->__data->getDefaultDatabase();
		if($default != NULL){
			$name		=	$this->getDatabaseName($default);
			$default	=	"WARNING: The database '$default' was loaded by default, this might not be what you want";
		}

return<<<FORM
<form method="post" class="select-database">
	<select id="change-database" name="change-database">
		$options
	</select>

	<div class="submit">
		<input type="submit" name="load-translations" value="Load Translations" />
	</div>

	<div class="default">$default</div>
</form>
FORM;
	}

	function showDatabaseCreate()
	{
return<<<FORM
<form method="post" class="create-database">
	<input class="entry" id="database-name" name="database-name" type="text" value="Enter name of database" />
	<div class="submit">
		<input type="submit" name="create-database" value="Create Database" />
	</div>
</form>
FORM;
	}

	function showExpressionCreate()
	{
return<<<FORM
<form class="create-expression" method="post">
	<div>
		<label for="expression-key">Key:</label>
		<input class="entry" id="expression-key" name="expression-key" type="text" />
	</div>

	<div>
		<label for="expression-text">Text: </label>
		<textarea class="entry" id="expression-text" name="expression-text"></textarea>
	</div>

	<div class="submit">
		<input type="submit" name="create-expression" value="Create Expression" />
	</div>
</form>
FORM;
	}

	function showTranslations()
	{
		$translation = $this->__data->getTranslations();

		$output = "";

		if(!empty($translations)){

			foreach($translations as $k=>$t){
$output .=<<<TRANSLATION
<div class="translation hbox">
	<div>
		<label for="expression-text-$k"><b>Expression Key:</b> $k</label>
		<textarea id="expression-text-$k" name="expression-text-$k">$t</textarea>
	</div>
	<div class="submit">
		<input type="submit" name="{$this->__data->__deleteKey}{$k}" value="Delete" />
		<input type="submit" name="{$this->__data->__updateKey}{$k}" value="Update" />
	</div>
</div>
TRANSLATION;
			}
		}else{
			$output = "There are no translations";
		}

		return $output;
	}

	function getNumTranslations()
	{
		return count($this->__data->getTranslations());
	}
}

$application = AntimatterTranslator_InterfaceDisplay::getInstance();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Antimatter Translator</title>

	<link rel="stylesheet" type="text/css" href="css/box-model.css" />
	<!--[if IE 6]><link rel="stylesheet" type="text/css" href="css/box-model.ie6.css" /><![endif]-->
	<!--[if IE 7]><link rel="stylesheet" type="text/css" href="css/box-model.ie7.css" /><![endif]-->

	<style type="text/css">
		body{
			font-family: Trebuchet Ms;
			font-size: 11px;
			width: 827px;
			margin: auto;
		}

 		label{
			display: inline-block;
			vertical-align: top;
			margin-right: 10px;
		}

		input{
			display: inline-block;

			font-family: Trebuchet Ms;
			font-size: 11px;
		}

		form .entry{
			margin-bottom: 10px;
		}

		textarea{
			font-family: Trebuchet Ms;
			font-size: 11px;

			display: inline-block;
			width: 200px;
			height: 100px;

			overflow: auto;
		}

		input, textarea, select{
			border: 1px solid #999999;
		}

		.submit{
			text-align: right;
		}

		.select-database select{
			display: inline-block;
			width: 100%;
			margin-bottom: 10px;
		}

		.select-database .default{
			margin-top: 20px;
			font-weight: bold;
			color: #CC0000;
		}

		.create-database .entry{
			width: 240px;
			display: block;
		}

		.create-expression label{
			width: 50px;
		}

		.create-expression .entry{
			width: 180px;
		}

		.column{
			position: relative;

			min-height: 190px;
			height: auto !important;
			height: 190px;

			width: 250px;
		}

		.create{
			width: 800px;
		}

		.create form{
			margin-bottom: 20px;
		}

		.create label{
			width: 250px;
		}

		.create .title{
			margin-bottom: 10px;
		}

		.create label{
			width: 150px;
		}

		.create .expression{
			width: 360px;
		}

		.create .entry{
			width: 500px;
		}

		.load, .create, .list{
			margin-bottom: 20px;
		}

		.list label{
			display: block;
		}

		.list .translation{
			margin: 10px;
			width: 380px;
		}

		.list .translation label{
			color: #CC0000;
		}

		.list .translation label b{
			color: #000000;
		}

		.list .translation textarea{
			width: 380px;
			margin: 5px 0 10px 0;

			background-color: #EFEFEF;
		}
	</style>
</head>

<body>
	<h1>Antimatter Translator</h1>
	<p>	To use this translator program, you simply select the database to work with, it will load
			all the translations and the expression keys available in the databases and it will display
			a list of things to translate
	</p>

	<div class="floatfix">
		<fieldset class="hbox">
			<legend>Load Database</legend>
			<div class="column"><?=$application->showDatabaseSelect();?></div>
		</fieldset>

		<fieldset class="hbox">
			<legend>Create Database</legend>
			<div class="column"><?=$application->showDatabaseCreate();?></div>
		</fieldset>

		<fieldset class="hbox">
			<legend>Create Expression</legend>
			<div class="column"><?=$application->showExpressionCreate();?></div>
		</fieldset>
	</div>

	<fieldset>
		<legend>Translations (<?=$application->getNumTranslations()?> available)</legend>
		<form method="post" class="list floatfix">
			<?=$application->showTranslations();?>
		</form>
	</fieldset>
</body>

</html>