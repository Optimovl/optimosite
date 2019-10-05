<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy\Relation;

	/**
	 * Класс миграции связей иерархии объектных типов данных
	 * @package UmiCms\System\Data\Object\Type\Hierarchy\Relation
	 */
	class Migration implements iMigration {

		/** @var \IConnection $connection подключение к бд */
		private $connection;

		/** @var iRepository $repository репозиторий иерархических связей типов данных */
		private $repository;

		/** @inheritdoc */
		public function __construct(\IConnection $connection, iRepository $repository) {
			$this->connection = $connection;
			$this->repository = $repository;
		}

		/** @inheritdoc */
		public function migrate($typeId) {
			$ancestorList = $this->getAncestorList($typeId);
			$repository = $this->getRepository();
			$level = count($ancestorList);

			foreach ($ancestorList as $ancestorId) {
				$repository->create($ancestorId, $typeId, $level);
			}

			return $this;
		}

		/**
		 * Возвращает список родительских типов для заданного типа
		 * @param int $typeId идентификатор типа
		 * @return int[]
		 */
		private function getAncestorList($typeId) {
			$currentId = (int) $typeId;
			$connection = $this->getConnection();
			$ancestors = [];

			while (true) {
				$currentId = ($currentId === 0) ? 'NULL' : $currentId;
				$sql = "SELECT `parent_id` FROM `cms3_object_types` WHERE `id` = $currentId";
				$result = $connection->queryResult($sql);

				if ($result->length() === 0) {
					break;
				}

				$row = $result->fetch();
				$currentId = (int) $row['parent_id'];
				$isCyclic = in_array($currentId, $ancestors);

				if ($isCyclic) {
					break;
				}

				$ancestors[] = $currentId;
			}

			return array_reverse($ancestors);
		}

		/**
		 * Возвращает подключение к бд
		 * @return \IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Возвращает репозиторий иерархических связей
		 * @return iRepository
		 */
		private function getRepository() {
			return $this->repository;
		}
	}
