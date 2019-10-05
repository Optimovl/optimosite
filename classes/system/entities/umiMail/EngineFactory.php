<?php

	namespace UmiCms\Mail;

	/**
	 * Класс фабрики средств отправки писем
	 * @package UmiCms\Mail
	 */
	class EngineFactory implements iEngineFactory {

		/** @var iEngine[] $engineList список созданных средств отправки */
		protected static $engineList = [];

		/** @inheritdoc */
		public static function get($codeName) {
			if (isset(self::$engineList[$codeName])) {
				return self::$engineList[$codeName];
			}

			return self::$engineList[$codeName] = self::create($codeName);
		}

		/** @inheritdoc */
		public static function create($codeName) {
			$className = self::getClassByCode($codeName);

			if (class_exists($className)) {
				return new $className();
			}

			$classPath = self::getClassPathByCode($codeName);

			if (!file_exists($classPath)) {
				throw new \Exception('Не удалось подключить реализации средства отправки с кодом: ' . $codeName);
			}

			/** @noinspection PhpIncludeInspection */
			include_once $classPath;

			if (!class_exists($className)) {
				throw new \Exception('Не удалось найти класс средства отправки с кодом: ' . $codeName);
			}

			$engine = new $className();

			if (!$engine instanceof iEngine) {
				throw new \Exception('Неверно написан класс средства отправки с кодом: ' . $codeName);
			}

			return $engine;
		}

		/** @inheritdoc */
		public static function createDefault() {
			return new Engine\phpMail();
		}

		/** @inheritdoc */
		public static function createDummy() {
			return new Engine\nullEngine();
		}

		/**
		 * Возвращает класс средства доставки писем по его коду
		 * @param string $codeName код средства доставки
		 * @return string
		 */
		protected static function getClassByCode($codeName) {
			return 'UmiCms\Mail\Engine\\' . $codeName;
		}

		/**
		 * Возвращает путь до файла класса средства доставки писем по его коду
		 * @param string $codeName код средства доставки
		 * @return string
		 */
		protected static function getClassPathByCode($codeName) {
			return dirname(__FILE__) . "/Engine/$codeName.php";
		}
	}
