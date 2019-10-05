<?php

	/** Класс помошника для заместителя объекта */
	abstract class objectProxyHelper {

		/**
		 * Возвращает префикс класса объекта по id его объекта-типа
		 * @param int $objectId id объекта типа скидки
		 * @return string префикс класса скидки
		 * @throws coreException
		 */
		public static function getClassPrefixByType($objectId) {
			static $cache = [];

			if (isset($cache[$objectId])) {
				return $cache[$objectId];
			}

			$object = selector::get('object')->id($objectId);

			if (!$object instanceof iUmiObject) {
				throw new coreException("Can't get class name prefix from object #{$objectId}");
			}

			return $cache[$objectId] = $object->class_name ?: '';
		}

		/**
		 * Подключает файл, содержащий класс
		 * @param string $directoryPath путь до директории с файлом класса (от пути директории с модулями)
		 * @param string $classPrefix имя класса (равно имени файла)
		 * @throws coreException
		 */
		public static function includeClass($directoryPath, $classPrefix) {

			$filePath = SYS_MODULES_PATH . $directoryPath . $classPrefix . '.php';

			if (!is_file($filePath)) {
				throw new coreException("Required source file {$filePath} is not found");
			}

			/** @noinspection PhpIncludeInspection */
			require_once $filePath;
		}
	}
