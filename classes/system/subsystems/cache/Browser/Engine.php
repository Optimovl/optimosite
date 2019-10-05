<?php

	namespace UmiCms\System\Cache\Browser;

	use UmiCms\System\Request\iFacade as iRequest;
	use UmiCms\System\Response\iFacade as iResponse;

	/**
	 * Абстрактный класс реализации браузерного кеширования
	 * @package UmiCms\System\Cache\Browser
	 */
	abstract class Engine implements iEngine {

		/** @var iRequest $request запрос */
		private $request;

		/** @var iResponse $response ответ */
		private $response;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @inheritdoc */
		public function __construct(iRequest $request, iResponse $response, \iConfiguration $configuration) {
			$this->request = $request;
			$this->response = $response;
			$this->configuration = $configuration;
		}

		/** @inheritdoc */
		abstract public function process();

		/**
		 * Возвращает значение заголовка "Cache-Control"
		 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
		 * @return string
		 */
		abstract protected function getCacheControl();

		/**
		 * Возвращает значение заголовка "Pragma"
		 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Pragma
		 * @return string
		 */
		abstract protected function getPragma();

		/** Завершает запрос, результат которого признан неизменнным со времени кешировая */
		protected function sendNotModified() {
			$buffer = $this->getResponse()
				->getCurrentBuffer();
			$buffer->status('304 Not Modified');
			$buffer->setHeader('Connection', 'close');
			$buffer->clear();
		}

		/**
		 * Возвращает дату в "html" формате
		 * @param int $timestamp дата в формате timestamp
		 * @return string
		 */
		protected function formatHtmlDate($timestamp) {
			return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
		}

		/**
		 * Возвращает значение параметра кешируемости для заголовка "Cache-Control"
		 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
		 * @return string
		 */
		protected function getCacheControlPrivacy() {
			$cacheControlOption = $this->getConfiguration()
				->get('cache', 'browser.cache-control');
			$cacheControlAllowed = ['private', 'public'];
			$cacheControl = 'private';

			if ($cacheControlOption !== null && in_array($cacheControlOption, $cacheControlAllowed)) {
				$cacheControl = $cacheControlOption;
			}

			return $cacheControl;
		}

		/**
		 * Возвращает запрос
		 * @return iRequest
		 */
		protected function getRequest() {
			return $this->request;
		}

		/**
		 * Возвращает ответ
		 * @return iResponse
		 */
		protected function getResponse() {
			return $this->response;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		protected function getConfiguration() {
			return $this->configuration;
		}
	}
