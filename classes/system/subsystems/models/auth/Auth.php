<?php

	namespace UmiCms\System\Auth;

	use UmiCms\System\Permissions\iSystemUsersPermissions;

	/**
	 * Фасад для работы с аутентификацией и авторизацией пользователя
	 * @package UmiCms\System\Auth
	 */
	class Auth implements iAuth {

		/** @var iAuthentication $authentication аутентификация */
		private $authentication;

		/** @var iAuthorization $authorization авторизация */
		private $authorization;

		/** @var iSystemUsersPermissions $systemUserPermissions класс прав системных пользователей */
		private $systemUserPermissions;

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
		) {
			$this->authentication = $authentication;
			$this->authorization = $authorization;
			$this->systemUserPermissions = $systemUsersPermissions;
		}

		/** @inheritdoc */
		public function checkLogin($login, $password) {
			try {
				$userId = $this->getAuthentication()
					->authenticate($login, $password);
			} catch (AuthenticationException $e) {
				return false;
			}

			return $userId;
		}

		/** @inheritdoc */
		public function checkCode($code) {
			try {
				$userId = $this->getAuthentication()
					->authenticateByCode($code);
			} catch (AuthenticationException $e) {
				return false;
			}

			return $userId;
		}

		/** @inheritdoc */
		public function login($login, $password) {
			try {
				$userId = $this->getAuthentication()
					->authenticate($login, $password);
			} catch (AuthenticationException $e) {
				return false;
			}

			try {
				$this->getAuthorization()
					->authorize($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function loginUsingId($userId) {
			try {
				$userId = $this->getAuthentication()
					->authenticateByUserId($userId);
			} catch (AuthenticationException $e) {
				return false;
			}

			try {
				$this->getAuthorization()
					->authorize($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function loginOnce($userId) {
			try {
				$userId = $this->getAuthentication()
					->authenticateByUserId($userId);
			} catch (AuthenticationException $e) {
				return false;
			}

			try {
				$this->getAuthorization()
					->authorizeStateless($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function loginUsingCode($code) {
			try {
				$userId = $this->getAuthentication()
					->authenticateByCode($code);
			} catch (AuthenticationException $e) {
				return false;
			}

			try {
				$this->getAuthorization()
					->authorize($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function loginBySocials($login, $provider) {
			try {
				$userId = $this->getAuthentication()
					->authenticateBySocials($login, $provider);
			} catch (AuthenticationException $e) {
				return false;
			}

			try {
				$this->getAuthorization()
					->authorize($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/**
		 *
		 * @param int $userId ИД пользователя или гостя-покупателя
		 * @return bool
		 */
		public function loginAsFakeUser($userId) {
			try {
				$userId = $this->getAuthentication()
					->authenticateFakeUser($userId);
			} catch (AuthenticationException $e) {
				return false;
			}

			try {
				$this->getAuthorization()
					->authorizeFakeUser($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function loginUsingPreviousUserId() {
			try {
				$userId = $this->getAuthentication()
					->authenticateByPreviousUserId();
			} catch (AuthenticationException $e) {
				return false;
			}

			try {
				$this->getAuthorization()
					->authorizeUsingPreviousUserId($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function isLoginAsGuest() {
			return $this->getUserId() == $this->getSystemUsersPermissions()
					->getGuestUserId();
		}

		/** @inheritdoc */
		public function isLoginAsSv() {
			return $this->getUserId() == $this->getSystemUsersPermissions()
					->getSvUserId();
		}

		/** @inheritdoc */
		public function isAuthorized() {
			return !$this->isLoginAsGuest();
		}

		/** @inheritdoc */
		public function loginAsGuest() {
			$userId = $this->getSystemUsersPermissions()
				->getGuestUserId();

			try {
				$this->getAuthorization()
					->authorize($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function loginAsSv() {
			$userId = $this->getSystemUsersPermissions()
				->getSvUserId();

			try {
				$this->getAuthorization()
					->authorize($userId);
			} catch (AuthorizationException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function getUserId() {
			$authorizedUserId = $this->getAuthorization()
				->getAuthorizedUserId();

			if ($authorizedUserId !== null) {
				return (int) $authorizedUserId;
			}

			return $this->getSystemUsersPermissions()
				->getGuestUserId();
		}

		/** @inheritdoc */
		public function logout() {
			$this->getAuthorization()
				->deAuthorize();
			return $this->loginAsGuestOnce();
		}

		/** @inheritdoc */
		public function logoutOnce() {
			$this->getAuthorization()
				->deAuthorizeStateless();
			return $this->loginAsGuestOnce();
		}

		/** @inheritdoc */
		public function loginByEnvironment() {
			$authorization = $this->getAuthorization();
			$userId = $this->authenticateByHttpAuth();

			if ($userId !== false) {
				$authorization->authorizeUsingFixedSessionId($userId);
				return;
			}

			if (defined('PRE_AUTH_ENABLED') && PRE_AUTH_ENABLED) {
				$userId = $this->authenticateByRequest();

				if ($userId !== false) {
					$authorization->authorize($userId);
					return;
				}
			}

			$userId = $this->authenticateBySession();
			$guestId = $this->getSystemUsersPermissions()
				->getGuestUserId();
			$userId = ($userId === false) ? $guestId : $userId;
			$authorization->authorizeStateless($userId);
		}

		/**
		 * Авторизует пользователя с гостевыми правами без сохранения состояния в сессию и куки
		 * @return bool
		 */
		private function loginAsGuestOnce() {
			$guestId = $this->getSystemUsersPermissions()
				->getGuestUserId();
			return $this->loginOnce($guestId);
		}

		/**
		 * Пытается аутентифицировать пользователя на основе данных http авторизации
		 * @return bool|int идентификатор пользователя или false, если его не удалось определить
		 * @throws AuthenticationException
		 */
		private function authenticateByHttpAuth() {
			try {
				$userId = $this->getAuthentication()
					->authenticateByHttpBasic();
			} catch (WrongCredentialsException $e) {
				$userId = false;
			}

			if ($userId !== false) {
				return $userId;
			}

			try {
				$userId = $this->getAuthentication()
					->authenticateByUmiHttpBasic();
			} catch (WrongCredentialsException $e) {
				$userId = false;
			}

			return $userId;
		}

		/**
		 * Пытается аутентифицировать пользователя на основе данных запроса
		 * @return bool|int идентификатор пользователя или false, если его не удалось определить
		 * @throws AuthenticationException
		 */
		private function authenticateByRequest() {
			try {
				$userId = $this->getAuthentication()
					->authenticateByRequestParams();
			} catch (WrongCredentialsException $e) {
				$userId = false;
			}

			if ($userId !== false) {
				return $userId;
			}

			try {
				$userId = $this->getAuthentication()
					->authenticateByHeaders();
			} catch (WrongCredentialsException $e) {
				$userId = false;
			}

			return $userId;
		}

		/**
		 * Пытается аутентифицировать пользователя на основе данных текущей сессии
		 * @return bool|int идентификатор пользователя или false, если его не удалось определить
		 */
		private function authenticateBySession() {
			try {
				$userId = $this->getAuthentication()
					->authenticateBySession();
			} catch (AuthenticationException $e) {
				$userId = false;
			}

			return $userId;
		}

		/**
		 * Возвращает класс аутентификации
		 * @return iAuthentication
		 */
		private function getAuthentication() {
			return $this->authentication;
		}

		/**
		 * Возвращает класс авторизации
		 * @return iAuthorization
		 */
		private function getAuthorization() {
			return $this->authorization;
		}

		/**
		 * Возвращает класс прав системных пользователей
		 * @return iSystemUsersPermissions
		 */
		private function getSystemUsersPermissions() {
			return $this->systemUserPermissions;
		}
	}
