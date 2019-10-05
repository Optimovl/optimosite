<?php

	namespace UmiCms\Classes\System\Utils\Api\Http\Xml;

	use Guzzle\Http\Message\Response;
	use UmiCms\Classes\System\Utils\Api\Http\Client as HttpClient;

	/**
	 * Абстрактный клиент некоторого сервиса, взаимодействующий с ним по http(s) с помощью xml
	 * @package UmiCms\Classes\System\Utils\Api\Http
	 */
	abstract class Client extends HttpClient {

		/**
		 * Возвращает заголовки http запроса по умолчанию
		 * @return array
		 */
		protected function getDefaultHeaders() {
			return [
				'Accept' => 'text/xml',
				'Content-Type' => 'text/xml; charset=utf-8'
			];
		}

		/**
		 * Возвращает содержимое тела ответа
		 * @param Response $response ответ
		 * @return \SimpleXMLElement
		 */
		protected function getResponseBody(Response $response) {
			return $response->xml();
		}
	}
