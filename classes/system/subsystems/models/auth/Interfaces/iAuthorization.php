<?php

	namespace UmiCms\System\Auth;

	use UmiCms\System\Cookies;
	use UmiCms\System\Protection;
	use UmiCms\System\Session\iSession;

	/**
	 * Интерфейс авторизации
	 * @package UmiCms\System\Auth
	 */
	interface iAuthorization {

		/**
		 * Конструктор
		 * @param iSession $session контейнер сессии
		 * @param Protection\CsrfProtection $tokenGenerator генератор csrf токенов
		 * @param \iPermissionsCollection $permissionsCollection коллекция прав доступа
		 * @param Cookies\iCookieJar $cookieJar класс для работы с куками
		 * @param \iUmiObjectsCollection $umiObjectsCollection коллекция объектов
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(
			iSession $session,
			Protection\CsrfProtection $tokenGenerator,
			\iPermissionsCollection $permissionsCollection,
			Cookies\iCookieJar $cookieJar,
			\iUmiObjectsCollection $umiObjectsCollection,
			\iConfiguration $configuration
		);

		/**
		 * Авторизует пользователя, начинает сессию и отправляет авторизационные куки
		 * @param int $userId идентификатор авторизуемого пользователя
		 * @return iAuthorization
		 * @throws AuthorizationException
		 */
		public function authorize($userId);

		/**
		 * Авторизует пользователя.
		 * Применяется для авторизации на основе авторизационных кук, то есть
		 * когда нужно использовать существующую сессию, а не начинать новую.
		 * @param int $userId идентификатор авторизуемого пользователя
		 * @return iAuthorization
		 * @throws AuthorizationException
		 */
		public function authorizeStateless($userId);

		/**
		 * Возвращает идентификатор авторизованного пользователя
		 * @return int|null
		 */
		public function getAuthorizedUserId();

		/**
		 * Деавторизует пользователя, завершает сессию и авторизационные куки
		 * @return iAuthorization
		 */
		public function deAuthorize();

		/**
		 * Деавторизует пользователя
		 * @return iAuthorization
		 */
		public function deAuthorizeStateless();

		/**
		 * Авторизует пользователя, начинает сессию и отправляет авторизационные куки.
		 * Не генерирует новые идентификатор сессии.
		 * Применяется для интеграции по протоколу CommerceML2.0, так как интегрируемый сервис
		 * фиксирует идентификатор сессии в юми и отправляет куки с ним при каждом запросе.
		 * @param int $userId идентификатор авторизуемого пользователя
		 * @return iAuthorization
		 * @throws AuthorizationException
		 */
		public function authorizeUsingFixedSessionId($userId);

		/**
		 * Авторизует пользователя, начинает сессию и отправляет авторизационные куки.
		 * Сохраняет идентификатор предыдущего пользователя.
		 * Используется в связке с iAuthorization::authorizeUsingPreviousUserId().
		 * Применяется для оформления/переоформления заказа от имени пользователя.
		 * @param int $userId идентификатор авторизуемого пользователя.
		 * @return iAuthorization
		 * @throws AuthorizationException
		 */
		public function authorizeFakeUser($userId);

		/**
		 * Авторизует пользователя, начинает сессию и отправляет авторизационные куки.
		 * Использует для авторизации идентификатор предыдущего пользователя.
		 * Используется в связке с iAuthorization::authorizeFakeUser().
		 * Применяется для оформления/переоформления заказа от имени пользователя.
		 * @param int $previousUserId идентификатор предыдущего пользователя
		 * @return iAuthorization
		 * @throws AuthorizationException
		 */
		public function authorizeUsingPreviousUserId($previousUserId);
	}
