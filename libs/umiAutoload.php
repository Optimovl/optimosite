<?php

	/** Класс для автозагрузки системных классов и вспомогательных классов для модулей */
	class umiAutoload {

		/** @const string CORE_SECTION_KEY ключ секции классов ядра */
		const CORE_SECTION_KEY = 'core';

		/** @var array массив с файлами для автозагрузки для каждого класса */
		private static $includes = [
			self::CORE_SECTION_KEY => []
		];

		/**
		 * Добавить классы и их файлы для автозагрузки
		 * @param array $classes массив вида
		 * [
		 *   'className' => [
		 *     'filePath',
		 *     ...
		 *   ],
		 *   ...
		 * ]
		 * @param string $section секция классов для автозагрузки
		 */
		public static function addClassesToAutoload($classes, $section = self::CORE_SECTION_KEY) {
			if (!isset(self::$includes[$section])) {
				self::$includes[$section] = [];
			}

			foreach ($classes as $class => $files) {
				if (is_string($files)) {
					$files = [$files];
				}

				self::$includes[$section][$class] = $files;
			}
		}

		/**
		 * Возвращает соответствия классов к их файлам
		 * @return array
		 */
		public static function getClassesMap() {
			return self::$includes;
		}

		/**
		 * Функция автозагрузки, в прикладном коде не используется
		 * @link http://php.net/manual/en/function.spl-autoload-register.php
		 *
		 * @param string $className имя класса, файлы которого нужно загрузить
		 */
		public static function autoload($className) {
			foreach (self::$includes as $section => $classes) {
				if (!is_array($classes) || !isset($classes[$className])) {
					continue;
				}

				$files = $classes[$className];

				if (is_array($files)) {
					foreach ($files as $filePath) {
						require_once $filePath;
					}

					break;
				}
			}
		}
	}
