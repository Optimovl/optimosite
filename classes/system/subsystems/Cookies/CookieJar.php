<?php

	namespace UmiCms\System\Cookies;

	use UmiCms\System\Request\Http\iCookies;
	use UmiCms\System\Protection\iEncrypter;

	/**
	 * Класс фасада для работ с куками
	 * @examples:
	 *
	 * 1) Получить значение куки от клиента: CookieJar->get('foo');
	 * 2) Установить куку для ответа клиенту: CookieJar->set('foo', 'bar', 1488);
	 * 3) Пришла ли кука от клиента: CookieJar->isExists('baz);
	 * 4) Удалить куку: CookieJar->remove('baz);
	 *
	 * @package UmiCms\System\Cookies
	 */
	class CookieJar implements iCookieJar {

		/** @var iFactory $factory экземпляр класса фабрики кук */
		private $factory;

		/** @var iResponsePool $response экземпляр класса списка кук, которые требуется отправить клиенту */
		private $response;

		/** @var iCookies $request экземпляр класса контейнера кук запроса */
		private $request;

		/** @var iEncrypter $encrypter шифровальщик */
		private $encrypter;

		/** @inheritdoc */
		public function __construct(
			iFactory $factory, iResponsePool $responsePool, iCookies $requestCookies, iEncrypter $encrypter
		) {
			$this->factory = $factory;
			$this->response = $responsePool;
			$this->request = $requestCookies;
			$this->encrypter = $encrypter;
		}

		/** @inheritdoc */
		public function get($name) {
			return $this->getRequest()
				->get($name);
		}

		/** @inheritdoc */
		public function getDecrypted($name) {
			$value = (string) $this->getRequest()
				->get($name);

			if (!isEmptyString($value)) {
				return $this->getEncrypter()
					->decrypt($value);
			}

			return $value ?: null;
		}

		/** @inheritdoc */
		public function set($name, $value = '', $expireTime = 0) {
			$cookie = $this->getFactory()
				->create($name, $value, $expireTime);

			$this->getResponsePool()
				->push($cookie);

			$this->getRequest()
				->set($name, $value);

			return $cookie;
		}

		/** @inheritdoc */
		public function setEncrypted($name, $value = '', $expireTime = 0) {
			$value = (string) $value;

			if (!isEmptyString($value)) {
				$value = $this->getEncrypter()
					->encrypt($value);
			}

			return $this->set($name, $value, $expireTime);
		}

		/** @inheritdoc */
		public function setFromHeader($header) {
			$cookie = $this->getFactory()
				->createFromHeader($header);

			$this->getResponsePool()
				->push($cookie);

			$this->getRequest()
				->set($cookie->getName(), $cookie->getValue());

			return $cookie;
		}

		/** @inheritdoc */
		public function isExists($name) {
			return $this->getRequest()
				->isExist($name);
		}

		/** @inheritdoc */
		public function remove($name) {
			$this->getRequest()
				->del($name);

			$cookie = $this->getResponsePool()
				->pull($name);

			$expiredCookieTime = time() - 3600;

			if ($cookie instanceof iCookie) {
				$cookie->setExpirationTime($expiredCookieTime);
			} else {
				$cookie = $this->getFactory()
					->create($name, '', $expiredCookieTime);
			}

			$this->getResponsePool()
				->push($cookie);

			return $this;
		}

		/** @inheritdoc */
		public function getResponsePool() {
			return $this->response;
		}

		/**
		 * Возвращает экземпляр класса фабрики кук
		 * @return iFactory
		 */
		private function getFactory() {
			return $this->factory;
		}

		/**
		 * Возвращает экземпляр класса контейнера кук запроса
		 * @return iCookies
		 */
		private function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает экземпляр шифровальщика
		 * @return iEncrypter
		 */
		private function getEncrypter() {
			return $this->encrypter;
		}
	}
