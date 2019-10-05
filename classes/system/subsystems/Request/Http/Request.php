<?php

	namespace UmiCms\System\Request\Http;

	/**
	 * Класс http запроса
	 * @package UmiCms\System\Request\Http
	 */
	class Request implements iRequest {

		/** @var iCookies $cookies контейнер кук запроса */
		private $cookies;

		/** @var iServer $server контейнер серверных переменных */
		private $server;

		/** @var iPost $post контейнер POST параметров */
		private $post;

		/** @var iGet $get контейнер GET параметров */
		private $get;

		/** @var iFiles $files контейнер загруженных файлов */
		private $files;

		/** @inheritdoc */
		public function __construct(iCookies $cookies, iServer $server, iPost $post, iGet $get, iFiles $files) {
			$this->cookies = $cookies;
			$this->server = $server;
			$this->post = $post;
			$this->get = $get;
			$this->files = $files;
		}

		/** @inheritdoc */
		public function Cookies() {
			return $this->cookies;
		}

		/** @inheritdoc */
		public function Server() {
			return $this->server;
		}

		/** @inheritdoc */
		public function Post() {
			return $this->post;
		}

		/** @inheritdoc */
		public function Get() {
			return $this->get;
		}

		/** @inheritdoc */
		public function Files() {
			return $this->files;
		}

		/** @inheritdoc */
		public function method() {
			return $this->Server()->get('REQUEST_METHOD');
		}

		/** @inheritdoc */
		public function isPost() {
			return $this->method() === 'POST';
		}

		/** @inheritdoc */
		public function isGet() {
			return $this->method() === 'GET';
		}

		/** @inheritdoc */
		public function host() {
			return $this->Server()->get('HTTP_HOST');
		}

		/** @inheritdoc */
		public function userAgent() {
			return $this->Server()->get('HTTP_USER_AGENT');
		}

		/** @inheritdoc */
		public function remoteAddress() {
			return $this->Server()->get('REMOTE_ADDR');
		}

		/** @inheritdoc */
		public function serverAddress() {
			return $this->Server()->get('SERVER_ADDR');
		}

		/** @inheritdoc */
		public function uri() {
			return $this->Server()->get('REQUEST_URI');
		}

		/** @inheritdoc */
		public function query() {
			return $this->Server()->get('QUERY_STRING');
		}

		/** @inheritdoc */
		public function getRawBody() {
			return file_get_contents('php://input');
		}
	}
