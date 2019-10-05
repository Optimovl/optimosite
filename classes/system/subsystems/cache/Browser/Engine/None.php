<?php

	namespace UmiCms\System\Cache\Browser\Engine;

	use UmiCms\System\Cache\Browser\Engine;

	/**
	 * Класс реализации отключенного браузерного кеширования
	 * @package UmiCms\System\Cache\Browser\Engine
	 */
	class None extends Engine {

		/** @inheritdoc */
		public function process() {
			$buffer = $this->getResponse()
				->getCurrentBuffer();
			$buffer->setHeader('Cache-Control', $this->getCacheControl());
			$buffer->setHeader('Pragma', $this->getPragma());
		}

		/** @inheritdoc */
		protected function getCacheControl() {
			return sprintf('%s, no-cache, must-revalidate, max-age=0', $this->getCacheControlPrivacy());
		}

		/** @inheritdoc */
		protected function getPragma() {
			return 'no-cache';
		}
	}
