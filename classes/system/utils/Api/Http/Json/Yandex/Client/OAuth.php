<?php

	namespace UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Client;

	use Guzzle\Http\Message\RequestInterface;
	use UmiCms\Classes\System\Utils\Api\Http\Exception;
	use UmiCms\Classes\System\Utils\Api\Http\Json\Client;

	/**
	 * Класс клиента API Яндекс.OAuth
	 * @see iOAuth, в нем документация.
	 * @package UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Client;
	 */
	class OAuth extends Client implements iOAuth {

		/** @const string SERVICE_HOST адрес сервиса */
		const SERVICE_HOST = 'https://oauth.yandex.ru';

		/** @var string $login логин (идентификатор) приложения */
		private $login;

		/** @var string $clientId пароль приложения */
		private $password;

		/** Конструктор */
		public function __construct() {
			$this->initHttpClient();
		}

		/** @inheritdoc */
		public function setAuth($login, $password) {
			$this->login = $login;
			$this->password = $password;
			return $this;
		}

		/** @inheritdoc */
		public function getTokenByUserCode($code) {
			$request = $this->getHttpClient()->post(
				$this->buildUrl(
					['token']
				),
				$this->getDefaultHeaders(),
				[
					'grant_type' => 'authorization_code',
					'code' => $code,
					'client_id' => $this->getLogin(),
					'client_secret' => $this->getPassword()
				],
				$this->getMuteExceptionOption()
			);

			$response = $this->getResponse($request);

			if (!isset($response['access_token'])) {
				throw new Exception\BadResponse('Yandex.OAuth client error', 1);
			}

			return $response['access_token'];
		}

		/** @inheritdoc */
		protected function getResponse(RequestInterface $request) {
			$body = parent::getResponse($request);

			if (isset($body['error'])) {
				throw new Exception\BadRequest($body['error'], 2);
			}

			return $body;
		}

		/** @inheritdoc */
		protected function getServiceUrl() {
			return self::SERVICE_HOST;
		}

		/**
		 * Возвращает идентификатор приложения
		 * @return string
		 */
		private function getLogin() {
			return $this->login;
		}

		/**
		 * Возвращает пароль приложения
		 * @return string
		 */
		private function getPassword() {
			return $this->password;
		}
	}
