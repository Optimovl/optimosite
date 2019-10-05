<?php

	namespace UmiCms\System\Data\Field\Type;

	/**
	 * Интерфейс миграции типов полей
	 * @package UmiCms\System\Data\Field\Type
	 */
	interface iMigration {

		/**
		 * Конструктор
		 * @param \iUmiObjectTypesCollection $typeCollection коллекция объектных типов
		 * @param \iUmiFieldsCollection $fieldCollection коллекция полей
		 * @param \iUmiFieldTypesCollection $fieldTypeCollection коллекция типов полей
		 */
		public function __construct(
			\iUmiObjectTypesCollection $typeCollection,
			\iUmiFieldsCollection $fieldCollection,
			\iUmiFieldTypesCollection $fieldTypeCollection
		);

		/**
		 * Выполняет миграцию
		 * @param array $target цель миграции
		 *
		 * [
		 *		 'type-guid' => 'Гуид типа, в котором содержится поле',
		 *		 'field' => 'Системное название поля',
		 *		 'to' => 'Системное название целевого поля',
		 * ]
		 *
		 * @return $this
		 * @throws \RuntimeException
		 */
		public function migrate(array $target);

		/**
		 * Откатывает миграцию
		 * @param array $target цель миграции
		 *
		 * [
		 *		 'type-guid' => 'Гуид типа, в котором содержится поле',
		 *		 'field' => 'Системное название поля',
		 *		 'from' => 'Системное название исходного поля'
		 * ]
		 *
		 * @return $this
		 * @throws \RuntimeException
		 */
		public function rollback(array $target);
	}