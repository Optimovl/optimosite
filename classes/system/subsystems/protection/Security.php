<?php

	namespace UmiCms\System\Protection;

	use UmiCms\Service;
	use UmiCms\System\Request\iFacade;

	/**
	 * Класс фасада защиты системы
	 * @example
	 *
	 * use UmiCms\Service;
	 *
	 * try {
	 *        Service::Protection()->checkCsrf();
	 *        // Проверка безопасности пройдена успешно
	 * } catch (\UmiCms\System\Protection\CsrfException $e) {
	 *        // Проверка безопасности провалена
	 * }
	 *
	 * @package UmiCms\System\Protection
	 */
	class Security implements iSecurity {

		/** @var iFacade $request http запрос */
		private $request;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var iCsrfProtection $csrfProtection защита от csrf атак */
		private $csrfProtection;

		/** @var iHashComparator $hashComparator сравнитель хэшей */
		private $hashComparator;

		/** @inheritdoc */
		public function __construct(iFacade $request, \iConfiguration $configuration,
			iCsrfProtection $csrfProtection, iHashComparator $hashComparator) {
			$this->setRequest($request)
				->setConfiguration($configuration)
				->setCsrfProtection($csrfProtection)
				->setHashComparator($hashComparator);
		}

		/** @inheritdoc */
		public function checkCsrf() {
			if (!$this->getConfiguration()->get('kernel', 'csrf_protection')) {
				return true;
			}

			try {
				$this->checkOrigin();
				$this->checkReferrer();
			} catch (\InvalidArgumentException $e) {
				/* Заголовки не были переданы */
			} catch (\Exception $e) {
				throw new CsrfException($e->getMessage());
			}

			$request = $this->getRequest();
			$token = $request->Post()->get('csrf');
			$token = $token ?: $request->Get()->get('csrf');

			$this->getCsrfProtection()
				->checkTokenMatch($token);

			return true;
		}

		/** @inheritdoc */
		public function checkOrigin() {
			try {
				return $this->getCsrfProtection()
					->checkOriginCorrect($this->getRequest()->Server()->get('HTTP_ORIGIN'));
			} catch (CsrfException $e) {
				throw new OriginException(getLabel('error-no-domain-permissions'));
			}
		}

		/** @inheritdoc */
		public function checkReferrer() {
			try {
				return $this->getCsrfProtection()
					->checkReferrerCorrect($this->getRequest()->Server()->get('HTTP_REFERER'));
			} catch (CsrfException $e) {
				throw new ReferrerException(getLabel('error-no-domain-permissions'));
			}
		}

		/** @inheritdoc */
		public function hashEquals($knownHash, $userHash) {
			return $this->getHashComparator()
				->equals($knownHash, $userHash);
		}

		/**
		 * Возвращает http запрос
		 * @return iFacade
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Устанавливает http запрос
		 * @param iFacade $request запрос
		 * @return $this
		 */
		private function setRequest(iFacade $request) {
			$this->request = $request;
			return $this;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Устанавливает конфигурацию
		 * @param \iConfiguration $configuration конфигурация
		 * @return $this
		 */
		private function setConfiguration(\iConfiguration $configuration) {
			$this->configuration = $configuration;
			return $this;
		}

		/**
		 * Возвращает защиту от csrf атак
		 * @return iCsrfProtection
		 */
		private function getCsrfProtection() {
			return $this->csrfProtection;
		}

		/**
		 * Устанавливает защиту от csrf атак
		 * @param iCsrfProtection $csrfProtection защита от csrf атак
		 * @return $this
		 */
		private function setCsrfProtection(iCsrfProtection $csrfProtection) {
			$this->csrfProtection = $csrfProtection;
			return $this;
		}

		/**
		 * Возвращает сравнителя хэшей
		 * @return iHashComparator
		 */
		private function getHashComparator() {
			return $this->hashComparator;
		}

		/**
		 * Устанавливает сравнителя хэшей
		 * @param iHashComparator $hashComparator сравнитель хэшей
		 * @return $this
		 */
		private function setHashComparator(iHashComparator $hashComparator) {
			$this->hashComparator = $hashComparator;
			return $this;
		}

		/** @deprecated  */
		public static function getInstance() {
			return Service::Protection();
		}
	}
