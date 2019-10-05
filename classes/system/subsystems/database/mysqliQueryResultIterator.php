<?php

	/** Итератор результата запроса */
	class mysqliQueryResultIterator implements IQueryResultIterator {

		/** @var mysqli_result $result результат запроса */
		private $result;

		/** @var int $number позиция текущего элемента */
		private $number = 0;

		/** @var int $rowsCount количество элементов */
		private $rowsCount;

		/** @var int $fetchType код типа результата выборки */
		private $fetchType;

		/**
		 * Конструктор
		 * @param mysqli_result $queryResult результат выборки
		 * @param int $fetchType код типа результата выборки
		 */
		public function __construct(mysqli_result $queryResult, $fetchType) {
			$this->result = $queryResult;
			$this->fetchType = $fetchType;
			$this->rowsCount = $queryResult->num_rows;
		}

		/** Возвращает итератор на первый элемент */
		public function rewind() {
			if ($this->result->num_rows > 0) {
				$this->result->data_seek(0);
			}
			$this->number = 0;
		}

		/**
		 * Проверяет корректность позиции элемента
		 * @return bool
		 */
		public function valid() {
			return $this->number < $this->rowsCount;
		}

		/**
		 * Возвращает ключ текущего элемента.
		 * @return int
		 */
		public function key() {
			return $this->number;
		}

		/**
		 * Возвращает текущий элемент
		 * @return array|mixed|object|stdClass
		 */
		public function current() {
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

		/** Переходит к следующему элементу */
		public function next() {
			$this->number++;
		}
	}
