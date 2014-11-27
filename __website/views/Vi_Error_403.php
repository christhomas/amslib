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

	<title>403 Forbidden | Not allowed</title>

	<link rel="stylesheet" type="text/css" href="../bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="../error.css" />
</head>

<body>
	<div class="container">
		<div class="jumbotron center">
			<h1>Forbidden Access <small><font face="Tahoma" color="red">Error 403</font></small></h1>
			<br />
			<p>	It was not permitted to access that resource, the system prevented this page from being accessed,
				it was not possible to report the specific reason why.
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