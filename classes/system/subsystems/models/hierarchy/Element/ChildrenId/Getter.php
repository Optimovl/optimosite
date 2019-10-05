<?php
	namespace UmiCms\System\Hierarchy\Element\ChildrenId;

	/**
	 * Класс получателя идентификаторов дочерних страниц
	 * @package UmiCms\System\Hierarchy\Element\ChildrenId
	 */
	class Getter implements iGetter {

		/** @var \IConnection $connection подключение к бд */
		private $connection;

		/** @inheritdoc */
		public function __construct(\IConnection $connection) {
			$this->connection = $connection;
		}

		/** @inheritdoc */
		public function get($parentId, $limit = self::DEFAULT_PART_LIMIT) {
			$query = $this->glueQuery(
				$this->getSelection(),
				$this->getRelationFilter($parentId),
				$this->getDeletedFilter(false),
				$this->getLimit($limit)
			);

			return $this->getResult($query);
		}

		/** @inheritdoc */
		public function getDeleted($parentId, $limit = self::DEFAULT_PART_LIMIT) {
			$query = $this->glueQuery(
				$this->getSelection(),
				$this->getRelationFilter($parentId),
				$this->getDeletedFilter(true),
				$this->getLimit($limit)
			);

			return $this->getResult($query);
		}

		/** @inheritdoc */
		public function getActive($parentId, $limit = self::DEFAULT_PART_LIMIT) {
			$query = $this->glueQuery(
				$this->getSelection(),
				$this->getRelationFilter($parentId),
				$this->getDeletedFilter(false),
				$this->getActiveFilter(true),
				$this->getLimit($limit)
			);

			return $this->getResult($query);
		}

		/** @inheritdoc */
		public function getInactive($parentId, $limit = self::DEFAULT_PART_LIMIT) {
			$query = $this->glueQuery(
				$this->getSelection(),
				$this->getRelationFilter($parentId),
				$this->getDeletedFilter(false),
				$this->getActiveFilter(false),
				$this->getLimit($limit)
			);

			return $this->getResult($query);
		}

		/**
		 * Склеивает запрос к бд
		 * @param string ...$parts части запроса
		 * @return string
		 */
		private function glueQuery(...$parts) {
			return implode(' ', $parts);
		}

		/**
		 * Возвращает часть запроса для выборки
		 * @return string
		 */
		private function getSelection() {
			return <<<SQL
SELECT `child_id` 
FROM `cms3_hierarchy_relations` 
LEFT JOIN `cms3_hierarchy` ON `cms3_hierarchy`.`id` = `cms3_hierarchy_relations`.`child_id`
SQL;
		}

		/**
		 * Возвращает часть запроса с базовым фильтром дочерних страниц
		 * @param int $parentId идентификатор родителя
		 * @return string
		 */
		private function getRelationFilter($parentId) {
			$parentId = (int) $parentId;
			$condition = ($parentId === 0) ? ' IS NULL' : " = $parentId";
			return "WHERE `rel_id` $condition";
		}

		/**
		 * Возвращает часть запроса с фильтром по статусу удаления
		 * @param bool $isDeleted статус удаления
		 * @return string
		 */
		private function getDeletedFilter($isDeleted) {
			$isDeleted = (int) $isDeleted;
			return "AND `cms3_hierarchy`.`is_deleted` = $isDeleted";
		}

		/**
		 * Возвращает часть запроса с фильтром по статусу активности
		 * @param bool $isActive статус активности
		 * @return string
		 */
		private function getActiveFilter($isActive) {
			$isActive = (int) $isActive;
			return "AND `cms3_hierarchy`.`is_active` = $isActive";
		}

		/**
		 * Возвращает часть запроса, отвечающую за ограничение на размер результата выборки
		 * @param int $limit значение ограничения
		 * @return string
		 */
		private function getLimit($limit) {
			$limit = (int) $limit;
			return "LIMIT 0, $limit";
		}

		/**
		 * Возвращает список идентификаторов дочерних страниц
		 * @param string $query выборка идентификаторов дочерних страниц
		 * @return int[]
		 */
		private function getResult($query) {
			try {
				$queryResult = $this->connection
					->queryResult($query)
					->setFetchAssoc();
			} catch (\databaseException $exception) {
				\umiExceptionHandler::report($exception);
				return [];
			}

			$idList = [];

			foreach ($queryResult as $row) {
				$idList[] = (int) $row['child_id'];
			}

			return $idList;
		}
	}