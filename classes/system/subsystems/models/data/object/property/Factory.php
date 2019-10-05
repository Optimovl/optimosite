<?php

	namespace UmiCms\System\Data\Object\Property;

	/**
	 * Фабрика значений полей объектов
	 * @package UmiCms\System\Data\Object\Property
	 */
	class Factory implements iFactory {

		/** @var array $classMap таблица соотвествий между типами данных поля и классами значенией полей */
		private $classMap = [
			'relation' => 'umiObjectPropertyRelation',
			'wysiwyg' => 'umiObjectPropertyWYSIWYG',
			'string' => 'umiObjectPropertyString',
			'file' => 'umiObjectPropertyFile',
			'img_file' => 'umiObjectPropertyImgFile',
			'swf_file' => 'umiObjectPropertyImgFile',
			'video_file' => 'umiObjectPropertyFile',
			'boolean' => 'umiObjectPropertyBoolean',
			'int' => 'umiObjectPropertyInt',
			'text' => 'umiObjectPropertyText',
			'date' => 'umiObjectPropertyDate',
			'symlink' => 'umiObjectPropertySymlink',
			'price' => 'umiObjectPropertyPrice',
			'float' => 'umiObjectPropertyFloat',
			'tags' => 'umiObjectPropertyTags',
			'password' => 'umiObjectPropertyPassword',
			'counter' => 'umiObjectPropertyCounter',
			'optioned' => 'umiObjectPropertyOptioned',
			'color' => 'umiObjectPropertyColor',
			'link_to_object_type' => 'umiObjectPropertyLinkToObjectType',
			'multiple_image' => 'umiObjectPropertyMultipleImgFile',
			'domain_id' => 'UmiCms\System\Data\Object\Property\Value\DomainId',
			'domain_id_list' => 'UmiCms\System\Data\Object\Property\Value\DomainIdList',
			'offer_id_list' => 'UmiCms\System\Data\Object\Property\Value\OfferIdList',
			'offer_id' => 'UmiCms\System\Data\Object\Property\Value\OfferId'
		];

		/** @var \iUmiFieldsCollection $fieldsCollection коллекция полей */
		private $fieldsCollection;

		/** @var \iUmiObjectsCollection $objectsCollection коллекция объектов */
		private $objectsCollection;

		/**
		 * Конструктор
		 * @param \iUmiFieldsCollection $fieldsCollection коллекция полей
		 * @param \iUmiObjectsCollection $objectsCollection коллекция объектов
		 */
		public function __construct(\iUmiFieldsCollection $fieldsCollection, \iUmiObjectsCollection $objectsCollection) {
			$this->fieldsCollection = $fieldsCollection;
			$this->objectsCollection = $objectsCollection;
		}

		/** @inheritdoc */
		public function create($objectId, $fieldId) {
			$objectCollection = $this->objectsCollection;

			if (!$objectCollection->isLoaded($objectId) && !$objectCollection->isExists($objectId)) {
				throw new \coreException(sprintf('umiObject not found by id "%s"', $objectId));
			}

			$field = $this->fieldsCollection->getById($fieldId);

			if (!$field instanceof \iUmiField) {
				throw new \coreException(sprintf('Cannot get umiField by id "%s"', $fieldId));
			}

			$className = $this->getClassName($field->getDataType());

			try {
				return new $className($objectId, $fieldId);
			} catch (\privateException $exception) {
				$exception->unregister();
				throw new \coreException($exception->getMessage(), $exception->getCode(), $exception->getStrCode());
			}
		}

		/**
		 * Возвращает имя класса значения поля объекта
		 * @param string $dataType тип данных поля
		 * @return string
		 * @throws \coreException
		 */
		private function getClassName($dataType) {
			if (isset($this->classMap[$dataType])) {
				return $this->classMap[$dataType];
			}

			throw new \coreException(sprintf('Unknown field data type "%s"', $dataType));
		}
	}
