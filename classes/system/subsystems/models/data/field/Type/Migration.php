<?php

	namespace UmiCms\System\Data\Field\Type;

	/**
	 * Класс миграции типов полей
	 * @package UmiCms\System\Data\Field\Type
	 */
	class Migration implements iMigration {

		/** @var \iUmiObjectTypesCollection $typeCollection коллекция объектных типов */
		private $typeCollection;

		/** @var \iUmiFieldsCollection $fieldCollection коллекция полей */
		private $fieldCollection;

		/** @var \iUmiFieldTypesCollection $fieldTypeCollection коллекция типов полей */
		private $fieldTypeCollection;

		/** @inheritdoc */
		public function __construct(
			\iUmiObjectTypesCollection $typeCollection,
			\iUmiFieldsCollection $fieldCollection,
			\iUmiFieldTypesCollection $fieldTypeCollection
		) {
			$this->typeCollection = $typeCollection;
			$this->fieldCollection = $fieldCollection;
			$this->fieldTypeCollection = $fieldTypeCollection;
		}

		/** @inheritdoc */
		public function migrate(array $target) {
			return $this->process($target, 'to');
		}

		/** @inheritdoc */
		public function rollback(array $target) {
			return $this->process($target, 'from');
		}

		/**
		 * Обрабатывает цель миграции
		 * @param array $target цель миграции
		 *
		 * [
		 *		 'type-guid' => 'Гуид типа, в котором содержится поле',
		 *		 'field' => 'Системное название поля',
		 *		 $index => 'Системное название целевого поля',
		 * ]
		 *
		 * @param string $index индекс системного названия целевого поля
		 * @return $this
		 * @throws \RuntimeException
		 */
		private function process(array $target, $index) {
			if (!isset($target['type-guid'], $target['field'], $target['to'], $target['from'])) {
				throw new \RuntimeException('Incorrect target given: ' . var_export($target, true));
			}

			$type = $this->getTypeCollection()
				->getTypeByGUID($target['type-guid']);

			if (!$type instanceof \iUmiObjectType) {
				throw new \RuntimeException('Incorrect type guid given: ' . var_export($target['type-guid'], true));
			}

			$fieldId = $type->getFieldId($target['field']);
			$field = $this->getFieldCollection()
				->getField($fieldId);

			if (!$field instanceof \iUmiField) {
				throw new \RuntimeException('Incorrect field name given: ' . var_export($target['field'], true));
			}

			$newFieldType = $this->getFieldTypeCollection()
				->getFieldTypeByDataType($target[$index]);

			if (!$newFieldType instanceof \iUmiFieldType) {
				throw new \RuntimeException('Incorrect field data type given: ' . var_export($target[$index], true));
			}

			$field->setFieldTypeId($newFieldType->getId());
			$field->removeRestriction();
			$field->commit();
			return $this;
		}

		/**
		 * Возвращает коллекцию объектных типов
		 * @return \iUmiObjectTypesCollection
		 */
		private function getTypeCollection() {
			return $this->typeCollection;
		}

		/**
		 * Возвращает коллекцию полей
		 * @return \iUmiFieldsCollection
		 */
		private function getFieldCollection() {
			return $this->fieldCollection;
		}

		/**
		 * Возвращает коллекцию типов полей
		 * @return \iUmiFieldTypesCollection
		 */
		private function getFieldTypeCollection() {
			return $this->fieldTypeCollection;
		}
	}