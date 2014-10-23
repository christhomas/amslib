<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?=$meta_description?>">
    <meta name="author" content="<?=$meta_author?>">

	<title><?=$site_title?></title>

	<link rel="shortcut icon" href="<?=$api->getImage("/favicon/favicon.ico")?>" type="image/x-icon" />
	<link rel="apple-touch-icon" href="<?=$api->getImage("/favicon/apple-touch-icon.png")?>" />
	<link rel="apple-touch-icon" sizes="57x57" href="<?=$api->getImage("/favicon/apple-touch-icon-57x57.png")?>" />
	<link rel="apple-touch-icon" sizes="72x72" href="<?=$api->getImage("/favicon/apple-touch-icon-72x72.png")?>" />
	<link rel="apple-touch-icon" sizes="76x76" href="<?=$api->getImage("/favicon/apple-touch-icon-76x76.png")?>" />
	<link rel="apple-touch-icon" sizes="114x114" href="<?=$api->getImage("/favicon/apple-touch-icon-114x114.png")?>" />
	<link rel="apple-touch-icon" sizes="120x120" href="<?=$api->getImage("/favicon/apple-touch-icon-120x120.png")?>" />
	<link rel="apple-touch-icon" sizes="144x144" href="<?=$api->getImage("/favicon/apple-touch-icon-144x144.png")?>" />
	<link rel="apple-touch-icon" sizes="152x152" href="<?=$api->getImage("/favicon/apple-touch-icon-152x152.png")?>" />

	<?=Amslib_Resource::getStylesheet()?>
	<?=Amslib_Resource::getJavascript()?>
</head>

<body>
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="glyphicon glyphicon-align-justify"></span>
				</button>

                <a class="navbar-brand" href="<?=$url_home?>"><?=$site_title?></a>
            </div>
        </div>
        <!-- /.container -->
    </nav>

    <main>
		<!--[if lt IE 7]>
		<p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
		<![endif]-->

		<?=$content?>
    </main>
</body>