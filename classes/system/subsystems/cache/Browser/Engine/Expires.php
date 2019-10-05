<?php

	namespace UmiCms\System\Cache\Browser\Engine;

	use UmiCms\System\Cache\Browser\Engine;

	/**
	 * Класс реализации браузерного кеширования с помощью заголовка "Expires"
	 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/headers/Expires
	 * @package UmiCms\System\Cache\Browser\Engine
	 */
	class Expires extends Engine {

		/** @const int DEFAULT_TIME_TO_LIVE время жизни по умолчанию */
		const DEFAULT_TIME_TO_LIVE = 86400;

		/** @inheritdoc */
		public function process() {
			$buffer = $this->getResponse()
				->getCurrentBuffer();
			$buffer->setHeader('Expires', $this->formatHtmlDate($this->getExpireTime()));
			$buffer->setHeader('Cache-Control', $this->getCacheControl());
			$buffer->setHeader('Pragma', $this->getPragma());
		}

		/** @inheritdoc */
		protected function getCacheControl() {
			return sprintf('%s, max-age=%s', $this->getCacheControlPrivacy(), (string) $this->getTimeToLive());
		}

		/** @inheritdoc */
		protected function getPragma() {
			return 'cache';
		}

		/**
		 * Возвращает дату инвалидации кеша
		 * @return int
		 */
		private function getExpireTime() {
			return time() + $this->getTimeToLive();
		}

		/**
		 * Возвращает время жизни кеша
		 * @return int
		 */
		private function getTimeToLive() {
			$timeToLife = (int) $this->getConfiguration()
				->get('cache', 'browser.expires.time-to-live');
			return ($timeToLife > 0) ? $timeToLife : self::DEFAULT_TIME_TO_LIVE;
		}
	}
