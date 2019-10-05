<?php

	/** Интерфейс сущности коллекции */
	interface iUmiCollectionItem {

		/**
		 * Конструктор
		 * @param array $param параметры сущности
		 * @param iUmiConstantMap $map
		 */
		public function __construct(array $param, iUmiConstantMap $map);

		/**
		 * Возвращает идентификатор сущности
		 * @return int
		 */
		public function getId();

		/**
		 * Устанавливает значение поля/свойства/атрибута сущности
		 * @param string $name имя поля/свойства/атрибута
		 * @param string $value значение поля/свойства/атрибута
		 * @return bool
		 */
		public function setValue($name, $value);

		/**
		 * Возвращает значение поля/свойства/атрибута сущности
		 * @param string $name имя поля/свойства/атрибута
		 * @return mixed
		 */
		public function getValue($name);

		/** Применяет изменения сущности */
		public function commit();

		/**
		 * Существует ли у сущности поле/свойство/атрибут с заданным именем
		 * @param string $name имя поля/свойства/атрибута
		 * @return bool
		 */
		public function isExistsProp($name);

		/**
		 * Возвращает список имен полей/свойств/атрибутов сущности
		 * @return array
		 */
		public function getPropsList();

		/**
		 * Была ли сущности изменена
		 * @return bool
		 */
		public function isUpdated();

		/**
		 * Изменяет значение флага "была обновлена" сущности
		 * @param bool $isUpdated значение флага
		 */
		public function setUpdatedStatus($isUpdated);

		/**
		 * Возвращает массив полей/свойств/атрибутов сущности со значениями
		 * @return array ['name' => 'value]
		 */
		public function export();

		/**
		 * Импортирует данные в поля/свойства/атрибуты сущности
		 * @param array $data данные ['name' => 'value]
		 * @return bool
		 */
		public function import(array $data);

		/**
		 * Перемещает текущую сущность по отношению к заданной
		 * @param \iUmiCollectionItem $baseEntity заданная сущность
		 * @param string $mode режим перемещения
		 * @return $this
		 */
		public function move(\iUmiCollectionItem $baseEntity, $mode);
	}
