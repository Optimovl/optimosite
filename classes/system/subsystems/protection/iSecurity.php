<?php
	namespace UmiCms\System\Protection;

	use UmiCms\System\Request\iFacade;

	/**
	 * Интфейс фасада защиты системы
	 * @package UmiCms\System\Protection
	 */
	interface iSecurity {

		/**
		 * Конструктор
		 * @param iFacade $request фасад запрос
		 * @param \iConfiguration $configuration конфигурация
		 * @param iCsrfProtection $csrfProtection защита от csrf атак
		 * @param iHashComparator $hashComparator сравнитель хэшей
		 */
		public function __construct(iFacade $request, \iConfiguration $configuration,
			iCsrfProtection $csrfProtection, iHashComparator $hashComparator);

		/**
		 * Выполняет проверку безопасности на наличие CSRF-атаки
		 * @return bool true если проверка безопасности пройдена успешно
		 * @throws CsrfException если проверка безопасности провалена
		 * @throws \coreException
		 */
		public function checkCsrf();

		/**
		 * Выполняет проверку безопасности на валидность заголовка Origin
		 * @return bool true если проверка безопасности пройдена успешно
		 * @throws OriginException если проверка безопасности провалена
		 * @throws \coreException
		 */
		public function checkOrigin();

		/**
		 * Выполняет проверку безопасности на валидность заголовка Referer
		 * @return bool true если проверка безопасности пройдена успешно
		 * @throws ReferrerException если проверка безопасности провалена
		 */
		public function checkReferrer();

		/**
		 * Сравнивает два хэша на идентичность
		 * @param string $knownHash хэш, с которым будет производиться сравнение
		 * @param string $userHash проверяемый хэш
		 * @return bool
		 */
		public function hashEquals($knownHash, $userHash);
	}