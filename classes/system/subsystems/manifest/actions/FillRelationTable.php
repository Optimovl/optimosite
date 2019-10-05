<?php

	namespace UmiCms\Manifest\Migrate\Object\Type\Hierarchy;

	use UmiCms\Service;
	use UmiCms\System\Data\Object\Type\Hierarchy\Relation\iMigration;
	use UmiCms\System\Data\Object\Type\Hierarchy\Relation\iRepository;

	/**
	 * Команда заполнения таблицы иерархических связей объектных типов данных
	 * @package UmiCms\Manifest\Migrate\Object\Type\Hierarchy
	 */
	class FillRelationTableAction extends \IterableAction {

		/** @const int LIMIT ограничение на количество типов, обрабатываемых за одну итерацию */
		const LIMIT = 150;

		/** @inheritdoc */
		public function execute() {
			$offset = $this->getOffset();
			$typeIdList = \umiObjectTypesCollection::getInstance()
				->getIdList(self::LIMIT, $offset);
			$migration = $this->getMigration();

			foreach ($typeIdList as $typeId) {
				$migration->migrate($typeId);
			}

			$this->setOffset(self::LIMIT + $offset);

			if (isEmptyArray($typeIdList)) {
				$this->setIsReady();
				$this->resetState();
			}

			$this->saveState();
			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			$this->getRepository()
				->deleteAll();
			return $this;
		}

		/** @inheritdoc */
		protected function getStartState() {
			return [
				'offset' => 0
			];
		}

		/**
		 * Возвращает смещение
		 * @return int
		 */
		private function getOffset() {
			$offset = $this->getStatePart('offset');
			return is_numeric($offset) ? $offset : 0;
		}

		/**
		 * Устанавливает смещение
		 * @param int $offset смещение
		 * @return $this
		 */
		private function setOffset($offset) {
			return $this->setStatePart('offset', $offset);
		}

		/**
		 * Возвращает миграциию иерархических связей
		 * @return iMigration
		 */
		private function getMigration() {
			return Service::get('ObjectTypeHierarchyRelationMigration');
		}

		/**
		 * Возвращает репозиторий иерархических связей объектных типов
		 * @return iRepository
		 */
		private function getRepository() {
			return Service::get('ObjectTypeHierarchyRelationRepository');
		}
	}
