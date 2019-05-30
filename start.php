<!DOCTYPE html>
<html>
<head>
	<title>启动</title>	
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<script type="text/javascript" src="./public/static/admin/js/jquery.min.js"></script>
	<script type="text/javascript">
		var i = setTimeout(function(){console.log('ok');window.close()},3000);
	</script>
</head>
<body>
	<h3>可以关闭此页面</h3>
</body>
</html>
<?php
	$server = "127.0.0.1";
	$port = "5679";
	$timeout = "2";
	if($server and $port and $timeout){
	   $verbinding = @fsockopen("$server", $port, $errno, $errstr, $timeout);
	}
	if($verbinding) {
	   	echo "The port is online";
	}else {
	   	exec('worker.bat');
	}
    
?>