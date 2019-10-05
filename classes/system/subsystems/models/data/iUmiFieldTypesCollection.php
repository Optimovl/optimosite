<?php

	/** Интерфейс для управления/получения доступа к типам полей */
	interface iUmiFieldTypesCollection {

		/**
		 * Создает новый тип поля
		 * @param string $name описание типа
		 * @param string $dataType тип данных
		 * @param bool $isMultiple является ли тип составным (массив значений)
		 * @param bool $isUnsigned зарезервировано и пока не используется
		 * @return int идентификатор созданного типа, либо false в случае неудачи
		 * @throws coreException
		 */
		public function addFieldType($name, $dataType = 'string', $isMultiple = false, $isUnsigned = false);

		/**
		 * Удаляет тип поля с заданным идентификатором из коллекции
		 * @param int $id идентификатор поля
		 * @return bool true, если удаление удалось
		 * @throws coreException
		 */
		public function delFieldType($id);

		/**
		 * Возвращает тип поля по его идентификатору, либо false в случае неудачи
		 * @param int $id идентификатор типа поля
		 * @return iUmiFieldType|bool
		 */
		public function getFieldType($id);

		/**
		 * Возвращает экземпляр класса umiFieldType по типу данных, либо false в случае неудачи
		 * @param string $dataType тип данных
		 * @param bool $isMultiple может ли значение поля данного типа состоять из массива значений
		 * @return iUmiFieldType|bool
		 */
		public function getFieldTypeByDataType($dataType, $isMultiple = false);

		/**
		 * Определяет, существует ли в БД тип поля с заданным идентификатором
		 * @param int $id идентификатор типа
		 * @return bool true, если тип поля существует в БД
		 */
		public function isExists($id);

		/**
		 * Возвращает список всех типов полей
		 * @return iUmiFieldType[]
		 */
		public function getFieldTypesList();

		/** Очищает кэш класса и заново загружает типы полей */
		public function clearCache();
	}
