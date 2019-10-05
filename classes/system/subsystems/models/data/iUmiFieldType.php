<?php

	/** Тип поля */
	interface iUmiFieldType extends iUmiEntinty {

		/**
		 * Возвращает список всех поддерживаемых идентификаторов типа
		 * @return array список идентификаторов
		 */
		public static function getDataTypes();

		/**
		 * Возвращает имя поля таблицы БД, где будут хранится данные по идентификатору типа
		 * @param string $dataType идентификатор типа
		 * @return string|bool имя поля таблицы БД, либо false, если связь не обнаружена
		 */
		public static function getDataTypeDB($dataType);

		/**
		 * Определяет, поддерживается ли идентификатор типа
		 * @param string $dataType идентификатор типа
		 * @return bool true, если идентификатор типа поддерживается
		 */
		public static function isValidDataType($dataType);

		/**
		 * Возвращает описание типа
		 * @return string описание типа
		 */
		public function getName();

		/**
		 * Определяет, может ли значение поля данного типа
		 * состоять из массива значений (составной тип)
		 * @return bool true, если тип составной
		 */
		public function getIsMultiple();

		/**
		 * Определяет, может ли значение поля данного типа иметь знак.
		 * Зарезервировано и пока не используется
		 * @return bool true, если значение поля не будет иметь знак
		 */
		public function getIsUnsigned();

		/**
		 * Возвращает идентификатор типа
		 * @return string идентификатор типа
		 */
		public function getDataType();

		/**
		 * Устанавливает новое описание типа
		 * Устанавливает флаг "Модифицирован".
		 * @param string $name
		 */
		public function setName($name);

		/**
		 * Устанавливает, может ли значение поля данного типа
		 * состоять из массива значений (составной тип)
		 * Устанавливает флаг "Модифицирован".
		 * @param bool $isMultiple
		 */
		public function setIsMultiple($isMultiple);

		/**
		 * Устанавливает, может ли значение поля данного типа иметь знак.
		 * Зарезервировано и пока не используется
		 * Устанавливает флаг "Модифицирован".
		 * @param bool $isUnsigned
		 */
		public function setIsUnsigned($isUnsigned);

		/**
		 * Устанавливает идентификатор типа
		 * Устанавливает флаг "Модифицирован".
		 * @param string $dataType идентификатор типа
		 * @return bool true, если удалось установить,
		 * false - если идентификатор не поддерживается
		 */
		public function setDataType($dataType);

		/**
		 * Проверяет является ли тип числовым
		 * @return bool
		 */
		public function isNumber();
	}
