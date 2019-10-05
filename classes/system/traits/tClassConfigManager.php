<?php

	/**
	 * Trait tClassConfig
	 * Трейт управления конфигурациями класса
	 */
	trait tClassConfigManager {

		/** @var ClassConfig конфигурация класса */
		private static $config;

		/** @var ClassConfig конфигурация класса по умолчанию */
		private static $defaultConfig;

		/** @var string имя директории с файлом конфигурации класса по умолчанию */
		private static $configDir = 'config';

		/** @var string имя свойства, в котором хранится конфигурация класса */
		private static $configProperty = 'classConfig';

		/** @var string имя класса, представляющего конфигурациию */
		private static $itemClass = 'ClassConfig';

		/** @inheritdoc */
		public static function setItemClass($class) {
			self::$itemClass = $class;
		}

		/** @inheritdoc */
		public static function setConfig($config) {
			if (is_string($config)) {
				return self::setConfigByFile($config);
			}

			if (is_array($config)) {
				return self::setConfigByContent($config);
			}

			return false;
		}

		/** @inheritdoc */
		public static function getConfig() {
			if (self::$config !== null) {
				return self::$config;
			}

			return self::getDefaultConfig();
		}

		/**
		 * Возвращает конфигурацию класса по умолчанию
		 * @return mixed
		 * @throws Exception
		 */
		protected static function getDefaultConfig() {
			if (self::$defaultConfig !== null) {
				return self::$defaultConfig;
			}

			$class = self::getClass();
			return self::$defaultConfig = self::createConfig($class, self::getClassConfigContent($class));
		}

		/**
		 * Возвращает конфигурацию из файла
		 * @param string $path путь до файла
		 * @return mixed
		 * @throws Exception
		 */
		protected static function getConfigFromFile($path) {
			if (self::isReadableFile($path)) {
				return include_once $path;
			}

			throw new Exception(sprintf('Невозможно загрузить конфигурацию из файла %s', $path));
		}

		/**
		 * Устанавливает конфигурацию из файла
		 * @param $filePath
		 * @return bool
		 * @throws Exception
		 */
		protected function setConfigByFile($filePath) {
			self::$config = $this->getConfigFromFile($filePath);
			return true;
		}

		/**
		 * Устанавливает конфигурацию
		 * @param array $config конфигурация
		 * @return bool
		 */
		protected function setConfigByContent(array $config) {
			self::$config = self::createConfig(self::getClass(), $config);
			return true;
		}

		/**
		 * Возвращает содержимое конфигурации класса
		 * @param string $class имя класса
		 * @return array
		 * @throws Exception
		 */
		protected static function getClassConfigContent($class) {
			$configProperty = self::$configProperty;

			if (!property_exists($class, $configProperty)) {
				throw new Exception(sprintf('Не определено свойство %s для класса %s', $configProperty, $class));
			}

			$content = $class::$$configProperty;

			if (!is_array($content)) {
				throw new Exception(sprintf('Конфигурация для класса %s не определена', $class));
			}

			return $content;
		}

		/**
		 * Возвращает может ли файл быть открыт для чтения
		 * @param string $filePath путь до файла
		 * @return bool
		 */
		private static function isReadableFile($filePath) {
			return is_file($filePath) && is_readable($filePath);
		}

		/**
		 * Возвращает путь до файла с классом
		 * @param string $class имя класса
		 * @return string
		 */
		private static function getClassPath($class) {
			$reflector = new ReflectionClass($class);
			return $reflector->getFileName();
		}

		/**
		 * Возвращает текущий класс
		 * @return string
		 */
		private static function getClass() {
			return get_called_class();
		}

		/**
		 * Создает объект конфигурации класса
		 * @param string $class имя класса
		 * @param array $config конфигурация класса
		 * @return mixed
		 */
		private static function createConfig($class, $config) {
			return new self::$itemClass($class, $config);
		}
	}
