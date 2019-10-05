<?php

	/** Интерфейс языка */
	interface iLang extends iUmiEntinty {

		/**
		 * Возвращает название
		 * @return string
		 */
		public function getTitle();

		/**
		 * Устанавливает название
		 * @param string $title
		 * @throws wrongParamException если название невалидно
		 */
		public function setTitle($title);

		/**
		 * Возвращает префикс
		 * @return string
		 */
		public function getPrefix();

		/**
		 * Устанавливает префикс
		 * @param string $prefix
		 * @throws wrongParamException если префикс невалидный
		 */
		public function setPrefix($prefix);

		/**
		 * Проверяет является ли язык языком по умолчанию
		 * @return bool
		 */
		public function getIsDefault();

		/**
		 * Устанавливает значение флага "по умолчанию" языка.
		 * Служебный метод, в прикладном коде стоит использовать:
		 * langsCollection::setDefault()
		 * @param bool $flag значение флага
		 */
		public function setIsDefault($flag);
	}
