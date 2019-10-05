<?php

	/** Интерфейс коллекции языков */
	interface iLangsCollection {

		/**
		 * Конструктор
		 * @param IConnection $connection соединение с базой данных
		 * @param iDomainsCollection $domainCollection коллекция доменов
		 */
		public function __construct(IConnection $connection, iDomainsCollection $domainCollection);

		/**
		 * Добавляет язык
		 * @param string $prefix префикс языковой версии
		 * @param string $title название языка
		 * @param bool $isDefault будет ли язык языком по умолчанию
		 * @return int идентификатор добавленного языка
		 * @throws coreException если язык с заданным префиксом уже существует
		 * @throws wrongParamException если значения параметров невалидны
		 */
		public function addLang($prefix, $title, $isDefault = false);

		/**
		 * Удаляет язык и связанные с ним сущности
		 * @param int $id идентификатор языка, который требуется удалить
		 * @return bool
		 * @throws coreException если заданный язык не существует
		 */
		public function delLang($id);

		/**
		 * Возвращает экземпляр языка по его идентификатору
		 * @param int $id идентификатор языка
		 * @return iLang|false
		 */
		public function getLang($id);

		/**
		 * Возвращает язык по умолчанию
		 * @return iLang|false
		 */
		public function getDefaultLang();

		/**
		 * Устанавливает язык по умолчанию
		 * @param int $id id языка, который нужно сделать языком по умолчанию
		 * @return bool
		 * @throws coreException если языка с заданным id не существует
		 */
		public function setDefault($id);

		/**
		 * Возвращает идентификатор языка по его префиксу
		 * @param string $prefix префикс языка
		 * @return int|bool
		 */
		public function getLangId($prefix);

		/**
		 * Возвращает список загруженных языков
		 * @return iLang[]
		 */
		public function getList();

		/**
		 * Проверяет существует ли язык с заданными идентификатором
		 * @param int $id идентификатор языка
		 * @return bool
		 */
		public function isExists($id);

		/**
		 * Определяет идентификатор языка по URL
		 * @param string $url
		 * @return bool|int результат определения
		 */
		public function getLanguageIdByUrl($url);

		/**
		 * Очищает внутренний кеш класса
		 */
		public function clearCache();
	}
