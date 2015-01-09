<div class="container">
	<div class="jumbotron center">
		<h1>Internal Server Error <small><font face="Tahoma" color="red">Error 500</font></small>
		</h1>
		<br />
		<p>An unrecoverable fatal error occured</p>

		<?php if(empty($data)): ?>
		<p>For some reason, there was no error data available, we have no information to provide you</p>
		<?php else: ?>
		<p>The page requested: <a href="<?=$data["uri"]?>"><?=$data["uri"]?></a></p>

		<p><b>Or you could just press this neat little button:</b></p>

		<p><a href="<?=$data["root"]?>" class="btn btn-large btn-info">
			<i class="icon-home icon-white"></i> Take Me Home
		</a></p>

		<p>Error: <?=$data["msg"]?> on page "<?=$data["uri"]?>" in file "<?=$data["file"]?>", line: <?=$data["line"]?></p>
		<?php endif ?>
	</div>
</div>