<?php

	chdir(dirname(__FILE__));
	include '../../../developerTools/jsPacker/class.JavaScriptPacker.php';
	include '../../../developerTools/jsPacker/jsPacker.php';

	$xml = @simplexml_load_file('compress.xml');

	if (!$xml) {
		die('// No valid source for packer');
	}

	foreach ($xml->packages->pack as $pack) {
		$fileResult = (string) $pack['path'];
		$sourceFiles = [];
		foreach ($pack->file as $file) {
			$sourceFiles[] = (string) $file['path'];
		}
		if (count($sourceFiles) > 0) {
			$packer = new jsPacker($sourceFiles);
			$packer->pack($fileResult);
		}
	}

