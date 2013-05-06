<!doctype html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> 		<html class="ie6" lang="en">	<![endif]-->
<!--[if IE 7]>    		<html class="ie7" lang="en">	<![endif]-->
<!--[if IE 8]>    		<html class="ie8" lang="en">	<![endif]-->
<!--[if IE 9]>    		<html class="ie9" lang="en">	<![endif]-->
<!--[if gt IE 9]><!-->	<html class="" lang="en">		<!--<![endif]-->

<head>
	<meta charset="utf-8" />

	<!-- Mobile viewport optimized: j.mp/bplateviewport -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="description" content="">

	<!-- Place favicon.ico and apple-touch-icon.png in the root directory -->

	<title>AMSLIB: Hello World Template</title>

	<link rel="shortcut icon" href="<?=$api->getImage("favicon")?>" />

	<?=Amslib_Resource::getStylesheet()?>
	<?=Amslib_Resource::getJavascript()?>
</head>

<body class="floatfix">
	<!--[if lt IE 7]>
	<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
	<![endif]-->

	<!-- Add your site or application content here -->
	<?=$content?>
</body>
</html>