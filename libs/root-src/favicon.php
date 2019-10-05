<?php
	require_once CURRENT_WORKING_DIR . '/libs/config.php';
	use UmiCms\Service;

	$domain = Service::DomainDetector()
		->detect();
	$favicon = $domain->getFavicon();
	$buffer = Service::Response()
		->getCurrentBuffer();

	$event = Service::EventPointFactory()
		->create('request-favicon', 'before');
	$event->addRef('favicon', $favicon)
		->setParam('buffer', $buffer)
		->setParam('domain', $domain)
		->call();

	if (!$favicon instanceof iUmiImageFile || $favicon->getIsBroken()) {
		$buffer->status('404 Not Found');
		$buffer->end();
	}

	$buffer->contentType($favicon->getMimeType());
	$buffer->push($favicon->getContent());

	$event->setMode('after')
		->call();

	$buffer->end();
