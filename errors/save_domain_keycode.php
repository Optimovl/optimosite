<?php

	require_once '../libs/config.php';

	use UmiCms\Service;

	$registry = Service::Registry();

	if ($registry->checkSelfKeycode()) {
		exit();
	}

	if (is_file(SYS_TEMP_PATH . '/runtime-cache/registry')) {
		unlink(SYS_TEMP_PATH . '/runtime-cache/registry');
	}

	if (is_file(SYS_TEMP_PATH . '/runtime-cache/trash')) {
		unlink(SYS_TEMP_PATH . '/runtime-cache/trash');
	}

	$serverAddress = Service::Request()->serverAddress();
	$ip = isset($serverAddress) ? $serverAddress : str_replace("\\", '', Service::Request()->documentRoot());
	$currentDomain = Service::Request()->host();
	$keycode = getRequest('keycode');
	$domainKeycode = getRequest('domain_keycode');
	$licenseCodename = getRequest('license_codename');

	$domainCollection = Service::DomainCollection();
	$defaultDomain = $domainCollection
		->getDefaultDomain();

	$domainNameList = [];

	foreach ($domainCollection->getList() as $domain) {
		$domainNameList[] = $domain->getHost();
	}

	$isCurrentDomainInDomainList = in_array($currentDomain, $domainNameList);

	$isDomainNotDefault = $isCurrentDomainInDomainList && !$domainCollection->isDefaultDomain($currentDomain);
	$previousEdition = $registry->get('//settings/system_edition');

	if (($domainKeycode === null || $licenseCodename === null) && $keycode !== null) {
		// Проверка лицензионного ключа
		$params = [
			'ip' => $ip,
			'domain' => $currentDomain,
			'keycode' => $keycode,
			'previous_edition' => $previousEdition,
			'last_update_time' => $registry->get('//settings/last_updated')
		];
		$url = 'aHR0cDovL3Vkb2QudW1paG9zdC5ydS91ZGF0YTovL2N1c3RvbS9wcmltYXJ5Q2hlY2tDb2RlLw==';
		$url = base64_decode($url) . base64_encode(serialize($params)) . '/';
		$result = umiRemoteFileGetter::get(
			$url,
			false,
			false,
			false,
			false,
			false,
			30
		);

		header('Content-type: text/xml; charset=utf-8');
		$xml = simplexml_load_string($result);
		$xml->addChild('is_domain_not_default', (int) $isDomainNotDefault);

		echo $xml->asXML();
		exit();
	}

	if (mb_strlen(str_replace('-', '', $domainKeycode)) != 33) {
		exit();
	}

	if (!$licenseCodename) {
		exit();
	}

	$pro = ['commerce', 'business', 'corporate', 'commerce_enc', 'business_enc', 'corporate_enc', 'gov'];
	$internalCodeName = in_array($licenseCodename, $pro) ? 'pro' : $licenseCodename;
	$checkKey = umiTemplater::getSomething($internalCodeName, $currentDomain);

	if ($checkKey != mb_substr($domainKeycode, 12)) {
		exit();
	}

	try {
		$defaultDomain->setHost($currentDomain);
		$defaultDomain->commit();
	} catch (databaseException $exception) {
		if ($exception->getCode() == IConnection::DUPLICATE_KEY_ERROR_CODE) {
			$currentDomainId = $domainCollection->getDomainId($currentDomain);
			$domainCollection->setDefaultDomain($currentDomainId);
		} else {
			throw $exception;
		}
	}

	$registry->set('//settings/keycode', $domainKeycode);
	$registry->set('//settings/system_edition', $licenseCodename);
	$registry->set('//settings/previous_edition', $previousEdition);

	/** @var autoupdate|AutoUpdateService $moduleAutoUpdates */
	$moduleAutoUpdates = cmsController::getInstance()->getModule('autoupdate');
	$isAutoUpdateModuleCorrect = ($moduleAutoUpdates instanceof def_module);

	if ($isAutoUpdateModuleCorrect) {
		$moduleAutoUpdates->resetSupportTimeCache();

		if ($moduleAutoUpdates->isMethodExists('deleteIllegalComponents')) {
			$moduleAutoUpdates->deleteIllegalComponents();
		} elseif ($moduleAutoUpdates->isMethodExists('deleteIllegalModules')) {
			$moduleAutoUpdates->deleteIllegalModules();
		}
	}

	$oldServicePath = SYS_MODULES_PATH . 'autoupdate/ch_m.php';

	if (is_file($oldServicePath)) {
		include $oldServicePath;
		ch_remove_m_garbage();
	}
