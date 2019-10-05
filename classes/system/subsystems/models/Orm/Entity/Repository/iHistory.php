<?php
	namespace UmiCms\System\Orm\Entity\Repository;

	/**
	 * Интерфейс истории репозитория сущностей
	 * @package UmiCms\System\Orm\Entity\Repository
	 */
	interface iHistory {

		/**
		 * Добавляет запись о создании сущности
		 * @param int $id идентификатор созданной сущности
		 * @return $this
		 */
		public function logCreate($id);

		/**
		 * Добавляет запись об обновлении сущности
		 * @param int $id идентификатор обновленной сущности
		 * @return $this
		 */
		public function logUpdate($id);

		/**
		 * Добавляет запись об удалении сущности
		 * @param int $id идентификатор удаленной сущности
		 * @return $this
		 */
		public function logDelete($id);

		/**
		 * Добавляет запись о запросе сущности(ей) по значению атрибута
		 * @param string $name имя атрибута
		 * @param mixed $value значение атрибута
		 * @return $this
		 */
		public function logGet($name, $value);

		/**
		 * Добавляет запись о запросе полного списка сущностей
		 * @param int $count размер списка
		 * @return $this
		 */
		public function logGetAll($count);

		/**
		 * Определяет существует ли запись о создании сущности
		 * @param int $id идентификатор созданной сущности
		 * @return bool
		 */
		public function isCreationLogged($id);

		/**
		 * Определяет существует ли запись об обновлении сущности
		 * @param int $id идентификатор обновленной сущности
		 * @return bool
		 */
		public function isUpdatingLogged($id);

		/**
		 * Определяет существует ли запись об удалении сущности
		 * @param int $id идентификатор удаленной сущности
		 * @return bool
		 */
		public function isDeletionLogged($id);

		/**
		 * Определяет существует ли запись о запросе сущности(ей) по значению атрибута
		 * @param string $name имя атрибута
		 * @param mixed $value значение атрибута
		 * @return bool
		 */
		public function isGettingLogged($name, $value);

		/**
		 * Определяет существует ли запись о запросе полного списка
		 * @return bool
		 */
		public function isGetAllLogged();

		/**
		 * Возвращает журнал записей о создании сущностей
		 * @return array
		 */
		public function readCreateLog();

		/**
		 * Возвращает журнал записей об обновлении сущностей
		 * @return array
		 */
		public function readUpdateLog();

		/**
		 * Возвращает журнал записей об удалении сущностей
		 * @return array
		 */
		public function readDeleteLog();

		/**
		 * Возвращает журнал записей о запросе сущности(ей) по значению атрибута
		 * @return array
		 */
		public function readGetLog();

		/**
		 * Возвращает журнал записей о запросе полного списка сущностей
		 * @return array
		 */
		public function readGetAllLog();

		/**
		 * Очищает историю
		 * @return $this
		 */
		public function clear();
	}