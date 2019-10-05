<?php

	/**
	 * Базовый тип, используется:
	 * 1. Для связывания страниц с соответствующим обработчиком (модуль/метод)
	 * 2. Для категоризации типов данных
	 * В новой терминологии getName()/getExt() значило бы getModule()/getMethod() соответственно
	 */
	class umiHierarchyType extends umiEntinty implements iUmiHierarchyType {

		private $name, $title, $ext;

		protected $store_type = 'element_type';

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		/** @inheritdoc */
		public function getModule() {
			return $this->getName();
		}

		/** @inheritdoc */
		public function getMethod() {
			return $this->getExt();
		}

		/** @inheritdoc */
		public function getExt() {
			return $this->ext;
		}

		/** @inheritdoc */
		public function setName($name) {
			if ($this->getName() != $name) {
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setTitle($title) {
			if ($this->getTitle() != $title) {
				$title = $this->translateI18n($title, 'hierarchy-type-');
				$this->title = $title;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setExt($ext) {
			if ($this->getExt() != $ext) {
				$this->ext = $ext;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < 4) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = "SELECT id, name, title, ext FROM cms3_hierarchy_types WHERE id = $escapedId LIMIT 0,1";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 4) {
				return false;
			}

			list($id, $name, $title, $ext) = $row;
			$this->name = $name;
			$this->title = $title;
			$this->ext = $ext;

			return true;
		}

		/** @inheritdoc */
		protected function save() {
			$name = self::filterInputString($this->name);
			$title = self::filterInputString($this->title);
			$ext = self::filterInputString($this->ext);

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
UPDATE cms3_hierarchy_types
SET name = '{$name}', title = '{$title}', ext = '{$ext}' WHERE id = '{$this->id}'
SQL;
			$connection->query($sql);

			return true;
		}
	}
