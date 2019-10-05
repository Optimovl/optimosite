<?php

	namespace UmiCms\System\Cache\Browser\Engine;

	use UmiCms\System\Cache\Browser\Engine;

	/**
	 * Класс реализации браузерного кеширования с помощью заголовка "Last-Modified"
	 * @link https://developer.mozilla.org/ru/docs/Web/HTTP/Headers/Last-Modified
	 * @package UmiCms\System\Cache\Browser\Engine
	 */
	class LastModified extends Engine {

		/** @inheritdoc */
		public function process() {
			$response = $this->getResponse();
			$updateTime = $response->getUpdateTime();

			$buffer = $response->getCurrentBuffer();
			$buffer->setHeader('Last-Modified', $this->formatHtmlDate($updateTime));
			$buffer->setHeader('Cache-Control', $this->getCacheControl());
			$buffer->setHeader('Pragma', $this->getPragma());

			if ($this->isFresh($updateTime)) {
				$this->sendNotModified();
			}
		}

		/** @inheritdoc */
		protected function getCacheControl() {
			return sprintf('%s, no-cache, must-revalidate', $this->getCacheControlPrivacy());
		}

		/** @inheritdoc */
		protected function getPragma() {
			return 'no-cache';
		}

		/**
		 * Валидирует присланную дату изменения страницы
		 * @param int $updateTime текущая дата изменения страницы
		 * @return bool
		 */
		private function isFresh($updateTime) {
			$server = $this->getRequest()
				->Server();
			$validateHeader = 'HTTP_IF_MODIFIED_SINCE';
			return $server->isExist($validateHeader) && strtotime($server->get($validateHeader)) >= $updateTime;
		}
	}
