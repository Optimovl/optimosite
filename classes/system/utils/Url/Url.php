<?php

	namespace UmiCms\System\Utils;

	/**
	 * Класс URL
	 * @package UmiCms\System
	 */
	class Url implements iUrl {

		/** @var string $scheme */
		private $scheme = '';

		/** @var string $host */
		private $host = '';

		/** @var int $port */
		private $port = 0;

		/** @var string $user */
		private $user = '';

		/** @var string $pass */
		private $pass = '';

		/** @var string $path */
		private $path = '';

		/** @var string $query */
		private $query = '';

		/** @var string $fragment */
		private $fragment = '';

		/** @inheritdoc */
		public function getScheme() {
			return $this->scheme;
		}

		/** @inheritdoc */
		public function setScheme($scheme) {
			$this->scheme = $scheme;
			return $this;
		}

		/** @inheritdoc */
		public function getHost() {
			return $this->host;
		}

		/** @inheritdoc */
		public function setHost($host) {
			$this->host = $host;
			return $this;
		}

		/** @inheritdoc */
		public function getPort() {
			return $this->port;
		}

		/** @inheritdoc */
		public function setPort($port) {
			$this->port = $port;
			return $this;
		}

		/** @inheritdoc */
		public function getUser() {
			return $this->user;
		}

		/** @inheritdoc */
		public function setUser($user) {
			$this->user = $user;
			return $this;
		}

		/** @inheritdoc */
		public function getPass() {
			return $this->pass;
		}

		/** @inheritdoc */
		public function setPass($pass) {
			$this->pass = $pass;
			return $this;
		}

		/** @inheritdoc */
		public function getPath() {
			return $this->path;
		}

		/** @inheritdoc */
		public function setPath($path) {
			$this->path = $path;
			return $this;
		}

		/** @inheritdoc */
		public function getQuery() {
			return $this->query;
		}
		/** @inheritdoc */
		public function setQuery($query) {
			$this->query = $query;
			return $this;
		}

		/** @inheritdoc */
		public function getQueryAsList() {
			parse_str($this->query, $queryList);
			return $queryList;
		}

		/** @inheritdoc */
		public function getFragment() {
			return $this->fragment;
		}

		/** @inheritdoc */
		public function setFragment($fragment) {
			$this->fragment = $fragment;
			return $this;
		}

		/** @inheritdoc */
		public function getUrl() {
			return $this->__toString();
		}

		/** @inheritdoc */
		public function __toString() {
			return $this->buildUrl();
		}

		/** @inheritdoc */
		public function merge(iUrl $target) {
			$scheme = $target->getScheme() ?: $this->getScheme();
			$user = $target->getUser() ?: $this->getUser();
			$pass = $target->getPass() ?: $this->getPass();
			$host = $target->getHost() ?: $this->getHost();
			$port = $target->getPort() ?: $this->getPort();
			$path = $target->getPath() ?: $this->getPath();
			$query = http_build_query($target->getQueryAsList() + $this->getQueryAsList());
			$fragment = $target->getFragment() ?: $this->getFragment();

			return $this->setScheme($scheme)
				->setUser($user)
				->setPass($pass)
				->setHost($host)
				->setPort($port)
				->setPath($path)
				->setQuery($query)
				->setFragment($fragment);
		}

		/**
		 * Строит url из текущих значений полей url
		 * @return string
		 */
		private function buildUrl() {
			$scheme = $this->getScheme() ? ($this->getScheme() . self::SCHEME_SUFFIX) : '';
			$user = $this->getUser() ? ($this->getUser() . self::COLON) : '';
			$password = $this->getPass() ? ($this->getPass() . self::PASSWORD_SUFFIX) : '';
			$port = $this->getPort() ? (self::COLON . $this->getPort()) : '';
			$query = $this->getQuery() ? (self::QUERY_IDENTIFIER . $this->getQuery()) : '';
			$fragment = $this->getFragment() ? (self::FRAGMENT_IDENTIFIER . $this->getFragment()) : '';

			return $scheme . $user . $password . $this->getHost() . $port . $this->getPath() . $query . $fragment;
		}
	}