<?php

	namespace UmiCms\System\Auth;

	use UmiCms\System\Permissions\iSystemUsersPermissions;

	/**
	 * Интерфейс авторизации пользователей
	 * @package UmiCms\System\Auth
	 */
	interface iAuth {

		/**
		 * Конструктор
		 * @param iAuthentication $authentication аутентификация
		 * @param iAuthorization $authorization авторизация
		 * @param iSystemUsersPermissions $systemUsersPermissions класс прав системных пользователей
		 */
		public function __construct(
			iAuthentication $authentication,
			iAuthorization $authorization,
			iSystemUsersPermissions $systemUsersPermissions
		);

		/**
		 * Проверяет есть пользователь с заданными логином и паролем
		 * @param string $login логин
		 * @param string $password пароль
		 * @return int|bool идентификатор найденного пользователя или false
		 */
		public function checkLogin($login, $password);

		/**
		 * Проверяет есть пользователь с заданными кодом активации
		 * @param string $code код активации
		 * @return int|bool идентификатор найденного пользователя или false
		 */
		public function checkCode($code);

		/**
		 * Авторизует пользователя по логину и паролю
		 * @param string $login логин
		 * @param string $password пароль
		 * @return bool возвращает результат операции
		 */
		public function login($login, $password);

		/**
		 * Авторизует пользователя по идентификатору
		 * @param int $userId идентификатор пользователя
		 * @return bool возвращает результат операции
		 */
		public function loginUsingId($userId);

		/**
		 * Авторизует пользователя без сохранения состояния в сессию и куки
		 * @param int $userId идентификатор пользователя
		 * @return bool возвращает результат операции
		 */
		public function loginOnce($userId);

		/**
		 * Авторизует пользователя по коду активации
		 * @param string $code код активации
		 * @return bool возвращает результат операции
		 */
		public function loginUsingCode($code);

		/**
		 * Авторизует пользователя, зарегистрированного через социальные сети
		 * @param string $login логин
		 * @param string $provider идентификатор социальной сети
		 * @return bool возвращает результат операции
		 */
		public function loginBySocials($login, $provider);

		/**
		 * Сохраняет текущий идентификатор пользователя и авторизует пользователя под новым
		 * Используется в связке с методом iAuth::loginUsingPreviousUserId()
		 * @param int $userId новый идентификатор пользователя
		 * @return bool возвращает результат операции
		 */
		public function loginAsFakeUser($userId);

		/**
		 * Авторизует пользователя под предыдущим идентификатором
		 * Используется в связке с методом iAuth::loginAsFakeUser()
		 * @return bool возвращает результат операции
		 */
		public function loginUsingPreviousUserId();

		/**
		 * Авторизован ли пользователь с гостевыми правами
		 * @return bool
		 */
		public function isLoginAsGuest();

		/**
		 * Авторизован ли пользователь с правами супервайзера
		 * @return bool
		 */
		public function isLoginAsSv();

		/**
		 * Авторизован ли пользователь (не под гостем)
		 * @return bool
		 */
		public function isAuthorized();

		/**
		 * Авторизует пользователя с гостевыми правами
		 * @return bool возвращает результат операции
		 */
		public function loginAsGuest();

		/**
		 * Авторизует пользователя с правами супервайзера
		 * @return bool возвращает результат операции
		 */
		public function loginAsSv();

		/**
		 * Возвращает идентификатор текущего пользователя
		 * @return int
		 */
		public function getUserId();

		/**
		 * Деавторизует пользователя
		 * @return bool возвращает результат операции
		 */
		public function logout();

		/**
		 * Деавторизует пользователя без изменения сессии и кук
		 * @return bool возвращает результат операции
		 */
		public function logoutOnce();

		/**
		 * Пытается авторизовать пользователя на основе данных HTTP запроса и сессии.
		 * Не стоит использовать в прикладном коде, так как метод предполагает только один вызов.
		 * @throws AuthenticationException
		 * @throws WrongCredentialsException
		 */
		public function loginByEnvironment();
	}
