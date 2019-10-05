<?php

	/** Interface iUmiImportSplitter */
	interface iUmiImportSplitter {

		/**
		 * Конструктор.
		 * @param string $type тип сценария импорта (префикс названия конкретного класса)
		 */
		public function __construct($type);

		/**
		 * @param $prefix
		 * @return mixed
		 */
		public static function get($prefix);

		/**
		 * @param $filePath
		 * @param int $blockSize
		 * @param int $offset
		 */
		public function load($filePath, $blockSize = 100, $offset = 0);

		/**
		 * @param DOMDocument $document
		 * @return mixed
		 */
		public function translate(DOMDocument $document);

		/** @return mixed */
		public function getXML();

		/** @return mixed */
		public function getDocument();

		/** @return mixed */
		public function getOffset();

		/**
		 * Возвращает значение режима, при котором файлы,
		 * указанные в импортируемых полях типа "файл"
		 * будут переименовываться в более удобное название.
		 * @return bool
		 */
		public function getRenameFiles();

		/**
		 * Определяет включен ли режим, при котором новые создаваемые типы данных
		 * не будут наследовать группы и поля родительского типа данных.
		 * @return bool
		 */
		public function getIgnoreParentGroups();

		/**
		 * Определяет включен ли режим, при котором будут автоматически создаваться
		 * новые типы данных (справочники).
		 * @return bool
		 */
		public function getAutoGuideCreation();
	}
