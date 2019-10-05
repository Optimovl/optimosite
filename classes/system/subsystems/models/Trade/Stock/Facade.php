<?php
	namespace UmiCms\System\Trade\Stock;

	use UmiCms\System\Trade\iStock;
	use \iUmiObject as iDataObject;
	use \iUmiObjectsCollection as iDataObjectCollection;
	use \iUmiObjectTypesCollection as iDataObjectTypeCollection;

	/**
	 * Класс фасада складов
	 * @package UmiCms\System\Trade\Stock
	 */
	class Facade implements iFacade {

		/** @var iFactory $factory фабрика складов */
		private $factory;

		/** @var iDataObjectCollection $objectCollection коллекция объектов данных */
		private $objectCollection;

		/** @var iDataObjectTypeCollection $objectTypeCollection коллекция типов объектов данных */
		private $objectTypeCollection;

		/** @inheritdoc */
		public function __construct(iFactory $factory, iDataObjectCollection $objectCollection, iDataObjectTypeCollection $typeCollection) {
			$this->setFactory($factory)
				->setObjectCollection($objectCollection)
				->setObjectTypeCollection($typeCollection);
		}

		/** @inheritdoc */
		public function get($id) {
			$dataObject = $this->getObjectCollection()
				->getById($id);

			if (!$dataObject instanceof iDataObject) {
				return null;
			}

			return $this->getFactory()
				->create($dataObject);
		}

		/** @inheritdoc */
		public function getList() {
			$storeList = [];
			$typeId = $this->getTypeId();
			$factory = $this->getFactory();

			foreach ($this->getObjectCollection()->getListByType($typeId) as $dataObject) {
				$storeList[] = $factory->create($dataObject);
			}

			return $storeList;
		}

		/** @inheritdoc */
		public function create($name) {
			if (!is_string($name) || isEmptyString($name)) {
				throw new \ErrorException('Incorrect stock name given');
			}

			$objectCollection = $this->getObjectCollection();
			$dataObjectId = $objectCollection->addObject($name, $this->getTypeId());
			$dataObject = $objectCollection->getById($dataObjectId);

			if (!$dataObject instanceof iDataObject) {
				throw new \ErrorException('Cannot get create stock data object');
			}

			$dataObject->setGUID(sprintf('trade_stock_data_object_%d', $dataObject->getId()));
			$dataObject->commit();
			return $this->getFactory()
				->create($dataObject);
		}

		/** @inheritdoc */
		public function save(iStock $stock) {
			$stock->getDataObject()
				->commit();
			return $this;
		}

		/** @inheritdoc */
		public function delete($id) {
			$collection = $this->getObjectCollection();
			$object = $collection->getById($id);

			if (!$object instanceof iDataObject) {
				return $this;
			}

			$stock = $this->getFactory()
				->create($object);
			$collection->delObject($stock->getDataObject()->getId());
			return $this;
		}

		/**
		 * Возвращает идентификатор типа склада
		 * @return bool|int
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		private function getTypeId() {
			$id = $this->getObjectTypeCollection()
				->getTypeIdByGUID(iFactory::TYPE_GUID);

			if (!is_numeric($id)) {
				throw new \ErrorException('Cannot get stock data object type');
			}

			return $id;
		}

		/**
		 * Устанавливает фабрику складов
		 * @param iFactory $factory фабрика складов
		 * @return $this
		 */
		private function setFactory(iFactory $factory) {
			$this->factory = $factory;
			return $this;
		}

		/**
		 * Возвращает фабрику складов
		 * @return iFactory
		 */
		private function getFactory() {
			return $this->factory;
		}

		/**
		 * Устанавливает коллекцию объектов данных
		 * @param iDataObjectCollection $collection коллекция объектов данных
 		 * @return $this
		 */
		private function setObjectCollection(iDataObjectCollection $collection) {
			$this->objectCollection = $collection;
			return $this;
		}

		/**
		 * Возвращает коллекцию объектов данных
		 * @return iDataObjectCollection
		 */
		private function getObjectCollection() {
			return $this->objectCollection;
		}

		/**
		 * Устанавливает коллекцию типов объектов данных
		 * @param iDataObjectTypeCollection $collection коллекция типов объектов данных
		 * @return $this
		 */
		private function setObjectTypeCollection(iDataObjectTypeCollection $collection) {
			$this->objectTypeCollection = $collection;
			return $this;
		}

		/**
		 * Возвращает коллекцию типов объектов данных
		 * @return iDataObjectTypeCollection
		 */
		private function getObjectTypeCollection() {
			return $this->objectTypeCollection;
		}
	}