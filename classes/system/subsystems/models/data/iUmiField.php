<?php

	/** Интерфейс поля */
	interface iUmiField extends iUmiEntinty {

		/**
		 * Возвращает строковый идентификатор (GUID)
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает строковый идентификатор (GUID)
		 * @param string $name строковый идентификатор (GUID)
		 * @throws wrongParamException
		 */
		public function setName($name);

		/**
		 * Возвращает наименование поля
		 * @return string название поля
		 */
		public function getTitle();

		/**
		 * Устанавливает наименование поля
		 * @param string $title описание поля
		 * @throws wrongParamException
		 */
		public function setTitle($title);

		/**
		 * Определяет является ли поле видимым на сайте
		 * @return bool
		 */
		public function getIsVisible();

		/**
		 * Устанавливает видимость поля на сайте (для tpl)
		 * @param bool $flag значение флага
		 */
		public function setIsVisible($flag);

		/**
		 * Возвращает идентификатор типа
		 * @return int
		 */
		public function getFieldTypeId();

		/**
		 * Устанавливает идентификатор типа
		 * @param int $id идентификатор типа
		 */
		public function setFieldTypeId($id);

		/**
		 * Возвращает идентификатор связанного справочника
		 * @return int|null
		 */
		public function getGuideId();

		/**
		 * Связано ли поле с каким-либо справочником
		 * @return bool
		 */
		public function hasGuide();

		/**
		 * Устанавливает идентификатор связанного справочника
		 * @param int|null $guideId идентификатор справочника
		 */
		public function setGuideId($guideId);

		/**
		 * Определяет индексируется ли значение поля для поиска
		 * @return bool
		 */
		public function getIsInSearch();

		/**
		 * Устанавливает, что поле индексируется для поиска
		 * @param bool $flag значение флага
		 */
		public function setIsInSearch($flag);

		/**
		 * Определяет индексируется ли значения поля для фильтра
		 * @return bool
		 */
		public function getIsInFilter();

		/**
		 * Устанавливает, что поле индексируется для фильтра
		 * @param bool $flag значение флага
		 */
		public function setIsInFilter($flag);

		/**
		 * Возвращает подсказку
		 * @return string
		 */
		public function getTip();

		/**
		 * Устанавливает подсказку
		 * @param string $tip подсказка
		 */
		public function setTip($tip);

		/**
		 * Определяет является ли поле важным для отображения
		 * @return bool
		 */
		public function isImportant();

		/**
		 * Устанавливает, что поле является важным для отображения
		 * @param bool $flag значение флага
		 */
		public function setImportanceStatus($flag = false);

		/**
		 * Определяет является ли поле обязательным для заполнения
		 * @return bool
		 */
		public function getIsRequired();

		/**
		 * Устанавливает, что поле является обязательным для заполнения
		 * @param bool $flag значение флага
		 */
		public function setIsRequired($flag = false);

		/**
		 * Возвращает идентификатор ограничения поля
		 * @return int
		 */
		public function getRestrictionId();

		/**
		 * Устанавливает идентификатор ограничения поля
		 * @param int|bool $id идентификатор ограничения
		 */
		public function setRestrictionId($id = false);

		/**
		 * Отключает ограничение значения поля
		 * @return $this
		 */
		public function removeRestriction();

		/**
		 * Определяет является ли поле системным.
		 * @return bool
		 */
		public function getIsSystem();

		/**
		 * Устанавливает, что поле является системным.
		 * @param bool $flag значение флага
		 */
		public function setIsSystem($flag = false);

		/**
		 * Возвращает тип данных поля
		 * @return string
		 */
		public function getDataType();

		/**
		 * Определяет может ли поле иметь несколько значений
		 * @return bool
		 */
		public function isMultiple();

		/**
		 * @deprecated
		 * @return bool
		 */
		public function getIsLocked();

		/**
		 * @deprecated
		 * @param bool $flag
		 */
		public function setIsLocked($flag);

		/**
		 * @deprecated
		 * @return bool
		 */
		public function getIsInheritable();

		/**
		 * @deprecated
		 * @param bool $flag
		 */
		public function setIsInheritable($flag);

		/**
		 * @deprecated
		 * @return bool
		 */
		public function getIsSortable();

		/**
		 * @deprecated
		 * @param bool $flag
		 */
		public function setIsSortable($flag = false);

		/**
		 * @deprecated
		 * @return iUmiFieldType|bool
		 */
		public function getFieldType();
	}
