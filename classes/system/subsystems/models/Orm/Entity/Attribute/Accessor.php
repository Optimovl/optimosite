<?php
	namespace UmiCms\System\Orm\Entity\Attribute;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\iAccessor;
	use UmiCms\System\Orm\Entity\Accessor as AbstractAccessor;

	/**
	 * Абстрактный класс аксессора атрибутов сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Accessor extends AbstractAccessor implements iAccessor {

		/** @inheritdoc */
		public function accessOne(iEntity $entity, $name) {
			$mapper = $this->getMapper();

			if ($name === $mapper::ID) { // оптимизация
				return $entity->getId();
			}

			return parent::accessOne($entity, $name);
		}

		/** @inheritdoc */
		protected function getSchema($name) {
			return $this->getMapper()->getAttributeSchema($name);
		}

		/** @inheritdoc */
		protected function getSchemaList() {
			return $this->getMapper()->getAttributeSchemaList();
		}

		/** @inheritdoc */
		protected function accessBySchema(iEntity $entity, array $schema) {
			list($accessor) = $schema;
			return $entity->$accessor();
		}
	}