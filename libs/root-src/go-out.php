<?php

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	$url = getRequest('url');
	$host = getServer('HTTP_HOST') ? str_replace('www.', '', getServer('HTTP_HOST')) : false;
	$referer = getServer('HTTP_REFERER') ? parse_url(getServer('HTTP_REFERER')) : false;

	$refererHost = false;

	if ($referer && isset($referer['host'])) {
		$refererHost = $referer['host'];
	}

	/* @var HTTPOutputBuffer $buffer */
	$buffer = Service::Response()
		->getCurrentBuffer();
	$buffer->contentType('text/html');
	$buffer->charset('utf-8');

	if (!$url || !$refererHost || !$host || !contains($refererHost, $host)) {
		$buffer->status(404);
		$buffer->push("<html><head><meta name=\"ROBOTS\" CONTENT=\"NOINDEX, NOFOLLOW\" /></head><body></body></html>");
		$buffer->end();
	}

	$detector = Service::BrowserDetector();
	if ($detector->isRobot()) {
		$buffer->status(403);
		$buffer->push("<html><head><meta name=\"ROBOTS\" CONTENT=\"NOINDEX, NOFOLLOW\" /></head><body></body></html>");
		$buffer->end();
	}

	$buffer->redirect($url);
