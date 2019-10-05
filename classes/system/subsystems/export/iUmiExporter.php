<?php

	/** Сценарий экспорта */
	interface iUmiExporter {

		/**
		 * Конструктор.
		 * @param string $type тип сценария экспорта (префикс названия конкретного класса)
		 */
		public function __construct($type);

		/**
		 * Инициализирует буфер вывода и возвращает его
		 * @return iOutputBuffer
		 */
		public function setOutputBuffer();

		/**
		 * Экспортирует данные.
		 * По умолчанию, работает со страницами, в качестве аргументов передаются корневые страницы,
		 * а экспорт производится на всю вложенность.
		 * @param iUmiEntinty[]|int[] $exportList список сущностей или их идентификаторов, которые требуется экспортировать
		 * @param iUmiEntinty[]|int[] $ignoreList список сущностей или их идентификаторов, которые требуется проигнорировать
		 * @return mixed
		 */
		public function export($exportList, $ignoreList);

		/**
		 * Возвращает тип сценария экспорта
		 * @return string
		 */
		public function getType();

		/**
		 * Возвращает расширение файла, в который производится экспорт данных
		 * @return mixed
		 */
		public function getFileExt();

		/**
		 * Возвращает название источника экспорта
		 * @return bool|string
		 */
		public function getSourceName();

		/**
		 * Устанавливает название источника экспорта
		 * @param bool|string $sourceName
		 */
		public function setSourceName($sourceName = false);

		/**
		 * Возвращает статус завершенности экспорта
		 * @return bool
		 */
		public function getIsCompleted();

		/**
		 * Устанавливает опции сериализации
		 * @param array $option опции сериализации
		 * @return $this
		 */
		public function setSerializeOptions(array $option);
	}
