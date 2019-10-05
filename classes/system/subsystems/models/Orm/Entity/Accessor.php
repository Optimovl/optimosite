<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\Mapper\tInjector as tMapperInjector;

	/**
	 * Абстрактный класс аксессора свойств сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Accessor implements iAccessor {

		use tMapperInjector;

		/** @inheritdoc */
		public function __construct(iMapper $mapper) {
			$this->setMapper($mapper);
		}

		/** @inheritdoc */
		public function accessOne(iEntity $entity, $name) {
			$schema = $this->getSchema($name);
			return $this->accessBySchema($entity, $schema);
		}

		/** @inheritdoc */
		public function accessMany(array $entityList, $name) {
			$result = [];

			foreach ($entityList as $entity) {
				$result[] = $this->accessOne($entity, $name);
			}

			return $result;
		}

		/** @inheritdoc */
		public function accessCollection(iCollection $collection, $name) {
			return $this->accessMany($collection->getList(), $name);
		}

		/** @inheritdoc */
		public function accessOneToMany(iEntity $entity, array $nameList) {
			$result = [];

			foreach ($nameList as $name) {
				$result[$name] = $this->accessOne($entity, $name);
			}

			return $result;
		}

		/** @inheritdoc */
		public function accessManyToMany(array $entityList, array $nameList) {
			$result = [];

			foreach ($entityList as $entity) {
				$result[] = $this->accessOneToMany($entity, $nameList);
			}

			return $result;
		}

		/** @inheritdoc */
		public function accessCollectionToMany(iCollection $collection, array $nameList) {
			return $this->accessManyToMany($collection->getList(), $nameList);
		}

		/** @inheritdoc */
		public function accessOneToAll(iEntity $entity) {
			$result = [];

			foreach ($this->getSchemaList() as $name => $schema) {
				$result[$name] = $this->accessBySchema($entity, $schema);
			}

			return $result;
		}

		/** @inheritdoc */
		public function accessManyToAll(array $entityList) {
			$result = [];

			foreach ($entityList as $entity) {
				$result[] = $this->accessOneToAll($entity);
			}

			return $result;
		}

		/** @inheritdoc */
		public function accessCollectionToAll(iCollection $collection) {
			return $this->accessManyToAll($collection->getList());
		}

		/** @inheritdoc */
		public function getPropertyList() {
			return array_keys($this->getSchemaList());
		}

		/**
		 * Возвращает схему доступа к свойству
		 * @param string $name имя свойства
		 * @return array
		 * @throws \ErrorException
		 */
		abstract protected function getSchema($name);

		/**
		 * Возвращает список схем доступа
		 * @return array
		 */
		abstract protected function getSchemaList();

		/**
		 * Возвращает значение свойства по его схеме доступа
		 * @param iEntity $entity сущность
		 * @param array $schema схема доступа атрибута
		 * @return mixed
		 */
		abstract protected function accessBySchema(iEntity $entity, array $schema);
	}