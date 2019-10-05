<?php

	namespace UmiCms\Manifest\Migrate\Object\Type\Hierarchy;

	use UmiCms\Service;
	use UmiCms\System\Data\Object\Type\Hierarchy\Relation\iRepository;

	/**
	 * Команда очищения таблицы иерархических связей объектных типов данных
	 * @package UmiCms\Manifest\Migrate\Object\Type\Hierarchy
	 */
	class TruncateRelationTableAction extends \Action {

		/** @inheritdoc */
		public function execute() {
			$this->getRepository()
				->deleteAll();
			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}

		/**
		 * Возвращает репозиторий иерархических связей объектных типов
		 * @return iRepository
		 */
		private function getRepository() {
			return Service::get('ObjectTypeHierarchyRelationRepository');
		}
	}
