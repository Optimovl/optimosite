<?php

	/** Интерфейс коллекции полей */
	interface iUmiFieldsCollection extends iSingleton {

		/**
		 * Создает поле и возвращает его.
		 * @param string $name строковой идентификатор поля (GUID)
		 * @param string $title наименование поля
		 * @param int $fieldTypeId идентификатор типа поля
		 * @return iUmiField|bool
		 */
		public function add($name, $title, $fieldTypeId);

		/**
		 * Возвращает поле по его идентификатору
		 * @param int $id идентификатор поля
		 * @return iUmiField|bool
		 */
		public function getById($id);

		/**
		 * Удаляет поле по его идентификатору
		 * @param int $id идентификатор поля
		 * @return bool было ли удалено поле
		 */
		public function delById($id);

		/**
		 * Проверяет существует ли поле с заданным идентификатором
		 * @param int $id идентификатор поля
		 * @return bool
		 */
		public function isExists($id);

		/**
		 * Возвращает список идентификаторов полей с заданным типом
		 * @param iUmiFieldType $type тип поля
		 * @return array
		 * @throws Exception
		 */
		public function getFieldIdListByType(iUmiFieldType $type);

		/**
		 * Возвращает список полей
		 * @param int[] $idList список идентификаторов полей
		 * @return iUmiField[]
		 */
		public function getFieldList(array $idList);

		/**
		 * Создает поле и возвращает его id
		 * @param string $name строковой идентификатор поля (GUID)
		 * @param string $title наименование поля
		 * @param int $fieldTypeId идентификатор типа поля
		 * @param bool $isVisible значения флага "Видимое"
		 * @param bool $isLocked значение флага "Заблокированное"
		 * @param bool $isInheritable значение флага "Наследуемое"
		 * @return int
		 */
		public function addField(
			$name,
			$title,
			$fieldTypeId,
			$isVisible = true,
			$isLocked = false,
			$isInheritable = false
		);

		/**
		 * Возвращает поле по его идентификатору
		 * @param int $id идентификатор поля
		 * @param array|bool $data данные поля или false
		 * @return iUmiField|bool
		 */
		public function getField($id, $data = false);

		/**
		 * Удаляет поле по его идентификатору
		 * @param int $id идентификатор поля
		 * @return bool было ли удалено поле
		 */
		public function delField($id);

		/** Очищает внутренний кеш */
		public function clearCache();

		/**
		 * Фильтрует список полей от полей с заданными именами
		 * @param iUmiField[] &$fieldList список полей
		 * @param string[] $blackList список имен
		 * @return $this;
		 */
		public function filterListByNameBlackList(array &$fieldList, array $blackList);

		/**
		 * Фильтрует список полей от полей c неразрешенными типами
		 * @param iUmiField[] &$fieldList список полей
		 * @param string[] $whiteList список разрешеных типов полей
		 * @return $this;
		 */
		public function filterListByTypeWhiteList(array &$fieldList, array $whiteList);
	}
