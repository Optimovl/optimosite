<?php

	interface iUmiFile {

		public function __construct($filePath);

		public function delete();

		public static function upload($groupName, $varName, $targetDirectory, $id = false);

		public function getSize();

		/**
		 * Возвращает расширение файла
		 * @param bool $caseSensitive учитывать регистр символов
		 * @return string
		 */
		public function getExt($caseSensitive = false);

		public function getFileName();

		/**
		 * Возвращает путь до директории, в которой лежит файл
		 * @param bool $webMode если true, то путь будет указан относительно DOCUMENT_ROOT'а
		 * @return string адрес директории
		 */
		public function getDirName($webMode = false);

		public function getModifyTime();

		/**
		 * Возвращает содержимое файла
		 * @return bool|string
		 */
		public function getContent();

		/**
		 * Устанавливает содержимое файла
		 * @param string $content
		 * @return bool|int
		 */
		public function putContent($content);

		/**
		 * Возвращает хеш от содержимого файла
		 * @return string
		 */
		public function getHash();

		public function getFilePath($webMode = false);

		public function getIsBroken();

		/**
		 * Устанавливает игнорируется ли безопасность файла
		 * @param bool $flag значение
		 * @return $this
		 */
		public function setIgnoreSecurity($flag = true);

		public function __toString();

		public static function getUnconflictPath($path);

		public function download($deleteAfterDownload = false);

		public function getOrder();

		public function setOrder($order);

		public function getId();

		public function setId($id);

		/**
		 * Считывает информацию о файле и обновляет свойства
		 * @return $this
		 */
		public function refresh();

		/**
		 * Копирует файл
		 * @param string $target путь до копии
		 * @return $this
		 */
		public function copy($target);

		/** Проверяет существует ли файл и возвращает результат проверки */
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
		 * Устанавливает путь до файла
		 * @param string $path путь
		 * @return $this
		 */
		public function setFilePath($path);
	}

