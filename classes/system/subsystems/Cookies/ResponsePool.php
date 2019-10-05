<?php

	namespace UmiCms\System\Cookies;

	/**
	 * Класс списка кук, которые требуется отправить клиенту
	 * @package UmiCms\System\Cookies
	 */
	class ResponsePool implements iResponsePool {

		/** @var array $cookieList список кук, которые требуется отправить клиенту */
		private $cookieList = [];

		/** @inheritdoc */
		public function push(iCookie $cookie) {
			$this->cookieList[$cookie->getName()] = $cookie;
			return $this;
		}

		/** @inheritdoc */
		public function pull($name) {
			$cookie = $this->get($name);

			if ($cookie === null) {
				return $cookie;
			}

			unset($this->cookieList[$name]);
			return $cookie;
		}

		/** @inheritdoc */
		public function isExists($name) {
			return array_key_exists($name, $this->cookieList);
		}

		/** @inheritdoc */
		public function get($name) {
			if (!$this->isExists($name)) {
				return null;
			}

			return $this->cookieList[$name];
		}

		/** @inheritdoc */
		public function getList() {
			return $this->cookieList;
		}

		/** @inheritdoc */
		public function remove($name) {
			unset($this->cookieList[$name]);
			return $this;
		}

		/** @inheritdoc */
		public function clear() {
			$this->cookieList = [];
			return $this;
		}
	}
