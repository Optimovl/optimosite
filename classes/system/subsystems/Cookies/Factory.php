<?php

	namespace UmiCms\System\Cookies;

	/**
	 * Класс фабрики кук
	 * @package UmiCms\System\Cookies
	 */
	class Factory implements iFactory {

		/** @var string $path значение uri по умолчанию, в рамках которого будет действовать кука */
		private $path = '/';

		/** @var string $domain значение домена по умолчанию, в рамках которого будут действовать создаваемые куки */
		private $domain = '';

		/** @var bool $secure значение флага по умолчанию, что куку можно использовать только по https для создаваемых кук */
		private $secure = false;

		/**
		 * @var bool $forHttpOnly значение флага по умолчанию, что кука будет доступна только через протокол HTTP,
		 * то есть к ней не будет доступа из javascript, для создаваемых кук
		 */
		private $forHttpOnly = false;

		/** @inheritdoc */
		public function create($name, $value = '', $expirationTime = 0) {
			$cookie = new Cookie($name, $value, $expirationTime);
			return $cookie->setPath($this->path)
				->setDomain($this->domain)
				->setSecureFlag($this->secure)
				->setHttpOnlyFlag($this->forHttpOnly);
		}

		/**
		 * @inheritdoc
		 * Реализация основана на:
		 * https://github.com/symfony/symfony/blob/master/src/Symfony/Component/HttpFoundation/Cookie.php
		 */
		public function createFromHeader($header) {
			if (!is_string($header) || !is_int(mb_strpos($header, 'Set-Cookie:'))) {
				throw new \wrongParamException('Wrong header given');
			}

			$header = str_replace('Set-Cookie:', '', $header);

			$data = [
				'expires' => 0,
				'path' => '/',
				'domain' => '',
				'secure' => false,
				'httponly' => false
			];

			foreach (explode(';', $header) as $part) {
				if (contains($part, '=')) {
					list($key, $value) = explode('=', trim($part), 2);
					$key = trim($key);
					$value = trim($value);
				} else {
					$key = trim($part);
					$value = '';
				}

				if (!isset($data['name'])) {
					$data['name'] = $key;
					$data['value'] = $value;
					continue;
				}

				switch ($key = mb_strtolower($key)) {
					case 'name':
					case 'value':
						break;
					case 'max-age':
						$data['expires'] = time() + (int) $value;
						break;
					case 'secure': {
						$data['secure'] = true;
						break;
					}
					case 'httponly': {
						$data['httponly'] = true;
						break;
					}
					default:
						$data[$key] = $value;
						break;
				}
			}

			$cookie = $this->create((string) $data['name'], $data['value'], (int) $data['expires']);
			return $cookie->setPath((string) $data['path'])
				->setDomain((string) $data['domain'])
				->setSecureFlag((bool) $data['secure'])
				->setHttpOnlyFlag((bool) $data['httponly']);
		}

		/** @inheritdoc */
		public function setPath($path) {
			if (!is_string($path) || empty($path)) {
				throw new \wrongParamException('Wrong default cookie path given');
			}

			$this->path = $path;
			return $this;
		}

		/** @inheritdoc */
		public function setDomain($domain) {
			if (!is_string($domain)) {
				throw new \wrongParamException('Wrong default cookie domain given');
			}

			$this->domain = $domain;
			return $this;
		}

		/** @inheritdoc */
		public function setSecureFlag($flag) {
			if (!is_bool($flag)) {
				throw new \wrongParamException('Wrong default cookie secure flag given');
			}

			$this->secure = $flag;
			return $this;
		}

		/** @inheritdoc */
		public function setHttpOnlyFlag($flag) {
			if (!is_bool($flag)) {
				throw new \wrongParamException('Wrong default cookie http only flag given');
			}

			$this->forHttpOnly = $flag;
			return $this;
		}
	}
