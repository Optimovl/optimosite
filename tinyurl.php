<?php

	use UmiCms\Service;

	if (!isset($_REQUEST['id'])) {
		die();
	}

	$id = (int) $_REQUEST['id'];

	require_once 'standalone.php';

	$hierarchy = umiHierarchy::getInstance();
	$url = $hierarchy->getPathById($id);

	$buffer = Service::Response()
		->getCurrentBuffer();

	if ($url) {
		$buffer->redirect($url);
	} else {
		$buffer->status(404);
		$buffer->end();
	}

