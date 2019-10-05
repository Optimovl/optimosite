<?php

	namespace UmiCms\System\Data\Object\Type\Hierarchy\Relation;

	use UmiCms\System\Data\Object\Type\Hierarchy\Relation;

	/**
	 * Класс фабрики иерахических связей между объектными типами данных
	 * @package UmiCms\System\Data\Object\Type\Relation
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create(array $data) {
			return new Relation($data);
		}
	}
