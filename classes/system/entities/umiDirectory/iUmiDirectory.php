<?php

	interface iUmiDirectory {

		/**
		 * Конструктор
		 * @param string $directoryPath путь до директории
		 */
		public function __construct($directoryPath);

		/**
		 * Считывает информацию о директории и обновляет свойства
		 * @return $this
		 */
		public function refresh();

		/**
		 * Возвращает путь до директории
		 * @return string
		 */
		public function getPath();

		public function getName();

		public function getIsBroken();

		/**
		 * Проверяет существует ли директория и возвращает результат проверки
		 * @return bool
		 */
		public function isExists();

		/**
		 * Проверяет доступна ли директория на чтение и возвращает результат проверки
		 * @return bool
		 */
		public function isReadable();

		/**
		 * Проверяет доступна ли директория на запись и возвращает результат проверки
		 * @return bool
		 */
		public function isWritable();

		/**
		 * Возвращает содержимое директории, соответствующее шаблону
		 * @link http://php.net/manual/ru/function.glob.php
		 * @param string $pattern шаблон (аналогичен php функции glob())
		 * @return string[]
		 */
		public function getList($pattern);

		public function getFSObjects($objectType = 0, $mask = '', $onlyReadable = false);

		public function getFiles($mask = '', $onlyReadable = false);

		public function getDirectories($mask = '', $onlyReadable = false);

		public function getAllFiles($i_obj_type = 0, $s_mask = '', $b_only_readable = false);

		/**
		 * Удаляет директорию рекурсивно вместе со всем содержанием
		 * @return bool
		 */
		public function deleteRecursively();

		/**
		 * Удаляет содержимое директории
		 * @return bool
		 */
		public function deleteContent();

		/**
		 * Удаляет директорию
		 * @param bool $recursively удалить рекурсивно вместе со всем содержанием
		 * @return bool
		 */
		public function delete($recursively = false);

		/**
		 * Удаляет пустую директорию и возвращает результат операции
		 * @return bool
		 */
		public function deleteEmptyDirectory();

		/**
		 * Возвращает путь до родительской директории
		 * @return string
		 */
		public function getParentPath();

		public static function requireFolder($folder, $basedir = '');

		/**
		 * Возвращает вес директории в байтах
		 * @param string $directoryPath путь до директории
		 * @return int
		 */
		public static function getDirectorySize($directoryPath);
	}
