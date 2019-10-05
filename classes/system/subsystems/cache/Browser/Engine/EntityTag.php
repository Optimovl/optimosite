<?php

	namespace UmiCms\System\Cache\Browser\Engine;

	use UmiCms\System\Cache\Browser\Engine;

	/**
	 * Класс реализации браузерного кеширования с помощью заголовка "ETag"
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/ETag
	 * @package UmiCms\System\Cache\Browser\Engine
	 */
	class EntityTag extends Engine {

		/** @inheritdoc */
		public function process() {
			$buffer = $this->getResponse()
				->getCurrentBuffer();
			$entityTag = $this->getTag();

			$buffer->setHeader('ETag', $entityTag);
			$buffer->setHeader('Cache-Control', $this->getCacheControl());
			$buffer->setHeader('Pragma', $this->getPragma());

			if ($this->isFresh($entityTag)) {
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
		 * Возвращает значение для заголовка "Etag"
		 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/ETag
		 * @return string
		 */
		private function getTag() {
			return sprintf('W/"%s"', sha1($this->getResponse()->getUpdateTime()));
		}

		/**
		 * Валидирует присланный "Etag"
		 * @param string $entityTag текущее значение etag
		 * @return bool
		 */
		private function isFresh($entityTag) {
			$server = $this->getRequest()
				->Server();
			$validateHeader = 'HTTP_IF_NONE_MATCH';
			return $server->isExist($validateHeader) && $server->get($validateHeader) == $entityTag;
		}
	}
