<?php

	namespace UmiCms\System\Data\Field\Restriction;

	/**
	 * Коллекция ограничений полей
	 * @todo дописать коллекцию, зарефакторить класс \baseRestriction, сделать интерфейс ограничения поля
	 * @package UmiCms\System\Data\Field\Restriction
	 */
	class Collection implements iCollection {

		/** @var \IConnection $connection подключение к бд */
		private $connection;

		/** @inheritdoc */
		public function __construct(\IConnection $connection) {
			$this->connection = $connection;
		}

		/** @inheritdoc */
		public function delete($id) {
			$id = (int) $id;

			$deleteSql = <<<SQL
DELETE FROM `cms3_object_fields_restrictions` WHERE `id` = $id
SQL;
			$this->getConnection()
				->query($deleteSql);
			return $this;
		}

		/** @inheritdoc */
		public function getFirstByPrefix($prefix) {
			$list = $this->getListByPrefix($prefix);

			if (empty($list)) {
				return null;
			}

			return array_shift($list);
		}

		/** @inheritdoc */
		public function getListByPrefix($prefix) {
			return array_filter(
				\baseRestriction::getList(),
				function (\baseRestriction $restriction) use ($prefix) {
					return $restriction->getClassName() == $prefix;
				}
			);
		}

		/**
		 * Возвращает подключение к бд
		 * @return \IConnection
		 */
		private function getConnection() {
			return $this->connection;
		}
	}
