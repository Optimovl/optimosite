<?php
	namespace UmiCms\System\Trade\Offer\Data\Object;

	use \iUmiObject as iObject;
	use UmiCms\System\Trade\iOffer;
	use \iUmiObjectsCollection as iCollection;
	use UmiCms\System\Trade\Offer\Data\Object\Type\iFacade as iTypeFacade;

	/**
	 * Класс фасада объектов данных торговых предложений
	 * @package UmiCms\System\Trade\Offer\Data\Object
	 */
	class Facade implements iFacade {

		/** @var iCollection $collection коллекция объектов данных */
		private $collection;

		/** @var iTypeFacade $typeFacade фасад типов */
		private $typeFacade;

		/** @inheritdoc */
		public function __construct(iCollection $collection, iTypeFacade $typeFacade) {
			$this->setCollection($collection)
				->setTypeFacade($typeFacade);
		}

		/** @inheritdoc */
		public function get($id) {
			$object = $this->getCollection()
				->getById($id);

			if (!$object instanceof iObject) {
				return null;
			}

			return $this->initObject($object);
		}

		/** @inheritdoc */
		public function getList(array $idList) {
			$list = $this->getCollection()
				->getObjectList($idList);

			foreach ($list as $object) {
				$this->initObject($object);
			}

			return $list;
		}

		/** @inheritdoc */
		public function create($name, $typeId = null) {
			$objectTypeFacade = $this->getTypeFacade();
			$typeId = $typeId ?: $objectTypeFacade->getRootType()->getId();
			$this->validateType($typeId);

			$collection = $this->getCollection();
			$id = $collection->addObject($name, $typeId);
			$object = $collection->getById($id);

			if (!$object instanceof iObject) {
				throw new \ErrorException('Cannot create trade offer data object');
			}

			return $this->setObjectGuid($object)
				->setSavingInDestructor(false);
		}

		/** @inheritdoc */
		public function createByOffer(iOffer $offer) {
			return $this->create($offer->getName(), $offer->getTypeId());
		}

		/** @inheritdoc */
		public function save(iObject $object) {
			$this->validateType($object->getTypeId());
			$object->commit();
			return $this;
		}

		/** @inheritdoc */
		public function delete($id) {
			$collection = $this->getCollection();
			$object = $collection->getById($id);

			if (!$object instanceof iObject) {
				return $this;
			}

			$this->validateType($object->getTypeId());
			$collection->delObject($id);
			return $this;
		}

		/** @inheritdoc */
		public function copy(iObject $source) {
			$this->validateType($source->getTypeId());

			$copyId = $this->getCollection()
				->cloneObject($source->getId());
			$copy = $this->get($copyId);

			if (!$copy instanceof iObject) {
				throw new \ErrorException('Cannot copy trade offer data object');
			}

			return $this->setObjectGuid($copy);
		}

		/**
		 * Устанавливает гуид объекту
		 * @param iObject $object объект
		 * @return iObject
		 * @throws \coreException
		 */
		private function setObjectGuid(iObject $object) {
			$object->setGUID(sprintf('trade_offer_data_object_%d', $object->getId()));
			$object->commit();
			return $object;
		}

		/**
		 * Валидирует тип с идентификатором
		 * @param int $id идентификатор
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		private function validateType($id) {
			if (!$this->getTypeFacade()->isValid($id)) {
				$message = sprintf('Invalid trade offer data object type (%s) given', $id);
				throw new \ErrorException($message);
			}
		}

		/**
		 * Инициализирует объект
		 * @param iObject $object
		 * @return iObject
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		private function initObject(iObject $object) {
			$this->validateType($object->getTypeId());
			return $object->setSavingInDestructor(false);
		}

		/**
		 * Устанавливает коллекцию объектов данных
		 * @param iCollection $collection коллекция
		 * @return $this
		 */
		private function setCollection(iCollection $collection) {
			$this->collection = $collection;
			return $this;
		}

		/**
		 * Возвращает коллекция объектов данных
		 * @return iCollection
		 */
		private function getCollection() {
			return $this->collection;
		}

		/**
		 * Устанавливает фасад типов
		 * @param iTypeFacade $objectTypeFacade фасад типов
		 * @return $this
		 */
		private function setTypeFacade(iTypeFacade $objectTypeFacade) {
			$this->typeFacade = $objectTypeFacade;
			return $this;
		}

		/**
		 * Возвращает фасад типов
		 * @return iTypeFacade
		 */
		private function getTypeFacade() {
			return $this->typeFacade;
		}
	}