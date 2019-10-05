<?php

	namespace UmiCms\System\Request\Mode;

	use UmiCms\System\Request\Path\iResolver;

	/**
	 * Класс определителя режима работы системы
	 * @package UmiCms\System\Request\Mode
	 */
	class Detector implements iDetector {

		/** @var iResolver $pathResolver распознаватель обрабатываемого пути */
		private $pathResolver;

		/** @inheritdoc */
		public function __construct(iResolver $pathResolver) {
			$this->pathResolver = $pathResolver;
		}

		/** @inheritdoc */
		public function detect() {
			if (php_sapi_name() == self::CLI_MODE) {
				return self::CLI_MODE;
			}

			$pathParts = $this->getPathResolver()
				->getParts();
			$twoFirstParts = array_slice($pathParts, 0, 2);
			return in_array(self::ADMIN_MODE, $twoFirstParts) ? self::ADMIN_MODE : self::SITE_MODE;
		}

		/** @inheritdoc */
		public function isAdmin() {
			return $this->detect() === self::ADMIN_MODE;
		}

		/** @inheritdoc */
		public function isSite() {
			return $this->detect() === self::SITE_MODE;
		}

		/** @inheritdoc */
		public function isCli() {
			return $this->detect() === self::CLI_MODE;
		}

		/**
		 * Возвращает распознавателя обрабатываемого пути
		 * @return iResolver
		 */
		private function getPathResolver() {
			return $this->pathResolver;
		}
	}
