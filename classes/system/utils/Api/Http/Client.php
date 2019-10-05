<?php

	namespace UmiCms\Classes\System\Utils\Api\Http;

	use Guzzle\Common\Exception\RuntimeException;
	use Guzzle\Http\Client as HttpClient;
	use Guzzle\Http\Message\RequestInterface;
	use Guzzle\Http\Message\Response;

	/**
	 * Абстрактный клиент некоторого сервиса, взаимодействующий с ним по http(s)
	 * @package UmiCms\Classes\System\Utils\Api\Http
	 */
	abstract class Client {

		/** @var HttpClient|null $httpClient http клиент */
		private $httpClient;

		/** @var array $lastRequestData данные последнего POST или PUT запроса */
		private $lastRequestData = [];

		/**
		 * Возвращает адрес сервиса
		 * @return string
		 */
		abstract protected function getServiceUrl();

		/**
		 * Возвращает http клиент
		 * @return HttpClient
		 */
		protected function getHttpClient() {
			return $this->httpClient;
		}

		/**
		 * Инициализирует http клиент
		 * @return $this
		 */
		protected function initHttpClient() {
			$this->httpClient = new HttpClient(
				$this->getServiceUrl()
			);
			return $this;
		}

		/**
		 * Возвращает заголовки http запроса по умолчанию
		 * @return array
		 */
		protected function getDefaultHeaders() {
			return [];
		}

		/**
		 * Возвращает опцию http клиента, которая отключает бросание исключения при получении
		 * ответа на запрос со статусом, отличным от 200 ок
		 * @return array
		 */
		protected function getMuteExceptionOption() {
			return [
				'exceptions' => false
			];
		}

		/**
		 * Формирует url из частей
		 * @param array $pathParts части адреса url, @see Client::buildPath()
		 * @param array $queryParts части url query, @see Client::buildQuery()
		 * @return string
		 */
		protected function buildUrl(array $pathParts = [], array $queryParts = []) {
			return $this->getPrefix() . '/' . $this->buildPath($pathParts) . $this->buildQuery($queryParts);
		}

		/**
		 * Возвращает префикс адреса запросов
		 * @return string
		 */
		protected function getPrefix() {
			return '';
		}

		/**
		 * Формирует путь (адрес) из частей:
		 *
		 * ['foo', 'bar', 'baz] => 'foo/bar/baz'
		 *
		 * @param array $pathParts
		 * @return string
		 */
		protected function buildPath(array $pathParts = []) {
			return implode('/', $pathParts);
		}

		/**
		 * Формирует query из частей:
		 *
		 * ['foo' => 'bar'] => '?foo=bar'
		 *
		 * @param array $queryParts
		 * @return string
		 */
		protected function buildQuery(array $queryParts = []) {
			return empty($queryParts) ? '' : '?' . http_build_query($queryParts);
		}

		/**
		 * Возвращает ответ на запрос
		 * @param RequestInterface $request запрос
		 * @return string|array
		 */
		protected function getResponse(RequestInterface $request) {
			$response = $request->send();
			return $this->getResponseBody($response);
		}

		/**
		 * Возвращает содержимое тела ответа
		 * @param Response $response ответ
		 * @return string|array
		 */
		protected function getResponseBody(Response $response) {
			return $response->getBody(true);
		}

		/**
		 * Кодирует данные, которые требуется передать POST
		 * @param \stdClass|array $data данные
		 * @return \stdClass|array
		 */
		protected function encodePostData($data) {
			return $data;
		}

		/**
		 * Создает GET запрос
		 * @param array $pathParts части адреса url, @see Client::buildPath()
		 * @param array $queryParts части url query, @see Client::buildQuery()
		 * @return RequestInterface
		 */
		protected function createGetRequest(array $pathParts = [], array $queryParts = []) {
			return $this->getHttpClient()->get(
				$this->buildUrl($pathParts, $queryParts),
				$this->getDefaultHeaders(),
				$this->getMuteExceptionOption()
			);
		}

		/**
		 * Создает POST запрос
		 * @param \stdClass|array $postData содержимое запроса
		 * @param array $pathParts части адреса url, @see Client::buildPath()
		 * @param array $queryParts части url query, @see Client::buildQuery()
		 * @return RequestInterface
		 */
		protected function createPostRequest($postData = [], array $pathParts = [], array $queryParts = []) {
			return $this->setLastRequestData($postData)
				->getHttpClient()
				->post(
					$this->buildUrl($pathParts, $queryParts),
					$this->getDefaultHeaders(),
					$this->encodePostData($postData),
					$this->getMuteExceptionOption()
				);
		}

		/**
		 * Создает PUT запрос
		 * @param \stdClass|array $postData содержимое запроса
		 * @param array $pathParts части адреса url, @see Client::buildPath()
		 * @param array $queryParts части url query, @see Client::buildQuery()
		 * @return RequestInterface
		 */
		protected function createPutRequest($postData = [], array $pathParts = [], array $queryParts = []) {
			return $this->setLastRequestData($postData)
				->getHttpClient()
				->put(
					$this->buildUrl($pathParts, $queryParts),
					$this->getDefaultHeaders(),
					$this->encodePostData($postData),
					$this->getMuteExceptionOption()
				);
		}

		/**
		 * Создает DELETE запрос
		 * @param array $pathParts части адреса url, @see Client::buildPath()
		 * @param array $queryParts части url query, @see Client::buildQuery()
		 * @return RequestInterface
		 */
		protected function createDeleteRequest(array $pathParts = [], array $queryParts = []) {
			return $this->getHttpClient()->delete(
				$this->buildUrl($pathParts, $queryParts),
				$this->getDefaultHeaders(),
				null,
				$this->getMuteExceptionOption()
			);
		}

		/**
		 * Возвращает данные последнего POST или PUT запроса
		 * @return array
		 */
		protected function getLastRequestData() {
			return $this->lastRequestData;
		}

		/**
		 * Устанавливает данные последнего POST или PUT запроса
		 * @param \stdClass|array $requestData
		 * @return $this
		 */
		protected function setLastRequestData($requestData = []) {
			$this->lastRequestData = $requestData;
			return $this;
		}

		/**
		 * Формирует сообщение для записи в журнал запросов
		 * @param RequestInterface $request http-запрос
		 * @return string
		 */
		protected function prepareLogMessage(RequestInterface $request) {
			$response = $request->getResponse();

			try {
				$responseBody = $response ? $this->getResponseBody($response) : '';
			} catch (RuntimeException $exception) {
				$responseBody = $response->getBody(true);
			}

			$responseBody = 'Response Body: ' . print_r($responseBody, true);
			$time = strftime('%d/%b/%Y %H:%M:%S');
			$method = $request->getMethod();
			$requestData = '';

			if (($method === 'POST' || $method === 'PUT') && umiCount($this->getLastRequestData()) > 0) {
				$requestData = 'Request Data: ' . print_r($this->getLastRequestData(), true);
				$this->setLastRequestData();
			}

			$url = $request->getUrl();
			$statusCode = $response->getStatusCode();
			$requestHeaders = 'Request Headers: ' . print_r($request->getHeaderLines(), true);
			$separator = str_repeat('-', 80);

			return <<<MESSAGE
[$time] $method $url $statusCode

$requestHeaders
$requestData

$responseBody
$separator


MESSAGE;
		}
	}
