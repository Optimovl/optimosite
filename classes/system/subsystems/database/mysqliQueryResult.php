<?php

	/** Класс для работы с результатом запроса */
	class mysqliQueryResult implements IQueryResult {

		/** @var mysqli_result $result результат выборки */
		private $result;

		/** @var int код типа формирования результата */
		private $fetchType;

		/** @inheritdoc */
		public function __construct($queryResult, $fetchType = IQueryResult::FETCH_ARRAY) {
			if (!$queryResult instanceof mysqli_result) {
				throw new Exception('Mysqli result expected');
			}
			$this->result = $queryResult;
			$this->fetchType = $fetchType;
		}

		/**
		 * Устанавливает код типа результата выборки
		 * @param int $fetchType код типа результата выборки
		 * @return $this
		 */
		public function setFetchType($fetchType) {
			if (!is_numeric($fetchType) || $fetchType > 3) {
				$this->fetchType = IQueryResult::FETCH_ARRAY;
			} else {
				$this->fetchType = $fetchType;
			}
			return $this;
		}

		/** @inheritdoc */
		public function setFetchArray() {
			return $this->setFetchType(self::FETCH_ARRAY);
		}

		/** @inheritdoc */
		public function setFetchRow() {
			return $this->setFetchType(self::FETCH_ROW);
		}

		/** @inheritdoc */
		public function setFetchAssoc() {
			return $this->setFetchType(self::FETCH_ASSOC);
		}

		/** @inheritdoc */
		public function setFetchObject() {
			return $this->setFetchType(self::FETCH_OBJECT);
		}

		/** Возвращает код типа результата выборки */
		public function getFetchType() {
			$this->fetchType;
		}

		/**
		 * Возвращает первый элемент результата выборки
		 * @return array|mixed|object|stdClass
		 */
		public function fetch() {
			switch ($this->fetchType) {
				case IQueryResult::FETCH_ARRAY : {
					return $this->result->fetch_array();
				}
				case IQueryResult::FETCH_ROW : {
					return $this->result->fetch_row();
				}
				case IQueryResult::FETCH_ASSOC : {
					return $this->result->fetch_assoc();
				}
				case IQueryResult::FETCH_OBJECT : {
					return $this->result->fetch_object();
				}
			}
		}

		/** @inheritdoc */
		public function hasRows() {
			return $this->length() !== 0;
		}

		/**
		 * Возвращает количество элементов результата выборки
		 * @return int
		 */
		public function length() {
			return $this->result->num_rows;
		}

		/**
		 * Возвращает итератор результата выборки
		 * @return mysqliQueryResultIterator
		 */
		public function getIterator() {
			return new mysqliQueryResultIterator($this->result, $this->fetchType);
		}

		/** Освобождает память занятую результатами запроса */
		public function freeResult() {
			$this->result->free_result();
		}
	}
