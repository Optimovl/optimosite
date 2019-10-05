<?php

	/** Свойство объекта */
	interface iUmiObjectProperty extends iUmiEntinty {

		/**
		 * todo: move it from here
		 * Заменяет в строке символ "%" на "&#037;" и обратно, в зависимости от режима работы cms.
		 * Используется ядром для защиты от инъекций макросов на клиентской стороне
		 * @param string $string фильтруемая строка
		 * @return string отфильтрованная строка
		 */
		public static function filterInputString($string);

		/**
		 * Возвращает уникальный идентификатор значения свойства
		 * @return string
		 */
		public function getId();

		/**
		 * Возвращает значение свойства
		 * @param array $params дополнительные параметры
		 * @return mixed значение поля
		 */
		public function getValue(array $params = null);

		/**
		 * @see iUmiField::getName()
		 * @return string
		 */
		public function getName();

		/**
		 * @see iUmiField::getTitle()
		 * @return string
		 */
		public function getTitle();

		/**
		 * Устанавливает значение.
		 * Устанавливает флаг "Модифицирован".
		 * @param mixed $value новое значение для поля
		 * @return bool true если прошло успешно
		 */
		public function setValue($value);

		/**
		 * Сбрасывает значение.
		 * Устанавливает флаг "Модифицирован".
		 */
		public function resetValue();

		/**
		 * @see iUmiFieldType::getIsMultiple()
		 * @return bool
		 */
		public function getIsMultiple();

		/**
		 * @see iUmiFieldType::getIsUnsigned()
		 * @return bool
		 */
		public function getIsUnsigned();

		/**
		 * @see iUmiFieldType::getDataType()
		 * @return string
		 */
		public function getDataType();

		/**
		 * @see iUmiField::getIsLocked()
		 * @return bool
		 */
		public function getIsLocked();

		/**
		 * @see iUmiField::getIsInheritable()
		 * @return bool
		 */
		public function getIsInheritable();

		/**
		 * @see iUmiField::getIsVisible()
		 * @return bool
		 */
		public function getIsVisible();

		/**
		 * Перезагружает значение свойства
		 * @return $this
		 */
		public function refresh();

		/**
		 * Возвращает id объекта, связанного с этим свойством
		 * @return int
		 */
		public function getObjectId();

		/**
		 * Возвращает идентификатор связанного поля
		 * @return int
		 */
		public function getFieldId();

		/**
		 * @deprecated
		 * @return iUmiObject
		 */
		public function getObject();

		/**
		 * @deprecated
		 * @return iUmiField
		 */
		public function getField();
	}
