<?php

	/**
	 * @deprecated
	 * Класс, содержащий результат
	 */
	class mysqlQueryResult implements IQueryResult {

		// Закрытые данные
		private $resource = null;

		private $fetchType = IQueryResult::FETCH_ARRAY;

		/**
		 * Конструктор
		 * @param Resource $_mysqlResultResource Ресурс результата mysql запроса
		 * @param Int $_fetchType Тип выборки (см. константы)
		 */
		public function __construct($_mysqlResultResource, $_fetchType = IQueryResult::FETCH_ARRAY) {
			$this->resource = $_mysqlResultResource;
			$this->fetchType = $_fetchType;
		}

		/**
		 * Часть интерфейса IteratorAggregate, возвращает итератор для обхода результата
		 * return Iterator
		 */
		public function getIterator() {
			return new mysqlQueryResultIterator($this->resource, $this->fetchType);
		}

		/**
		 * Устанавливает тип выборки из результата (см. константы)
		 * @param Int $newType
		 * @return $this
		 */
		public function setFetchType($newType) {
			if ($newType > 3) {
				$this->fetchType = IQueryResult::FETCH_ARRAY;
			} else {
				$this->fetchType = $newType;
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

		/**
		 * Возвращает тип выборки
		 * @return Int
		 */
		public function getFetchType() {
			return $this->fetchType;
		}

		/**
		 * Выбирает строку значений из результата
		 * и возвращает ее в соответствии с заданым типом выборки
		 */
		public function fetch() {
			$result = null;
			switch ($this->fetchType) {
				case IQueryResult::FETCH_ARRAY  :
					$result = mysql_fetch_array($this->resource);
					break;
				case IQueryResult::FETCH_ROW    :
					$result = mysql_fetch_row($this->resource);
					break;
				case IQueryResult::FETCH_ASSOC  :
					$result = mysql_fetch_assoc($this->resource);
					break;
				case IQueryResult::FETCH_OBJECT :
					$result = mysql_fetch_object($this->resource);
					break;
			}
			return $result;
		}

		/** @inheritdoc */
		public function hasRows() {
			return $this->length() !== 0;
		}

		/**
		 * Возвращает количество выбранных строк
		 * @return Int
		 */
		public function length() {
			return mysql_num_rows($this->resource);
		}

		/** Освобождает память занятую результатами запроса */
		public function freeResult() {
			return mysql_free_result($this->resource);
		}
	}

	;

	class mysqlQueryResultIterator implements IQueryResultIterator {

		private $resource = null;

		private $number = 0;

		private $rowcount = 0;

		private $fetchType = IQueryResult::FETCH_ARRAY;

		function __construct($_mysqlResultResource, $_fetchType = IQueryResult::FETCH_ARRAY) {
			$this->resource = $_mysqlResultResource;
			$this->fetchType = $_fetchType;
			$this->rowcount = $this->resource ? mysql_num_rows($this->resource) : 0;
		}

		function rewind() {
			if ($this->resource && (mysql_num_rows($this->resource) > 0)) {
				mysql_data_seek($this->resource, 0);
			}
			$this->number = 0;
		}

		function valid() {
			return $this->number < $this->rowcount;
		}

		function key() {
			return $this->number;
		}

		function current() {
			switch ($this->fetchType) {
				case IQueryResult::FETCH_ARRAY  :
					return mysql_fetch_array($this->resource);
				case IQueryResult::FETCH_ROW    :
					return mysql_fetch_row($this->resource);
				case IQueryResult::FETCH_ASSOC  :
					return mysql_fetch_assoc($this->resource);
				case IQueryResult::FETCH_OBJECT :
					return mysql_fetch_object($this->resource);
			}
		}

		function next() {
			$this->number++;
		}
	}

	;
?>
