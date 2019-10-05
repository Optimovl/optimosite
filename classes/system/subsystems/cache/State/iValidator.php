<?php

	namespace UmiCms\System\Cache\State;

	use UmiCms\System\Auth\iAuth;
	use UmiCms\System\Request\iFacade as iRequest;
	use UmiCms\System\Response\iFacade as iResponse;

	/**
	 * Интерфейс валидатора состояния
	 * @package UmiCms\System\Cache\Statical
	 */
	interface iValidator {

		/**
		 * Конструктор
		 * @param iAuth $auth фасад авторизации и аутентификации
		 * @param iRequest $request запрос
		 * @param \iCmsController $cmsController
		 * @param iResponse $response ответ
		 */
		public function __construct(
			iAuth $auth,
			iRequest $request,
			\iCmsController $cmsController,
			iResponse $response
		);

		/**
		 * Валидирует запрос
		 * @return bool
		 */
		public function isValid();
	}
