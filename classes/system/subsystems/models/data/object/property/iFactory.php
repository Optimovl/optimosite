<?php

	namespace UmiCms\System\Data\Object\Property;

	/**
	 * Интерфейс фабрики значений полей объектов
	 * @package UmiCms\System\Data\Object\Property
	 */
	interface iFactory {

		/**
		 * Создает экземпляр значения поля объекта
		 * @param int $objectId идентификатор объека
		 * @param int $fieldId идентификатор поля
		 * @return \iUmiObjectProperty
		 * @throws \coreException
		 */
		public function create($objectId, $fieldId);
	}
