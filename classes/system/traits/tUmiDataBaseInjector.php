<?php

	/** Трейт работника с базой данных */
	trait tUmiDataBaseInjector {

		/** @var IConnection $connection подключение в базе данных */
		private $connection;

		/**
		 * Возвращает подключение к базе данных
		 * @return IConnection
		 * @throws Exception
		 */
		public function getConnection() {
			if (!$this->connection instanceof IConnection) {
				throw new RequiredPropertyHasNoValueException('You should set IConnection first');
			}

			return $this->connection;
		}

		/**
		 * Устанавливает подключение к базе данных
		 * @param IConnection $connection подключение к базе данных
		 */
		public function setConnection(IConnection $connection) {
			$this->connection = $connection;
		}
	}

