<?
	include "standalone.php";
	
	$path = getRequest('path');

	
	$param=explode ("/", $path);
	//print_r($param);
	$pagetopdf=run_standalone('pagetopdf');
	$pagetopdf->file($param[0], $param[1], $param[2], $param[3]);
?>
