<?php
	namespace UmiCms\System\Orm\Entity\Relation;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\iMutator;
	use UmiCms\System\Orm\Entity\Mutator as AbstractMutator;

	/**
	 * Абстрактный класс мутатора связей сущности
	 * @package UmiCms\System\Orm\Entity\Relation
	 */
	abstract class Mutator extends AbstractMutator implements iMutator {

		/** @inheritdoc */
		protected function getSchema($name) {
			return $this->getMapper()->getRelationSchema($name);
		}

		/** @inheritdoc */
		protected function getSchemaList() {
			return $this->getMapper()->getRelationSchemaList();
		}

		/** @inheritdoc */
		protected function mutateBySchema(iEntity $entity, array $schema, $value) {
			list(,,,,$mutator) = $schema;
			$entity->$mutator($value);
			return $entity;
		}
	}