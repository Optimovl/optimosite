<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy;

	/**
	 * Интерфейс иерархической связи между объектными типа данных
	 * @package UmiCms\System\Data\Object\Type\Hierarchy
	 */
	interface iRelation {

		/**
		 * Конструктор
		 * @param array $data данные связи
		 * @throws \RuntimeException
		 */
		public function __construct(array $data);

		/**
		 * Возвращает идентификатор связи
		 * @return int
		 */
		public function getId();

		/**
		 * Возвращает идентификатор родительского объектного типа данных
		 * @return int
		 */
		public function getParentId();

		/**
		 * Устанавливает идентификатор родительского объектного типа данных
		 * @param int $id идентификатор типа данных
		 * @return $this
		 * @throws \RuntimeException
		 */
		public function setParentId($id);

		/**
		 * Возвращает идентификатор дочернего типа данных
		 * @return int
		 */
		public function getChildId();

		/**
		 * Устанавливает идентификатор дочернего типа данных
		 * @param int $id идентификатор типа данных
		 * @return $this
		 * @throws \RuntimeException
		 */
		public function setChildId($id);

		/**
		 * Возвращает уровень вложенности иерархии
		 * @return int
		 */
		public function getLevel();
	}
