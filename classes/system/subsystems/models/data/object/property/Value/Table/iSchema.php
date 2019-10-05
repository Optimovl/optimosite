<?php

	namespace UmiCms\System\Data\Object\Property\Value\Table;

	/**
	 * Интерфейс схемы таблиц значений свойств объектов
	 * @package UmiCms\System\Data\Object\Property\Value\Table
	 */
	interface iSchema {

		/** @const string IMAGES_TABLE_NAME хранилище изображений */
		const IMAGES_TABLE_NAME = 'cms3_object_images';

		/** @const string COUNTER_TABLE_NAME хранилище счетчиков */
		const COUNTER_TABLE_NAME = 'cms3_object_content_cnt';

		/** @const string DOMAIN_ID_TABLE_NAME хранилище ссылок на домены */
		const DOMAIN_ID_TABLE_NAME = 'cms3_object_domain_id_list';

		/** @const string DEFAULT_TABLE_NAME хранилище по умолчанию */
		const DEFAULT_TABLE_NAME = 'cms3_object_content';

		/** @const string OFFER_ID_TABLE_NAME хранилище ссылок на торговые предложения */
		const OFFER_ID_TABLE_NAME = 'cms3_object_offer_id_list';

		/**
		 * Возвращает название таблицы, где хранится значение свойства объекта
		 * @param \iUmiObjectProperty $property значение свойства объекта
		 * @return string
		 */
		public function getTable(\iUmiObjectProperty $property);

		/**
		 * Возвращает название таблицы, где хранится значение свойства объекта
		 * @param string $dataType тип данных значения
		 * @return string
		 */
		public function getTableByDataType($dataType);

		/**
		 * Возвращает список таблиц
		 * @return string[]
		 */
		public function getTableList();

		/**
		 * Возвращает таблицу для хранения изображений
		 * @return string
		 */
		public function getImagesTable();

		/**
		 * Возвращает таблицу для хранения счетчиков
		 * @return string
		 */
		public function getCounterTable();

		/**
		 * Возвращает таблицу для хранения ссылок на домены
		 * @return string
		 */
		public function getDomainIdTable();

		/**
		 * Возвращает таблицу для хранения ссылок на торговые предложения
		 * @return string
		 */
		public function getOfferIdTable();

		/**
		 * Возвращает таблицу для значений полей по умолчанию
		 * @return string
		 */
		public function getDefaultTable();
	}