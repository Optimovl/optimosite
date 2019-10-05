<?php

	/** Класс-коллекция, который обеспечивает управление иерархическими типами */
	class umiHierarchyTypesCollection extends singleton implements iSingleton, iUmiHierarchyTypesCollection {

		/** @var iUmiHierarchyType[] $typeList список загруженных иерархических типов */
		private $typeList = [];

		/** Конструктор */
		protected function __construct() {
		}

		/**
		 * @inheritdoc
		 * @return iUmiHierarchyTypesCollection
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function getType($id) {
			$type = $this->getLoadedType($id);

			if ($type instanceof iUmiHierarchyType) {
				return $type;
			}

			return false;
		}

		/** @inheritdoc */
		public function getTypeByName($name, $ext = false) {
			if ($name == 'content' and $ext == 'page') {
				$ext = false;
			}

			foreach ($this->getTypesList() as $type) {
				if ($type->getName() == $name && $ext === false) {
					return $type;
				}
				if ($type->getName() == $name && $type->getExt() == $ext && $ext !== false) {
					return $type;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function getTypesByModules($modules) {
			$modules = (array) $modules;
			return array_filter(
				$this->getTypesList(),
				function ($type) use ($modules) {
					/** @var iUmiHierarchyType $type */
					return in_array($type->getName(), $modules);
				}
			);
		}

		/** @inheritdoc */
		public function addType($name, $title, $ext = '') {
			$type = $this->getTypeByName($name, $ext);

			if ($type instanceof iUmiHierarchyType) {
				$type->setTitle($title);
				return $type->getId();
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$nameTemp = $connection->escape($name);
			$sql = "INSERT INTO cms3_hierarchy_types (name) VALUES('{$nameTemp}')";
			$connection->query($sql);

			$typeId = $connection->insertId();

			$type = new umiHierarchyType($typeId);
			$type->setName($name);
			$type->setTitle($title);
			$type->setExt($ext);
			$type->commit();

			$this->setLoadedType($type);

			return $typeId;
		}

		/** @inheritdoc */
		public function delType($id) {
			if (!$this->isExists($id)) {
				return false;
			}

			$this->unsetLoadedType($id);

			$id = (int) $id;
			$sql = "DELETE FROM cms3_hierarchy_types WHERE id = $id";
			ConnectionPool::getInstance()
				->getConnection()
				->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function isExists($id) {
			if (!is_string($id) && !is_int($id)) {
				return false;
			}

			return array_key_exists($id, $this->getTypesList());
		}

		/** @inheritdoc */
		public function getTypesList() {
			if (empty($this->typeList)) {
				$this->loadTypeList();
			}

			return $this->typeList;
		}

		/** @inheritdoc */
		public function clearCache() {
			$this->typeList = [];
			$this->loadTypeList();
		}

		/** Загружает список типов */
		private function loadTypeList() {
			$sql = 'SELECT  `id`, `name`, `title`, `ext` FROM `cms3_hierarchy_types` ORDER BY `name`, `ext`';
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$id = $row[0];

				try {
					$type = new umiHierarchyType($id, $row);
				} catch (privateException $e) {
					$e->unregister();
					continue;
				}

				$this->setLoadedType($type);
			}

			return true;
		}

		/**
		 * Удаляет тип из кеша
		 * @param int $id id типа
		 * @return $this
		 */
		private function unsetLoadedType($id) {
			unset($this->typeList[$id]);
			return $this;
		}

		/**
		 * Добавляет тип в кеш
		 * @param iUmiHierarchyType $type тип
		 * @return $this
		 */
		private function setLoadedType(iUmiHierarchyType $type) {
			$this->typeList[$type->getId()] = $type;
			return $this;
		}

		/**
		 * Возвращает тип из кеша
		 * @param int $id id типа
		 * @return iUmiHierarchyType|null
		 */
		private function getLoadedType($id) {
			if ($this->isExists($id)) {
				return $this->typeList[$id];
			}

			return null;
		}
	}
