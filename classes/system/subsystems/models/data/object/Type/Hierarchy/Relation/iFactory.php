<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy\Relation;

	use UmiCms\System\Data\Object\Type\Hierarchy\iRelation;

	/**
	 * Интерфейс фабрики иерахических связей между объектными типами данных
	 * @package UmiCms\System\Data\Object\Type\Relation
	 */
	interface iFactory {

		/**
		 * Создает иерархическую связь
		 * @param array $data данные связи
		 * @return iRelation
		 * @throws \RuntimeException
		 */
		public function create(array $data);
	}
