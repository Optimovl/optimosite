<?php

	namespace UmiCms\System\Request\Http;

	/**
	 * Интерфейс http запроса
	 * @package UmiCms\System\Request\Http
	 */
	interface iRequest {

		/**
		 * Конструктор
		 * @param iCookies $cookies контейнер кук запроса
		 * @param iServer $server контейнер серверных переменных
		 * @param iPost $post контейнер POST параметров
		 * @param iGet $get контейнер GET параметров
		 * @param iFiles $files контейнер загруженных файлов
		 */
		public function __construct(iCookies $cookies, iServer $server, iPost $post, iGet $get, iFiles $files);

		/**
		 * Возвращает контейнер кук запроса
		 * @return iCookies
		 */
		public function Cookies();

		/**
		 * Возвращает контейнер серверных переменных
		 * @return iServer
		 */
		public function Server();

		/**
		 * Возвращает контейнер POST параметров
		 * @return iPost
		 */
		public function Post();

		/**
		 * Возвращает контейнер GET параметров
		 * @return iGet
		 */
		public function Get();

		/**
		 * Возвращает контейнер загруженных файлов
		 * @return iFiles
		 */
		public function Files();

		/**
		 * Возвращает метод
		 * @return string
		 */
		public function method();

		/**
		 * Определяет, что запрос произведен методом "POST"
		 * @return bool
		 */
		public function isPost();

		/**
		 * Определяет, что запрос произведен методом "GET"
		 * @return bool
		 */
		public function isGet();

		/**
		 * Возвращает хост
		 * @return string
		 */
		public function host();

		/**
		 * Возвращает user agent
		 * @return string
		 */
		public function userAgent();

		/**
		 * Возвращает ip адрес отправителя запроса
		 * @return string
		 */
		public function remoteAddress();

		/**
		 * Возвращает ip адрес сервера
		 * @return string
		 */
		public function serverAddress();

		/**
		 * Возвращает uri
		 * @return string
		 */
		public function uri();

		/**
		 * Возвращает query
		 * @return string
		 */
		public function query();

		/**
		 * Возвращает необработанные данные тела запроса
		 * @return string
		 */
		public function getRawBody();
	}
