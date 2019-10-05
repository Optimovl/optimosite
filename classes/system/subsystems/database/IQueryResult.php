<?php

	/** Класс, содержащий результат */
	interface IQueryResult extends IteratorAggregate {

		// Константы, определяющие тип выборки результата
		const FETCH_ARRAY = 0;

		const FETCH_ROW = 1;

		const FETCH_ASSOC = 2;

		const FETCH_OBJECT = 3;

		/**
		 * Конструктор
		 * @param Resource $_ResultResource Ресурс результата mysql запроса
		 * @param int $_fetchType Тип выборки (см. константы)
		 */
		public function __construct($_ResultResource, $_fetchType = self::FETCH_ARRAY);

		/**
		 * Устанавливает тип выборки из результата (см. константы)
		 * @param int $newType
		 * @return $this
		 */
		public function setFetchType($newType);

		/**
		 * Устанавливает тип выборки из результата - массив со смешанными ключами
		 * @return $this
		 */
		public function setFetchArray();

		/**
		 * Устанавливает тип выборки из результата - массив с числовыми ключами
		 * @return $this
		 */
		public function setFetchRow();

		/**
		 * Устанавливает тип выборки из результата - массив со строковыми ключами
		 * @return $this
		 */
		public function setFetchAssoc();

		/**
		 * Устанавливает тип выборки из результата - объект
		 * @return $this
		 */
		public function setFetchObject();

		/**
		 * Возвращает тип выборки
		 * @return Int
		 */
		public function getFetchType();

		/**
		 * Выбирает строку значений из результата
		 * и возвращает ее в соответствии с заданным типом выборки
		 */
		public function fetch();

		/**
		 * Определяет содержит ли результат строки
		 * @return bool
		 */
		public function hasRows();

		/**
		 * Возвращает количество выбранных строк
		 * @return Int
		 */
		public function length();

		/** Освобождает память занятую результатами запроса */
		public function freeResult();
	}

	/** Интерфейс итератора результата запроса */
	interface IQueryResultIterator extends Iterator {

	}
