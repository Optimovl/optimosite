<?php

	namespace UmiCms\System\Auth;

	use UmiCms\System\Auth\AuthenticationRules;
	use UmiCms\System\Session\iSession;

	/**
	 * Класс аутентификации пользователей
	 * @package UmiCms\System\Auth
	 */
	class Authentication implements iAuthentication {

		/** @var AuthenticationRules\iFactory $authenticationRulesFactory фабрика правил аутентификации */
		private $authenticationRulesFactory;

		/** @var iSession $session контейнер сессии */
		private $session;

		/** @inheritdoc */
		public function __construct(AuthenticationRules\iFactory $authenticationRulesFactory, iSession $session) {
			$this->authenticationRulesFactory = $authenticationRulesFactory;
			$this->session = $session;
		}

		/** @inheritdoc */
		public function authenticate($login, $password) {
			$this->validateLogin($login);
			$this->validatePassword($password);

			$userId = $this->getAuthenticationRulesFactory()
				->createByLoginAndPassword($login, $password)
				->validate();

			if ($userId === false) {
				throw new AuthenticationException("Cannot authenticate user by login = {$login} and password = {$password}");
			}

			return $userId;
		}

		/** @inheritdoc */
		public function authenticateByLoginAndHash($login, $hash) {
			$this->validateLogin($login);
			$this->validatePassword($hash);
			$userId = $this->getAuthenticationRulesFactory()
				->createByLoginAndHash($login, $hash)
				->validate();

			if ($userId === false) {
				throw new AuthenticationException("Cannot authenticate user by login = {$login} and hash = {$hash}");
			}

			return $userId;
		}

		/** @inheritdoc */
		public function authenticateByCode($code) {
			$this->validateCode($code);

			$userId = $this->getAuthenticationRulesFactory()
				->createByActivationCode($code)
				->validate();

			if ($userId === false) {
				throw new AuthenticationException("Cannot authenticate user by activation code = {$code}");
			}

			return $userId;
		}

		/** @inheritdoc */
		public function authenticateBySocials($login, $provider) {
			$this->validateLogin($login);
			$this->validateProvider($provider);

			$userId = $this->getAuthenticationRulesFactory()
				->createByLoginAndProvider($login, $provider)
				->validate();

			if ($userId === false) {
				throw new AuthenticationException("Cannot authenticate user by login = {$login} and provider = {$provider}");
			}

			return $userId;
		}

		/** @inheritdoc */
		public function authenticateByUserId($userId) {
			$this->validateUserId($userId);

			$userId = $this->getAuthenticationRulesFactory()
				->createByUserId($userId)
				->validate();

			if ($userId === false) {
				throw new AuthenticationException("Cannot authenticate user by id = {$userId}");
			}

			return $userId;
		}

		/** @inheritdoc */
		public function authenticateByRequestParams() {
			$login = getRequest('u-login');
			$password = getRequest('u-password');

			try {
				return $this->authenticate($login, $password);
			} catch (AuthenticationException $exception) {
				$hash = getRequest('u-password-md5') ?: getRequest('u-password-hash');
				$hash = $this->isUmiManagerHash($hash) ? '' : $hash;

				return $this->authenticateByLoginAndHash($login, $hash);
			}
		}

		/** @inheritdoc */
		public function authenticateByHeaders() {
			$login = getServer('u-login');
			$password = getServer('u-password');

			return $this->authenticate($login, $password);
		}

		/** @inheritdoc */
		public function authenticateByHttpBasic() {
			$login = getServer('PHP_AUTH_USER');
			$password = getServer('PHP_AUTH_PW');

			return $this->authenticate($login, $password);
		}

		/** @inheritdoc */
		public function authenticateByUmiHttpBasic() {
			$rawAuthenticationParams = getRequest('umi_authorization');
			$authenticationParams = explode(':', base64_decode(mb_substr($rawAuthenticationParams, 6)));

			if (umiCount($authenticationParams) != 2) {
				throw new WrongCredentialsException('Cannot parse umi_authorization param');
			}

			list($login, $password) = $authenticationParams;

			return $this->authenticate($login, $password);
		}

		/** @inheritdoc */
		public function authenticateBySession() {
			$userId = $this->getSession()
				->get('user_id');
			return $this->authenticateByUserId($userId);
		}

		/** @inheritdoc */
		public function authenticateFakeUser($userId) {
			$this->validateUserId($userId);
			$validId = $this->getAuthenticationRulesFactory()
				->createByFakeUser($userId)
				->validate();

			if ($validId === false) {
				throw new AuthenticationException("Cannot authenticate fake user by id = {$userId}");
			}

			return $validId;
		}

		/** @inheritdoc */
		public function authenticateByPreviousUserId() {
			$session = $this->getSession();

			if (!$session->get('fake-user')) {
				throw new WrongCredentialsException('fake-user flag expected for authenticate by previous user id');
			}

			$userId = $session->get('old_user_id');

			return $this->authenticateByUserId($userId);
		}

		/**
		 * Валидирует идентификатор пользователя
		 * @param int $userId идентификатор пользователя
		 * @throws WrongCredentialsException
		 */
		private function validateUserId($userId) {
			if (!is_int($userId) || $userId <= 0) {
				throw new WrongCredentialsException('Wrong user id given, integer > 0 expected');
			}
		}

		/**
		 * Валидирует логин пользователя
		 * @param string $login логин
		 * @throws WrongCredentialsException
		 */
		private function validateLogin($login) {
			$this->validateNotEmptyString($login, 'login');
		}

		/**
		 * Валидирует пароль пользователя
		 * @param string $password пароль
		 * @throws WrongCredentialsException
		 */
		private function validatePassword($password) {
			$this->validateNotEmptyString($password, 'password');
		}

		/**
		 * Валидирует название провайдера данных пользователя (социальной сети)
		 * @param string $provider название провайдера данных пользователя (социальной сети)
		 * @throws WrongCredentialsException
		 */
		private function validateProvider($provider) {
			$this->validateNotEmptyString($provider, 'provider');
		}

		/**
		 * Валидирует код активации пользователя
		 * @param string $code код активации пользователя
		 * @throws WrongCredentialsException
		 */
		private function validateCode($code) {
			$this->validateNotEmptyString($code, 'activation code');
		}

		/**
		 * Валидирует значение параметра, оно должно содежать непустую строку
		 * @param string $string значение параметра
		 * @param string $name валидируемые название параметра
		 * @throws WrongCredentialsException
		 */
		private function validateNotEmptyString($string, $name) {
			if (!is_string($string) || $string === '') {
				throw new WrongCredentialsException("Wrong user {$name} given, not empty string expected");
			}
		}

		/**
		 * Возвращает фабрику правил аутентификации
		 * @return AuthenticationRules\iFactory
		 */
		private function getAuthenticationRulesFactory() {
			return $this->authenticationRulesFactory;
		}

		/**
		 * Возвращает контейнер сессии
		 * @return iSession
		 */
		private function getSession() {
			return $this->session;
		}

		/**
		 * Проверяет является ли хэш хэшем из
		 * мобильного приложения UMI.Manager
		 * @param string $hash
		 * @return bool
		 */
		private function isUmiManagerHash($hash) {
			return $hash === 'null';
		}
	}
