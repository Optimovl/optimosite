<?php

	/** Фабрика классов обработки изображений */
	class imageUtils {

		/** @var gdProcessor|imageMagickProcessor|null экземпляр текущего класса обработки изображений */
		private static $processor;

		/**
		 * @var array Поддерживаемые расширения обработки изображений
		 * Порядок важен, @see imageUtils::determineProcessor()
		 */
		private static $supportedProcessors = ['imagick', 'gd'];

		/**
		 * Возвращает текущий экземпляр класса обработки изображений
		 * @return iImageProcessor
		 * @throws Exception
		 */
		public static function getImageProcessor() {
			if (!self::$processor) {
				self::$processor = self::determineProcessor();
			}

			return self::$processor;
		}

		/**
		 * Возвращает экземпляр класса imageMagickProcessor
		 * @return imageMagickProcessor
		 * @throws Exception
		 */
		public static function getImageMagickProcessor() {
			return self::createProcessor('imagick');
		}

		/**
		 * Возвращает экземпляр класса gdProcessor
		 * @return gdProcessor
		 * @throws Exception
		 */
		public static function getGDProcessor() {
			return self::createProcessor('gd');
		}

		/**
		 * Определяет текущий обработчик изображений
		 * @return iImageProcessor
		 */
		private static function determineProcessor() {
			$umiConfig = mainConfiguration::getInstance();
			$customProcessor = $umiConfig->get('kernel', 'image-processor');
			if ($customProcessor) {
				return self::createProcessor($customProcessor);
			}

			foreach (self::$supportedProcessors as $processor) {
				if (extension_loaded($processor)) {
					return self::createProcessor($processor);
				}
			}

			throw new Exception("No available extensions for image processing");
		}

		/**
		 * Создает обработчик изображений
		 * @param string $extensionName название расширения обработчика
		 * @return gdProcessor|imageMagickProcessor
		 * @throws Exception
		 */
		private static function createProcessor($extensionName) {
			if (!extension_loaded($extensionName)) {
				throw new Exception("Extension $extensionName is not loaded");
			}

			switch ($extensionName) {
				case 'imagick':
					return new imageMagickProcessor();
				case 'gd':
					return new gdProcessor();
				default:
					throw new Exception("Unsupported extension: $extensionName");
			}
		}
	}
