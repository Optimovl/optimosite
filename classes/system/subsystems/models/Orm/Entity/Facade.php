<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\Factory\tInjector as tFactoryInjector;
	use UmiCms\System\Orm\Entity\Builder\tInjector as tBuilderInjector;
	use UmiCms\System\Orm\Entity\Collection\tInjector as tCollectionInjector;
	use UmiCms\System\Orm\Entity\Repository\tInjector as tRepositoryInjector;
	use UmiCms\System\Orm\Entity\Relation\Accessor\tInjector as tRelationAccessorInjector;
	use UmiCms\System\Orm\Entity\Attribute\Accessor\tInjector as tAttributeAccessorInjector;

	/**
	 * Абстрактный класс фасада сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Facade implements iFacade {

		use tFactoryInjector;
		use tBuilderInjector;
		use tCollectionInjector;
		use tRepositoryInjector;
		use tRelationAccessorInjector;
		use tAttributeAccessorInjector;

		/** @inheritdoc */
		public function __construct(
			iCollection $collection,
			iRepository $repository,
			iFactory $factory,
			iAccessor $attributeAccessor,
			iAccessor $relationAccessor,
			iBuilder $builder
		) {
			$this->setCollection($collection)
				->setRepository($repository)
				->setFactory($factory)
				->setAttributeAccessor($attributeAccessor)
				->setRelationAccessor($relationAccessor)
				->setBuilder($builder);
		}

		/** @inheritdoc */
		public function get($id) {
			$collection = $this->getCollection();
			$entity = $collection->get($id);

			if ($this->isValidEntity($entity)) {
				return $entity;
			}

			$entity = $this->getRepository()
				->get($id);

			if (!$this->isValidEntity($entity)) {
				return null;
			}

			$collection->push($entity);
			return $entity;
		}

		/** @inheritdoc */
		public function getAll() {
			$repository = $this->getRepository();
			$collection = $this->getCollection();

			if ($repository->getHistory()->isGetAllLogged()) {
				return $collection;
			}

			$entityList = $repository->getAll();
			return $collection->pushList($entityList);
		}

		/** @inheritdoc */
		public function getList(array $idList) {
			return $this->getListByValueList(iMapper::ID, $idList);
		}

		/** @inheritdoc */
		public function extractAttributeList(iEntity $entity) {
			return $this->getAttributeAccessor()
				->accessOneToAll($entity);
		}

		/** @inheritdoc */
		public function extractRelationList(iEntity $entity) {
			return $this->getRelationAccessor()
				->accessOneToAll($entity);
		}

		/** @inheritdoc */
		public function extractPropertyList(iEntity $entity) {
			return array_merge($this->extractAttributeList($entity), $this->extractRelationList($entity));
		}

		/** @inheritdoc */
		public function loadRelations(iCollection $collection, array $ignoredRelationList = []) {
			$relationList = $this->getRelationAccessor()
				->getPropertyList();
			$relationToLoad = array_diff($relationList, $ignoredRelationList);
			$this->getBuilder()
				->buildRelationListForCollection($collection, $relationToLoad);
			return $this;
		}

		/** @inheritdoc */
		public function importToEntity(iEntity $entity, array $attributeList) {
			$this->getBuilder()
				->buildAttributesList($entity, $attributeList);
			$this->save($entity);
			return $this;
		}

		/** @inheritdoc */
		public function copy(iEntity $source) {
			$attributeList = $this->extractAttributeList($source);
			unset($attributeList[iMapper::ID]);
			return $this->create($attributeList);
		}

		/** @inheritdoc */
		public function getCollectionBy($name, $value) {
			$collection = clone $this->getCollection();
			$collection->pushList($this->getListBy($name, $value));
			return $collection;
		}

		/** @inheritdoc */
		public function getCollectionByValueList($name, array $valueList) {
			$collection = clone $this->getCollection();
			$collection->pushList($this->getListByValueList($name, $valueList));
			return $collection;
		}

		/** @inheritdoc */
		public function getCollectionByIdList(array $idList) {
			return $this->getCollectionByValueList(iMapper::ID, $idList);
		}

		/** @inheritdoc */
		public function mapCollection(array $entityList) {
			$collection = clone $this->getCollection();

			foreach ($entityList as $entity) {
				if ($this->isValidEntity($entity)) {
					$collection->push($entity);
				}
			}

			return $collection;
		}

		/** @inheritdoc */
		public function mapCollectionWithRelations(array $entityList) {
			$collection = $this->mapCollection($entityList);
			$this->loadRelations($collection);
			return $collection;
		}

		/** @inheritdoc */
		public function create(array $attributeList = []) {
			$entity = $this->getFactory()
				->create();

			$this->getBuilder()
				->buildAttributesList($entity, $attributeList);

			$this->getRepository()
				->save($entity);

			$this->getCollection()
				->push($entity);

			return $entity;
		}

		/** @inheritdoc */
		public function save(iEntity $entity) {
			if (!$this->isValidEntity($entity)) {
				throw new \ErrorException('Incorrect entity given');
			}

			$this->getRepository()
				->save($entity);

			return $this;
		}

		/** @inheritdoc */
		public function delete($id) {

			$this->getRepository()
				->delete($id);

			$this->getCollection()
				->pull($id);

			return $this;
		}

		/** @inheritdoc */
		public function deleteList(array $idList) {

			$this->getRepository()
				->deleteList($idList);

			$this->getCollection()
				->pullList($idList);

			return $this;
		}

		/**
		 * Возвращает список сущностей со значением указанного поля из
		 * заданного списка.
		 * @param string $name имя поля
		 * @param array $valueList список значений
		 * @return iEntity[]
		 * @throws \ErrorException
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		protected function getListByValueList($name, array $valueList) {
			if (isEmptyArray($valueList)) {
				return [];
			}

			$repository = $this->getRepository();
			$collection = $this->getCollection();
			$entityList = [];

			if ($repository->getHistory()->isGettingLogged($name, $valueList)) {
				$entityList = $collection->getListBy($name, $valueList, iCollection::COMPARE_TYPE_IN_LIST);
			}

			if (isEmptyArray($entityList)) {
				$entityList = $repository->getListByValueList($name, $valueList);
				$collection->pushList($entityList);
			}

			return $entityList;
		}

		/**
		 * Возвращает список сущностей с заданным значением указанного поля
		 * @param string $name имя поля
		 * @param mixed $value значение
		 * @return iEntity[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		protected function getListBy($name, $value) {
			$repository = $this->getRepository();
			$collection = $this->getCollection();
			$entityList = [];

			if ($repository->getHistory()->isGettingLogged($name, $value)) {
				$entityList = $collection->getListBy($name, $value);
			}

			if (isEmptyArray($entityList)) {
				$entityList = $repository->getListBy($name, $value);
				$collection->pushList($entityList);
			}

			return $entityList;
		}

		/**
		 * Определяет валидность сущности
		 * @param mixed $entity сущность
		 * @return bool
		 */
		abstract protected function isValidEntity($entity);

		/**
		 * Определяет валиден ли режим перемещения
		 * @param string $mode режим
		 * @return bool
		 */
		protected function isValidMoveMode($mode) {
			if (!is_string($mode) || isEmptyString($mode)) {
				return false;
			}

			return in_array($mode, $this->getMoveModeList());
		}

		/**
		 * Возвращает список режимов перемещения
		 * @return string[]
		 */
		protected function getMoveModeList() {
			return [
				self::MOVE_MODE_BEFORE_ENTITY,
				self::MOVE_MODE_AFTER_ENTITY,
				self::MOVE_MODE_AS_ENTITY_CHILD
			];
		}
	}