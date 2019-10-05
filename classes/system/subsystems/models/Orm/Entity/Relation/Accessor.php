<?php
	namespace UmiCms\System\Orm\Entity\Relation;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\iAccessor;
	use UmiCms\System\Orm\Entity\Accessor as AbstractAccessor;

	/**
	 * Абстрактный класс аксессора связей сущности
	 * @package UmiCms\System\Orm\Entity\Relation
	 */
	abstract class Accessor extends AbstractAccessor implements iAccessor {

		/** @inheritdoc */
		protected function getSchema($name) {
			return $this->getMapper()->getRelationSchema($name);
		}

		/** @inheritdoc */
		protected function getSchemaList() {
			return $this->getMapper()->getRelationSchemaList();
		}

		/** @inheritdoc */
		protected function accessBySchema(iEntity $entity, array $schema) {
			list(,,,$accessor) = $schema;
			return $entity->$accessor();
		}
	}