<?php

	define('CRON', true);
	define('PACKER_WORKING_DIR', __DIR__);

	require_once __DIR__ . '/class/Packer.php';
	chdir(__DIR__ . '/../../');

	if (!file_exists('./standalone.php')) {
		die('Не найден standalone.php.' . PHP_EOL);
	}

	require_once 'standalone.php';

	if (!isset($argv)) {
		die('Пакер необходимо запускать через консоль.' . PHP_EOL);
	}

	if (!isset($argv[1])) {
		die('Первым параметром нужно передать путь до файла конфигурации пакера.' . PHP_EOL);
	}

	$configFilePath = $argv[1];
	try {
		$packer = new Packer($configFilePath);
		$packer->setExporter(
			new xmlExporter(
				$packer->getConfig('package')
			)
		);

		chdir(dirname(dirname(PACKER_WORKING_DIR)));
		$packer->run();
	} catch (Exception $e) {
		die($e->getMessage() . PHP_EOL);
	}
