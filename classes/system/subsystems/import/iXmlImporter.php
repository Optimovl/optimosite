<?php

	interface iXmlImporter {

		public function __construct($sourceName = false);

		public function loadXmlString($xmlString);

		public function loadXmlFile($path);

		public function loadXmlDocument(DOMDocument $doc);

		public function setDestinationElement($element);

		/**
		 * Устанавливает режим, при котором будут автоматически создаваться новые типы данных (справочники)
		 * @param bool $autoGuideCreation
		 * @return $this
		 */
		public function setAutoGuideCreation($autoGuideCreation = false);

		public function execute();

		/**
		 * Запускает удаление
		 * @throws publicException
		 */
		public function demolish();

		/**
		 * Включает отправку событий
		 * @return $this
		 */
		public function enableEvents();

		/**
		 * Выключает отправку событий
		 * @return $this
		 */
		public function disableEvents();

		/**
		 * Устанавливает идентификатор домена, в который будут принудительно импортированы
		 * все сущности, связанные с доменами:
		 *
		 * 1) Шаблоны дизайна;
		 * 2) Объектные типы;
		 * 3) Значения полей типа "Ссылка на домен";
		 * 4) Значения полей типа "Ссылка на список доменов";
		 * 5) Иерархические элементы (страницы);
		 * 6) Почтовые уведомления;
		 *
		 * @param int $id идентификатор домена
		 * @return $this
		 */
		public function setForcedDomainId($id);

		/**
		 * Возвращает журнал импорта
		 * @return array
		 */
		public function getImportLog();

		/**
		 * Возвращает количество ошибок импорта
		 * @return int
		 */
		public function getErrorCount();

		/**
		 * Возвращает количество созданных объектов
		 * @return int
		 */
		public function getCreatedEntityCount();

		/**
		 * Возвращает количество обновленных объектов
		 * @return int
		 */
		public function getUpdatedEntityCount();

		/**
		 * Возвращает количество удаленных объектов
		 * @return int
		 */
		public function getDeletedEntityCount();
	}
