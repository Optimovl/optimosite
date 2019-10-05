<?php

	/** Группа поля */
	interface iUmiFieldsGroup extends iUmiEntinty {

		/**
		 * Возвращает список всех групп с указанным названием
		 * вне зависимости от типа данных
		 * @param string $name название группы полей
		 * @return iUmiFieldsGroup[]|bool
		 */
		public static function getAllGroupsByName($name);

		/**
		 * Возвращает строковой id группы
		 * @return string строковой id группы
		 */
		public function getName();

		/**
		 * Возвращает название группы
		 * @return string название группы в текущей языковой версии
		 */
		public function getTitle();

		/**
		 * Возвращает id типа данных, к которому относится группа полей
		 * @return int id типа данных
		 */
		public function getTypeId();

		/**
		 * Возвращает порядковый номер группы,
		 * по которому она сортируется в рамках типа данных
		 * @return int порядковый номер
		 */
		public function getOrd();

		/**
		 * Определяет, активна ли группа полей
		 * @return bool значение флага активности
		 */
		public function getIsActive();

		/**
		 * Определяет, видима ли группа полей
		 * @return bool значение флага видимости
		 */
		public function getIsVisible();

		/**
		 * Определяет, заблокирована ли группа полей (разработчиком)
		 * @return bool значение флага блокировка
		 */
		public function getIsLocked();

		/**
		 * Возвращает подсказку группы полей
		 * @return string текст подсказки
		 */
		public function getTip();

		/**
		 * Устанавливает строковой id группы
		 * @param string $name новый строковой id группы полей
		 */
		public function setName($name);

		/**
		 * Устанавливает название группы полей
		 * @param string $title новое название группы полей
		 */
		public function setTitle($title);

		/**
		 * Устанавливает тип данных, которому принадлежит группа полей
		 * @param int $typeId id нового типа данных
		 * @return bool true
		 */
		public function setTypeId($typeId);

		/**
		 * Устанавливает новое значение порядка сортировки
		 * @param int $ord новый порядковый номер
		 */
		public function setOrd($ord);

		/**
		 * Устанавливает активность группы полей
		 * @param bool $isActive новое значение флага активности
		 */
		public function setIsActive($isActive);

		/**
		 * Устанавливает видимость группы полей
		 * @param bool $isVisible новое значение флага видимости
		 */
		public function setIsVisible($isVisible);

		/**
		 * Устанавливает состояние блокировки группы полей
		 * @param bool $isLocked новое значение флага блокировки
		 */
		public function setIsLocked($isLocked);

		/**
		 * Устанавливает новую подсказку для группы полей
		 * @param string $newTip текст новой подсказки
		 */
		public function setTip($newTip);

		/**
		 * Возвращает список всех полей в группе
		 * @return iUmiField[]
		 */
		public function getFields();

		/**
		 * Определяет есть ли в группе поля
		 * @return bool
		 */
		public function hasFields();

		/**
		 * Определяет есть ли в группе поле с заданным именем
		 * @param string $name имя
		 * @return bool
		 */
		public function hasFieldWithName($name);

		/**
		 * Возвращает поле группы по имени
		 * @param string $name имя
		 * @return iUmiField|null
		 */
		public function getFieldByName($name);

		/**
		 * Присоединяет к группе еще одно поле
		 * @param int $fieldId id присоединяемого поля
		 * @param bool $ignoreLoaded если true, добавит уже внесенное в эту группу поле
		 * @return bool
		 * @throws coreException
		 */
		public function attachField($fieldId, $ignoreLoaded = false);

		/**
		 * Открепляет поле от группы.
		 * Если после открепления у поля больше нет групп - оно удаляется.
		 * @param int $id id поля (iUmiField)
		 * @return bool
		 */
		public function detachField($id);

		/**
		 * Перемещает одно поле после другого поля в группе
		 * @param int $fieldId id перемещаемого поля
		 * @param int $afterFieldId id поля, после которого нужно расположить перемещаемое поле
		 * @param int $groupId id группы полей, в которой производятся перемещения
		 * @param bool $isLast переместить в конец
		 * @return bool результат операции
		 * @throws coreException
		 */
		public function moveFieldAfter($fieldId, $afterFieldId, $groupId, $isLast);

		/**
		 * @internal
		 * @param array|bool $rows
		 */
		public function loadFields($rows = false);
	}
