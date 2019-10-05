<?php

	namespace UmiCms\System\Data\Field\Restriction;

	/**
	 * Интерфейс коллекции ограничений полей
	 * @package UmiCms\System\Data\Field\Restriction
	 */
	interface iCollection {

		/**
		 * Конструктор
		 * @param \IConnection $connection подключение к бд
		 */
		public function __construct(\IConnection $connection);

		/**
		 * Удаляет ограничение
		 * @param int $id идентификатор ограничения
		 * @return $this
		 */
		public function delete($id);

		/**
		 * Возвращает первое ограничение с заданным префиксом класса
		 * @param string $prefix префикс класса ограничения
		 * @return \baseRestriction|null
		 */
		public function getFirstByPrefix($prefix);

		/**
		 * Возвращает список ограничений с заданным префиксом класса
		 * @param string $prefix префикс класса ограничения
		 * @return \baseRestriction[]
		 */
		public function getListByPrefix($prefix);
	}
