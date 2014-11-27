<?php
$base = isset($_SERVER["__WEBSITE_ROOT__"]) ? $_SERVER["__WEBSITE_ROOT__"] : "";

$data = "";
if(!strlen($data) && isset($_GET["data"])){
	$data = $_GET["data"];
}

if(!strlen($data) && isset($_SERVER["QUERY_STRING"])){
	$part = explode("data=",$_SERVER["QUERY_STRING"]);
	$data = end($part);
}

$data = json_decode(base64_decode($data),true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<!-- General mobile devices web-app optimisation -->
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="HandheldFriendly" content="True">
	<meta name="MobileOptimized" content="320">
	<meta http-equiv="cleartype" content="on">
	<meta charset="utf-8">
	<!-- TODO --- fix for iPhone5 -- width=320.1 -->
	<!-- viewport - only web app -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">

	<title>404 Page Not Found</title>

	<link rel="stylesheet" type="text/css" href="<?=$base?>error/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="<?=$base?>error/error.css" />
</head>

<body>
	<div class="container">
		<div class="jumbotron center">
			<h1>Page Not Found <small><font face="Tahoma" color="red">Error 404</font></small></h1>
			<br />
			<p>	The page you requested could not be found, either contact your webmaster or
				try again. Use your browsers <b>Back</b> button to navigate to the page you
				have prevously come from
			</p>

			<p>The page requested: <a href="#__TODO_PAGE_URL__">__TODO_PAGE_URL__</a></p>

			<p><b>Or you could just press this neat little button:</b></p>

			<a href="#__TODO_HOME_URL__" class="btn btn-large btn-info">
				<i class="icon-home icon-white"></i> Take Me Home
			</a>
		</div>
	</div>
</body>
</html>