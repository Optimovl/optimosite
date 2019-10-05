<?php

	namespace UmiCms\System\Data\Object\Property;

	use UmiCms\System\Data\Object\Property\Value\Table\iSchema;

	/**
	 * Интерфейс репозитория значений полей объектов
	 * @package UmiCms\System\Data\Object\Property
	 */
	interface iRepository {

		/**
		 * Конструктор
		 * @param \IConnection $connection подключение к бд
		 * @param iFactory $factory фабрика значений полей объектов
		 * @param \iUmiFieldsCollection $fieldCollection коллекция полей
		 * @param iSchema $schema схема таблиц значений свойств объектов
		 */
		public function __construct(
			\IConnection $connection,
			iFactory $factory,
			\iUmiFieldsCollection $fieldCollection,
			iSchema $schema
		);

		/**
		 * Возвращает список значений поля
		 * @param int $fieldId идентификатор поля
		 * @param int $limit ограничение длину списка
		 * @param int $offset смещение выборки
		 * @param string|null $dataType тип данных поля
		 * @return \iUmiObjectProperty[]
		 */
		public function getListByFieldId($fieldId, $limit = 100, $offset = 0, $dataType = null);
	}