<?php

	namespace UmiCms\Classes\System\Utils\Api\Http\Json;

	use Guzzle\Http\Message\Response;
	use UmiCms\Classes\System\Utils\Api\Http\Client as HttpClient;

	/**
	 * Абстрактный клиент некоторого сервиса, взаимодействующий с ним по http(s) с помощью json
	 * @package UmiCms\Classes\System\Utils\Api\Http
	 */
	abstract class Client extends HttpClient {

		/**
		 * Возвращает заголовки http запроса по умолчанию
		 * @return array
		 */
		protected function getDefaultHeaders() {
			return [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json;charset=UTF-8'
			];
		}

		/**
		 * Возвращает содержимое тела ответа
		 * @param Response $response ответ
		 * @return array
		 */
		protected function getResponseBody(Response $response) {
			$body = $response->getBody(true);
			return empty($body) ? [] : $response->json();
		}

		/**
		 * Кодирует данные, которые требуется передать POST
		 * @param \stdClass|array $data данные
		 * @return string
		 */
		protected function encodePostData($data) {
			return json_encode($data);
		}
	}
