<?php

	namespace UmiCms\System\Auth;

	use UmiCms\System\Auth\AuthenticationRules;
	use UmiCms\System\Session\iSession;

	/**
	 * Интерфейс аутентификации пользователей
	 * @package UmiCms\System\Auth
	 */
	interface iAuthentication {

		/**
		 * Конструктор
		 * @param AuthenticationRules\iFactory $authenticationRulesFactory фабрика правил аутентификации
		 * @param iSession $session контейнер сессии
		 */
		public function __construct(AuthenticationRules\iFactory $authenticationRulesFactory, iSession $session);

		/**
		 * Аутентифицирует пользователя по логину и паролю
		 * @param string $login логин
		 * @param string $password пароль
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticate($login, $password);

		/**
		 * Аутентифицирует пользователя по логину и хешу
		 * @param string $login логин
		 * @param string $hash хеш пароля
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByLoginAndHash($login, $hash);

		/**
		 * Аутентифицирует пользователя по коду активации
		 * @param string $code код активации
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByCode($code);

		/**
		 * Аутентифицирует пользователя по логину и названию провайдера данных пользователя (социальной сети)
		 * @param string $login логин
		 * @param string $provider название провайдера данных пользователя (социальной сети)
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateBySocials($login, $provider);

		/**
		 * Аутентифицирует пользователя по идентификатору
		 * @param int $userId идентификатор пользователя
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByUserId($userId);

		/**
		 * Аутентифицирует пользователя по параметрам запроса (u-login и u-password).
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByRequestParams();

		/**
		 * Аутентифицирует пользователя по серверным заголовкам (u-login и u-password).
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByHeaders();

		/**
		 * Аутентифицирует пользователя по логину и паролю http авторизации (PHP_AUTH_USER и PHP_AUTH_PW).
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByHttpBasic();

		/**
		 * Аутентифицирует пользователя по параметру запроса http авторизации UMI.CMS (umi_authorization).
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByUmiHttpBasic();

		/**
		 * Аутентифицирует пользователя по сессии (user_id).
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateBySession();

		/**
		 * Аутентифицирует временного пользователя по идентификатору
		 * @param int $userId идентификатор пользователя
		 * @return mixed
		 */
		public function authenticateFakeUser($userId);

		/**
		 * Аутентифицирует пользователя по идентификатору предыдущего авторизованного пользователя (old_user_id).
		 * @return int идентификатор пользователя, если аутентификация прошла удачно
		 * @throws WrongCredentialsException
		 * @throws AuthenticationException
		 */
		public function authenticateByPreviousUserId();
	}
