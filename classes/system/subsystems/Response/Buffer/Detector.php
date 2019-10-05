<?php

	namespace UmiCms\System\Response\Buffer;

	use UmiCms\System\Request\Mode\iDetector as ModeDetector;

	/**
	 * Класс определителя текущего буфера
	 * @package UmiCms\System\Response\Buffer
	 */
	class Detector implements iDetector {

		/** @var ModeDetector $modeDetector определитель режима работы системы */
		private $modeDetector;

		/** @inheritdoc */
		public function __construct(ModeDetector $modeDetector) {
			$this->modeDetector = $modeDetector;
		}

		/** @inheritdoc */
		public function detect() {
			if ($this->getModeDetector()->isCli()) {
				return self::DEFAULT_CLI_BUFFER;
			}

			if (isCronCliMode()) {
				return self::DEFAULT_CLI_BUFFER;
			}

			return self::DEFAULT_HTTP_BUFFER;
		}

		/**
		 * Возвращает определитель режима работы системы
		 * @return ModeDetector
		 */
		private function getModeDetector() {
			return $this->modeDetector;
		}
	}
