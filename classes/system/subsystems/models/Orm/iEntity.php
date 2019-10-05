<?php
	namespace UmiCms\System\Orm;

	/**
	 * Интерфейс сущности
	 * @package UmiCms\System\Orm
	 */
	interface iEntity {

		/**
		 * Возвращает идентификатор
		 * @return int|null
		 */
		public function getId();

		/**
		 * Устанавливает идентификатор
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setId($id);

		/**
		 * Определяет установлен ли идентификатор
		 * @return bool
		 */
		public function hasId();

		/**
		 * Определяет было ли решение обновлено
		 * @return bool
		 */
		public function isUpdated();

		/**
		 * Устанавливает было ли решения обновлено
		 * @param bool $flag значение
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setUpdated($flag = true);
	}