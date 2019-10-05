<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use \iUmiField as iField;
	use \iUmiObject as iObject;
	use \iUmiObjectType as iType;
	use UmiCms\System\Trade\iOffer;
	use \iUmiFieldsCollection as iFieldFacade;
	use UmiCms\System\Trade\Offer\iCharacteristic;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;
	use UmiCms\System\Trade\Offer\Data\Object\iFacade as iObjectFacade;
	use UmiCms\System\Trade\Offer\Data\Object\Type\iFacade as iTypeFacade;

	/**
	 * Класс фасада характеристик торговых предложений
	 * @package UmiCms\System\Trade\Offer\Characteristic
	 */
	class Facade implements iFacade {

		/** @var iMapper $mapper маппер характеристик торговых предложений */
		private $mapper;

		/** @var iFactory $factory фабрика характеристик торговых предложений */
		private $factory;

		/** @var iCollection $collection коллекция характеристик торговых предложений */
		private $collection;

		/** @var iObjectFacade $objectFacade фасад объектов данных торговых предложений */
		private $objectFacade;

		/** @var iTypeFacade $typeFacade фасад типов торговых предложений */
		private $typeFacade;

		/** @var iFieldFacade $fieldFacade фасад полей торговых предложений */
		private $fieldFacade;

		/** @inheritdoc */
		public function __construct(
			iMapper $mapper,
			iFactory $factory,
			iCollection $collection,
			iObjectFacade $objectFacade,
			iTypeFacade $typeFacade,
			iFieldFacade $fieldFacade
		) {
			$this->mapper = $mapper;
			$this->factory = $factory;
			$this->collection = $collection;
			$this->objectFacade = $objectFacade;
			$this->typeFacade = $typeFacade;
			$this->fieldFacade = $fieldFacade;
		}

		/** @inheritdoc */
		public function getCollectionByType($id) {
			$characteristicList = $this->createCharacteristicListByType($id);
			return $this->mapCollection($characteristicList);
		}

		/** @inheritdoc */
		public function getCollectionByOffer(iOffer $offer) {
			$characteristicList = $this->createCharacteristicListByOffer($offer);
			return $this->mapCollection($characteristicList);
		}

		/** @inheritdoc */
		public function getCollectionByOfferCollection(iOfferCollection $offerCollection) {

			$dataObjectIdList = $offerCollection->extractDataObjectId();
			// оптимизация количества запросов - предварительная загрузка всех объектов
			$this->getObjectFacade()->getList($dataObjectIdList);
			// оптимизация количества запросов - предварительная загрузка значений полей объектов
			\umiObjectProperty::loadPropsData($dataObjectIdList);

			$characteristicList = [];

			foreach ($offerCollection as $offer) {
				foreach ($this->createCharacteristicListByOffer($offer) as $characteristic) {
					$characteristicList[] = $characteristic;
				}
			}

			return $this->mapCollection($characteristicList);
		}

		/** @inheritdoc */
		public function createByOfferAndField(iOffer $offer, iField $field) {
			$characteristic = $this->getFactory()
				->create($field);

			if ($offer->hasDataObjectId()) {
				$dataObject = $this->getObject($offer->getDataObjectId());
			} else {
				$dataObject = $this->createObject($offer);
			}

			return $characteristic->setDataObject($dataObject);
		}

		/** @inheritdoc */
		public function createByOfferAndFieldName(iOffer $offer, $name) {
			$id = $this->getTypeFacade()
				->get($offer->getTypeId())
				->getFieldId($name);
			$field = $this->getFieldFacade()
				->getById($id);

			if (!$field instanceof iField) {
				throw new \ExpectFieldException(sprintf('Incorrect field name "%s" given.', $field));
			}

			return $this->createByOfferAndField($offer, $field);
		}

		/** @inheritdoc */
		public function mapCollection(array $characteristicList) {
			$collection = clone $this->getCollection();
			return $collection->pushList($characteristicList);
		}

		/**
		 * Создает список характеристик заданного типа
		 * @param int $id идентификатор типа
		 * @return iCharacteristic[]
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \coreException
		 * @throws \expectObjectTypeException
		 */
		private function createCharacteristicListByType($id) {
			$type = $this->getType($id);
			$fieldList = $this->getFieldList($type);
			return $this->getFactory()
				->createList($fieldList);
		}

		/**
		 * Создает список характеристик торгового предложения
		 * @param iOffer $offer предложение
		 * @return iCharacteristic[]
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \coreException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		private function createCharacteristicListByOffer(iOffer $offer) {

			if (!$offer->hasDataObjectId()) {
				return [];
			}

			$characteristicList = $this->createCharacteristicListByType($offer->getTypeId());

			foreach ($characteristicList as $characteristic) {
				$dataObject = $this->getObject($offer->getDataObjectId());
				$characteristic->setDataObject($dataObject);
			}

			return $characteristicList;
		}

		/**
		 * Возвращает тип торгового предложения
		 * @param int $id идентификатор типа
		 * @return iType
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \expectObjectTypeException
		 */
		private function getType($id) {
			$type = $this->getTypeFacade()
				->get($id);

			if (!$type instanceof iType) {
				throw new \expectObjectTypeException('Incorrect offer type id given');
			}

			return $type;
		}

		/**
		 * Возвращает объект данных торгового предложения
		 * @param int $id идентификатор объекта данных
		 * @return iObject
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \expectObjectException
		 */
		private function getObject($id) {
			$object = $this->getObjectFacade()
				->get($id);

			if (!$object instanceof iObject) {
				throw new \expectObjectException('Incorrect offer data object id given');
			}

			return $object;
		}

		/**
		 * Создает объект данных для торгового предложения
		 * @param iOffer $offer торговое предложение
		 * @return iObject
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		private function createObject(iOffer $offer) {
			$object = $this->getObjectFacade()
				->createByOffer($offer);
			$offer->setDataObjectId($object->getId());
			return $object;
		}

		/**
		 * Возвращает список полей типа торгового предложения
		 * @param iType $type тип торгового предложения
		 * @return \iUmiField[]
		 * @throws \ExpectFieldGroupException
		 */
		private function getFieldList(iType $type) {
			$fieldList = $type->getFieldListFromGroup(self::GROUP_NAME);
			$this->getFieldFacade()
				->filterListByNameBlackList($fieldList, [self::OFFER_LIST_FIELD_NAME])
				->filterListByTypeWhiteList($fieldList, self::FIELD_TYPE_WHITE_LIST);
			return $fieldList;
		}

		/**
		 * Возвращает маппер характеристик торговых предложений
		 * @return iMapper
		 */
		public function getMapper() {
			return $this->mapper;
		}

		/**
		 * Возвращает фабрику характеристик торговых предложений
		 * @return iFactory
		 */
		private function getFactory() {
			return $this->factory;
		}

		/**
		 * Возвращает коллекцию характеристик торговых предложений
		 * @return iCollection
		 */
		private function getCollection() {
			return $this->collection;
		}

		/**
		 * Возвращает фасад объектов данных торговых предложений
		 * @return iObjectFacade
		 */
		private function getObjectFacade() {
			return $this->objectFacade;
		}

		/**
		 * Возвращает фасад типов торговых предложений
		 * @return iTypeFacade
		 */
		private function getTypeFacade() {
			return $this->typeFacade;
		}

		/**
		 * Возвращает фасад полей торговых предложений
		 * @return iFieldFacade
		 */
		private function getFieldFacade() {
			return $this->fieldFacade;
		}
	}