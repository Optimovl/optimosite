<?php

	namespace UmiCms\System\Data\Object\Property\Value;

	use UmiCms\System\Data\Object\Property\Value\Table\iSchema;

	/**
	 * Интерфейс миграции значения полей с идентификаторами домена в хранилище для полей типа "Ссылка на домен"
	 * @package UmiCms\System\Data\Object\Property\Value
	 */
	interface iMigration {

		/**
		 * Конструктор
		 * @param iSchema $schema схема таблиц значений свойств объектов
		 * @param \IConnection $connection подключение к бд
		 */
		public function __construct(iSchema $schema, \IConnection $connection);

		/**
		 * Выполняет миграцию
		 * @param \iUmiObjectProperty $property мигрируемое значение свойства объекта
		 * @param string $previousDataType предыдущий тип поля
		 * @return $this
		 * @throws \RuntimeException
		 * @throws \databaseException
		 */
		public function migrate(\iUmiObjectProperty $property, $previousDataType);

		/**
		 * Откатывает миграцию
		 * @param \iUmiObjectProperty $property мигрируемое значение свойства объекта
		 * @param string $previousDataType предыдущий тип поля
		 * @return $this
		 * @throws \RuntimeException
		 * @throws \databaseException
		 */
		public function rollback(\iUmiObjectProperty $property, $previousDataType);
	}