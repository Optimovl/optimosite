<?php

	$config = mainConfiguration::getInstance();
	$schemeList = $config->get('streams', 'enable');

	if (is_array($schemeList)) {
		foreach ($schemeList as $scheme) {
			try {
				umiBaseStream::registerStream($scheme);
			} catch (Exception $exception) {
				umiExceptionHandler::report($exception);
			}
		}
	}

	$userAgent = $config->get('streams', 'user-agent');

	if ($userAgent) {
		$options = [
			'http' => [
				'user_agent' => $userAgent,
			]
		];

		$context = stream_context_create($options);
		libxml_set_streams_context($context);
	}
