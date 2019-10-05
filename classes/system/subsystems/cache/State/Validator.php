<?php

	namespace UmiCms\System\Cache\State;

	use UmiCms\System\Auth\iAuth;
	use UmiCms\System\Request\iFacade as iRequest;
	use UmiCms\System\Response\iFacade as iResponse;

	/**
	 * Класс валидатора состояния
	 * @package UmiCms\System\Cache\Statical\Request
	 */
	class Validator implements iValidator {

		/** @var iAuth $auth фасад авторизации и аутентификации */
		private $auth;

		/** @var iRequest $request запрос */
		private $request;

		/** @var \iCmsController $cmsController cms controller */
		private $cmsController;

		/** @var iResponse $response ответ */
		private $response;

		/** @inheritdoc */
		public function __construct(
			iAuth $auth,
			iRequest $request,
			\iCmsController $cmsController,
			iResponse $response
		) {
			$this->auth = $auth;
			$this->request = $request;
			$this->cmsController = $cmsController;
			$this->response = $response;
		}

		/** @inheritdoc */
		public function isValid() {
			if (!$this->isCorrectResponse()) {
				return false;
			}

			if (!$this->isGetRequest()) {
				return false;
			}

			if ($this->isAdminRequest()) {
				return false;
			}

			if ($this->isUserAuthorised()) {
				return false;
			}

			return $this->isPageRequest();
		}

		/**
		 * Определяет авторизован ли пользователь
		 * @return bool
		 */
		private function isUserAuthorised() {
			return $this->getAuth()
				->isAuthorized();
		}

		/**
		 * Определяет запрошен ли текущий запрос методов GET
		 * @return bool
		 */
		private function isGetRequest() {
			return $this->getRequest()
				->isGet();
		}

		/**
		 * Определяет работает ли система в административнов режиме
		 * @return bool
		 */
		private function isAdminRequest() {
			return $this->getRequest()
				->isAdmin();
		}

		/**
		 * Определяет корректен ли ответ на запрос
		 * @return bool
		 */
		private function isCorrectResponse() {
			return $this->getResponse()
				->isCorrect();
		}

		/**
		 * Определяет запрощена ли страница
		 * @return bool
		 */
		private function isPageRequest() {
			$pageIdDefined = (bool) $this->getCmsController()
				->getCurrentElementId();
			$htmlRequested = $this->getRequest()
				->isHtml();
			return $pageIdDefined && $htmlRequested;
		}

		/**
		 * Возвращает фасад авторизации и аутентификации
		 * @return iAuth
		 */
		private function getAuth() {
			return $this->auth;
		}

		/**
		 * Возвращает запрос
		 * @return iRequest
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает cms controller
		 * @return \iCmsController
		 */
		private function getCmsController() {
			return $this->cmsController;
		}

		/**
		 * Возвращает ответ
		 * @return iResponse
		 */
		private function getResponse() {
			return $this->response;
		}
	}
