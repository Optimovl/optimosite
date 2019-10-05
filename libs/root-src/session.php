<?php
	/**
	 * Проверяет время жизни сессии или обновляет ее.
	 * Возвращает время, оставшееся до конца жизни сессии.
	 *
	 * Варианты запросов:
	 *
	 * 1) http://domain.com/session.php - возвращает время жизни сессии в секундах;
	 * 2) http://domain.com/session.php?a=ping - устанавливает (продлевает) время жизни сессии и возвращает его в сек;
	 * 3) http://domain.com/session.php?a=ping&u-login=sv&u-password=1 - устанавливает (продлевает) время жизни сессии,
	 * авторизует пользователя и возвращает время жизни сессии в секундах;
	 *
	 * Если время жизни сессии истекло или не удалось авторизоваться по присланным данным,
	 * то возвращается ключевое значение "-1".
	 *
	 * Используется для контроля времени жизни сессии в административной панели, /js/cms/session.js.
	 */

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	$pingAction = (getRequest('a') == 'ping');
	$session = Service::Session();

	if ($pingAction) {
		$session->startActiveTime();
	}

	$successfulAuth = false;

	if ($pingAction && getRequest('u-login') && getRequest('u-password')) {
		$auth = Service::Auth();
		try {
			$auth->loginByEnvironment();
			$successfulAuth = $auth->isAuthorized();
		} catch (UmiCms\System\Auth\AuthorizationException $e) {
			$successfulAuth = false;
		}
	}

	$expiredSessionLifeTime = '-1';

	switch (true) {
		case ($session->isActiveTimeExpired() === false) : {
			$sessionRemainingTime = $session->getActiveTime();
			break;
		}
		case ($successfulAuth) : {
			$sessionRemainingTime = $session->getMaxActiveTime() * $session::SECONDS_IN_ONE_MINUTE;
			break;
		}
		default : {
			$session->clear();
			$sessionRemainingTime = $expiredSessionLifeTime;
		}
	}

	$buffer = Service::Response()
		->getCurrentBuffer();
	$buffer->push($sessionRemainingTime);
	$buffer->end();
