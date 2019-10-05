<?php
	namespace UmiCms\System\Orm\Entity\Repository;

	/**
	 * Класс истории репозитория сущностей
	 * @package UmiCms\System\Orm\Entity\Repository
	 */
	class History implements iHistory {

		/** @var array $createLog журнал записей о создании сущностей */
		private $createLog = [];

		/** @var array $updateLog журнал записей об обновлении сущностей */
		private $updateLog = [];

		/** @var array $deleteLog журнал записей об удалении сущностей */
		private $deleteLog = [];

		/** @var array $getLog журнал записей о запросе сущности(ей) по значению атрибута */
		private $getLog = [];

		/** @var array $getAllLog журнал записей полного списка сущностей */
		private $getAllLog = [];

		/** @inheritdoc */
		public function logCreate($id) {
			$this->createLog[] = $id;
			return $this;
		}

		/** @inheritdoc */
		public function logUpdate($id) {
			$this->updateLog[] = $id;
			return $this;
		}

		/** @inheritdoc */
		public function logDelete($id) {
			$this->deleteLog[] = $id;
			return $this;
		}

		/** @inheritdoc */
		public function logGet($name, $value) {
			$this->getLog[] = $this->packGetArgs($name, $value);
			return $this;
		}

		/** @inheritdoc */
		public function logGetAll($count) {
			$this->getAllLog[] = $count;
			return $this;
		}

		/** @inheritdoc */
		public function isCreationLogged($id) {
			return in_array($id, $this->createLog);
		}

		/** @inheritdoc */
		public function isUpdatingLogged($id) {
			return in_array($id, $this->updateLog);
		}

		/** @inheritdoc */
		public function isDeletionLogged($id) {
			return in_array($id, $this->deleteLog);
		}

		/** @inheritdoc */
		public function isGettingLogged($name, $value) {
			return in_array($this->packGetArgs($name, $value), $this->getLog);
		}

		/** @inheritdoc */
		public function isGetAllLogged() {
			return count($this->getAllLog) > 0;
		}

		/** @inheritdoc */
		public function readCreateLog() {
			return $this->createLog;
		}

		/** @inheritdoc */
		public function readUpdateLog() {
			return $this->updateLog;
		}

		/** @inheritdoc */
		public function readDeleteLog() {
			return $this->deleteLog;
		}

		/** @inheritdoc */
		public function readGetLog() {
			return $this->getLog;
		}

		/** @inheritdoc */
		public function readGetAllLog() {
			return $this->getAllLog;
		}

		/** @inheritdoc */
		public function clear() {
			$this->createLog = [];
			$this->updateLog = [];
			$this->deleteLog = [];
			$this->getLog = [];
			$this->getAllLog = [];
			return $this;
		}

		/**
		 * Упаковывает параметры запроса сущности(ей) по значению атрибута
		 * @param string $name имя атрибута
		 * @param string $value значение атрибута
		 * @return string
		 */
		private function packGetArgs($name, $value) {
			return sprintf('%s::%s', $name, serialize($value));
		}
	}