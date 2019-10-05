<?php

	namespace UmiCms\System\Cookies;

	/**
	 * Класс куки
	 * @package UmiCms\System\Cookies
	 */
	class Cookie implements iCookie {

		/** @var string $name название */
		private $name;

		/** @var mixed $value значение */
		private $value = '';

		/** @var int $expirationTime время, когда срок действия истекает */
		private $expirationTime = 0;

		/** @var string $path uri, в рамках которого будет действовать кука */
		private $path = '/';

		/** @var string $domain домен (поддомен), в рамках которого будет действовать кука */
		private $domain = '';

		/** @var bool $secure флаг, что куку можно использовать только по https */
		private $secure = false;

		/**
		 * @var bool $forHttpOnly флаг, что кука будет доступна только через протокол HTTP, то есть к ней не будет
		 * доступа из javascript
		 */
		private $forHttpOnly = false;

		/** @inheritdoc */
		public function __construct($name, $value = '', $expirationTime = 0) {
			$this->setName($name)
				->setValue($value)
				->setExpirationTime($expirationTime);
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function getValue() {
			return $this->value;
		}

		/** @inheritdoc */
		public function setValue($value) {
			$this->value = $value;
			return $this;
		}

		/** @inheritdoc */
		public function getExpirationTime() {
			return $this->expirationTime;
		}

		/** @inheritdoc */
		public function setExpirationTime($time) {
			if (!is_int($time)) {
				throw new \wrongParamException('Wrong cookie expiration time given');
			}

			$this->expirationTime = $time;
			return $this;
		}

		/** @inheritdoc */
		public function getPath() {
			return $this->path;
		}

		/** @inheritdoc */
		public function setPath($path) {
			if (!is_string($path) || empty($path)) {
				throw new \wrongParamException('Wrong cookie path given');
			}

			$this->path = $path;
			return $this;
		}

		/** @inheritdoc */
		public function getDomain() {
			return $this->domain;
		}

		/** @inheritdoc */
		public function setDomain($domain) {
			if (!is_string($domain)) {
				throw new \wrongParamException('Wrong cookie domain given');
			}

			$this->domain = $domain;
			return $this;
		}

		/** @inheritdoc */
		public function isSecure() {
			return $this->secure;
		}

		/** @inheritdoc */
		public function setSecureFlag($flag) {
			if (!is_bool($flag)) {
				throw new \wrongParamException('Wrong cookie secure flag given');
			}

			$this->secure = $flag;
			return $this;
		}

		/** @inheritdoc */
		public function isForHttpOnly() {
			return $this->forHttpOnly;
		}

		/** @inheritdoc */
		public function setHttpOnlyFlag($flag) {
			if (!is_bool($flag)) {
				throw new \wrongParamException('Wrong cookie http only flag given');
			}

			$this->forHttpOnly = $flag;
			return $this;
		}

		/** @inheritdoc */
		private function setName($name) {
			if (!is_string($name) || empty($name)) {
				throw new \wrongParamException('Wrong cookie name given');
			}

			$this->name = $name;
			return $this;
		}
	}
