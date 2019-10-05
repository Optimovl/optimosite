<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\Mapper\tInjector as tMapperInjector;
	use UmiCms\System\Orm\Entity\Relation\Mutator\tInjector as tRelationMutatorInjector;
	use UmiCms\System\Orm\Entity\Attribute\Mutator\tInjector as tAttributeMutatorInjector;
	use UmiCms\System\Orm\Entity\Attribute\Accessor\tInjector as tAttributeAccessorInjector;

	/**
	 * Абстрактный класс строителя сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Builder implements iBuilder {

		use tMapperInjector;
		use tRelationMutatorInjector;
		use tAttributeMutatorInjector;
		use tAttributeAccessorInjector;

		/** @var \ServiceContainer $serviceContainer контейнер сервисов */
		private $serviceContainer;

		/** @inheritdoc */
		public function __construct(
			iMapper $mapper,
			\iServiceContainer $serviceContainer,
			iMutator $relationMutator,
			iAccessor $attributeAccessor,
			iMutator $attributeMutator
		) {
			$this->setMapper($mapper)
				->setServiceContainer($serviceContainer)
				->setRelationMutator($relationMutator)
				->setAttributeAccessor($attributeAccessor)
				->setAttributeMutator($attributeMutator);
		}

		/** @inheritdoc */
		public function buildAllRelations(iEntity $entity) {

			foreach ($this->getMapper()->getRelationSchemaList() as $relation => $schema) {
				$this->buildRelation($entity, $relation, $schema);
			}

			return $entity;
		}

		/** @inheritdoc */
		public function buildAttributesList(iEntity $entity, array $attributeList) {
			return $this->getAttributeMutator()
				->mutateList($entity, $attributeList);
		}

		/** @inheritdoc */
		public function buildAllRelationsForCollection(iCollection $collection) {

			if ($collection->getCount() === 0) {
				return $collection;
			}

			foreach ($this->getMapper()->getRelationSchemaList() as $relation => $schema) {
				$this->buildRelationForCollection($collection, $relation, $schema);
			}

			return $collection;
		}

		/** @inheritdoc */
		public function buildAttributesListForMany(array $entityList, array $attributeListSet) {

			$attributeMutator = $this->getAttributeMutator();

			foreach ($attributeListSet as $index => $attributeList) {
				if (!$entityList[$index] || !$entityList[$index] instanceof iEntity) {
					throw new \ErrorException('Incorrect arguments given');
				}

				/** @var iEntity $entity */
				$entity = $entityList[$index];
				$attributeMutator->mutateList($entity, $attributeList);
			}

			return $entityList;
		}

		/** @inheritdoc */
		public function buildOneRelation(iEntity $entity, $relation) {
			$schema = $this->getMapper()->getRelationSchema($relation);
			return $this->buildRelation($entity, $relation, $schema);
		}

		/** @inheritdoc */
		public function buildOneAttribute(iEntity $entity, $attribute, $value) {
			return $this->getAttributeMutator()
				->mutate($entity, $attribute, $value);
		}

		/** @inheritdoc */
		public function buildOneRelationForCollection(iCollection $collection, $relation) {

			if ($collection->getCount() === 0) {
				return $collection;
			}

			$schema = $this->getMapper()->getRelationSchema($relation);
			return $this->buildRelationForCollection($collection, $relation, $schema);
		}

		/** @inheritdoc */
		public function buildRelationListForCollection(iCollection $collection, array $relationList) {

			if ($collection->getCount() === 0) {
				return $collection;
			}

			foreach ($relationList as $relation) {
				$this->buildOneRelationForCollection($collection, $relation);
			}

			return $collection;
		}

		/**
		 * Устанавливает связь коллекции сущностей
		 * @param iCollection $collection коллекции сущностей
		 * @param string $relation имя зависимости
		 * @param array $schema схема инициализации зависимости
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		protected function buildRelationForCollection(iCollection $collection, $relation,  array $schema) {
			list($idField, $serviceName, $type) = $schema;
			$idList = $collection->extractField($idField);
			$service = $this->getServiceContainer()->get($serviceName);
			$reflection = new \ReflectionClass($collection->getFirst());
			$referredIdField = $this->getReferredIdField($reflection);
			$relationMutator = $this->getRelationMutator();
			$attributeAccessor = $this->getAttributeAccessor();
			$ignoredRelationList = [
				mb_convert_case($reflection->getShortName(), MB_CASE_LOWER)
			];

			switch ($type) {
				case iMapper::ONE_ID_TO_ONE: {
					if ($service instanceof iFacade) {
						$dependencyCollection = $service->getCollectionByIdList($idList);
						$service->loadRelations($dependencyCollection, $ignoredRelationList);
						/** @var iEntity[] $dependencyList */
						$dependencyList = $dependencyCollection->getList();
					} else {
						/** @var \iUmiEntinty[] $dependencyList */
						$dependencyList = $service->getList($idList);
					}

					foreach ($collection as $entity) {
						foreach ($dependencyList as $dependency) {
							if ($attributeAccessor->accessOne($entity, $idField) != $dependency->getId()) {
								continue;
							}

							$relationMutator->mutate($entity, $relation, $dependency);
						}
					}

					break;
				}
				case iMapper::ONE_ID_TO_COLLECTION: {
					/** @var iFacade $service */
					$dependencyCollection = $service->getCollectionByValueList($referredIdField, $idList);

					if ($service instanceof iFacade) {
						$service->loadRelations($dependencyCollection, $ignoredRelationList);
					}

					foreach ($collection as $entity) {
						/** @var iCollection $dependencySubCollection */
						$dependencySubCollection = $dependencyCollection->copy()->filter([
							$referredIdField => [
								iCollection::COMPARE_TYPE_EQUALS => $entity->getId()
							]
						]);

						$relationMutator->mutate($entity, $relation, $dependencySubCollection);
					}

					break;
				}
				case iMapper::ONE_ENTITY_TO_COLLECTION: {
					/** @var iFacade $service */
					$method = sprintf('getCollectionBy%sCollection', $reflection->getShortName());
					/** @var iCollection $dependencyCollection */
					$dependencyCollection = $service->$method($collection);

					if ($service instanceof iFacade) {
						$service->loadRelations($dependencyCollection, $ignoredRelationList);
					}

					foreach ($collection as $entity) {
						/** @var iCollection $dependencySubCollection */
						$dependencySubCollection = $dependencyCollection->copy()->filter([
							$idField => [
								iCollection::COMPARE_TYPE_EQUALS => $attributeAccessor->accessOne($entity, $idField)
							]
						]);

						$relationMutator->mutate($entity, $relation, $dependencySubCollection);
					}
				}
			}

			return $collection;
		}

		/**
		 * Возвращает имя поля зависимой сущности, ссылающегося на сущность
		 * @param \ReflectionClass $reflection отражение сущности
		 * @return string
		 */
		private function getReferredIdField(\ReflectionClass $reflection) {
			$shortName = mb_convert_case($reflection->getShortName(), MB_CASE_LOWER);
			return sprintf('%s_id', $shortName);
		}

		/**
		 * Устанавливает связь сущности
		 * @param iEntity $entity сущность
		 * @param string $relation имя связи
		 * @param array $schema схема инициализации зависимости
		 * @return iEntity
		 * @throws \Exception
		 * @throws \ErrorException
		 */
		protected function buildRelation(iEntity $entity, $relation, array $schema) {
			list($idField, $serviceName, $type) = $schema;
			$id = $this->getAttributeAccessor()
				->accessOne($entity, $idField);
			$dependency = $this->getDependency($id, $serviceName, $type, $entity);

			if ($dependency === null) {
				return $entity;
			}

			return $this->getRelationMutator()
				->mutate($entity, $relation, $dependency);
		}


		/**
		 * Возвращает зависимую сущность
		 * @param int $id идентификатор для получение зависимой сущности
		 * @param string $serviceName имя сервиса для получения зависимой сущности
		 * @param string $type тип зависимости
		 * @param iEntity $entity родительская сущность
		 * @return null|object
		 * @throws \Exception
		 */
		protected function getDependency($id, $serviceName, $type, iEntity $entity) {
			$dependency = null;
			$service = $this->getServiceContainer()->get($serviceName);
			$reflection = new \ReflectionClass($entity);
			$referredIdField = $this->getReferredIdField($reflection);

			switch ($type) {
				case iMapper::ONE_ID_TO_ONE: {
					$dependency = $service->get($id);
					break;
				}
				case iMapper::ONE_ID_TO_COLLECTION: {
					$dependency = $service->getCollectionBy($referredIdField, $id);
					break;
				}
				case iMapper::ONE_ENTITY_TO_COLLECTION: {
					$method = sprintf('getCollectionBy%s', $reflection->getShortName());
					$dependency = $service->$method($entity);
					break;
				}
			}

			return $dependency;
		}

		/**
		 * Устанавливает контейнер сервисов
		 * @param \iServiceContainer $container контейнер сервисов
		 * @return $this
		 */
		protected function setServiceContainer(\iServiceContainer $container) {
			$this->serviceContainer = $container;
			return $this;
		}

		/**
		 * Возвращает контейнер сервисов
		 * @return \ServiceContainer
		 */
		protected function getServiceContainer() {
			return $this->serviceContainer;
		}
	}