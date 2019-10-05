<?php

	interface iUmiEntinty {

		/**
		 * Фильтрует значение перед записью в БД
		 * @param string $string значение
		 * @return string отфильтрованное значение
		 */
		public static function filterInputString($string);

		/**
		 * Возвращает уникальный идентификатор сущности
		 * @return int
		 */
		public function getId();

		/**
		 * Сохраняет измененную сущность и возвращает результат операции.
		 * @return bool
		 */
		public function commit();

		/**
		 * Обновляет данные сущности из БД. Внесенные изменения скорее всего будут утеряны.
		 * @return bool результат операции зависит от реализации loadInfo() в дочернем классе
		 */
		public function update();

		/**
		 * Определяет, была ли обновлена сущность
		 * @return bool
		 */
		public function getIsUpdated();

		/**
		 * Устанавливает была ли сущность изменена
		 * @param bool $isUpdated значение флага измененности
		 */
		public function setIsUpdated($isUpdated = true);

		/**
		 * Возвращает тип кешируемой сущности
		 * @return string
		 */
		public function getStoreType();

		/**
		 * Переводит строковую константу по ее ключу
		 * @param string $label ключ строковой константы
		 * @return string значение константы в текущей локали
		 */
		public function translateLabel($label);

		/**
		 * Устанавливает поддержку сохранения измененной сущности на этапе ее удаления из памяти
		 * @param bool $flag значение
		 * @return $this
		 */
		public function setSavingInDestructor($flag = true);
	}
