<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;

	/**
	 * Интерфейс аксессора свойств сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iAccessor {

		/**
		 * Конструктор
		 * @param iMapper $mapper маппер сущности
		 */
		public function __construct(iMapper $mapper);

		/**
		 * Возвращает значение свойства сущности
		 * @param iEntity $entity сущность
		 * @param string $name имя свойства
		 * @return mixed
		 * @throws \ErrorException
		 */
		public function accessOne(iEntity $entity, $name);

		/**
		 * Возвращает значение свойства сущностей
		 * @param iEntity[] $entityList список сущностей
		 * @param string $name имя свойства
		 * @return array
		 * @throws \ErrorException
		 */
		public function accessMany(array $entityList, $name);

		/**
		 * Возвращает значение свойства коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @param string $name имя свойства
		 * @return array
		 * @throws \ErrorException
		 */
		public function accessCollection(iCollection $collection, $name);

		/**
		 * Возвращает значения списка свойств сущности
		 * @param iEntity $entity сущность
		 * @param string[] $nameList список имен свойств
		 * @return array
		 * @throws \ErrorException
		 */
		public function accessOneToMany(iEntity $entity, array $nameList);

		/**
		 * Возвращает значения списка свойств сущностей
		 * @param iEntity[] $entityList список сущностей
		 * @param string[] $nameList список имен свойств
		 * @return array
		 * @throws \ErrorException
		 */
		public function accessManyToMany(array $entityList, array $nameList);

		/**
		 * Возвращает значения списка свойств коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @param string[] $nameList список имен свойств
		 * @return array
		 * @throws \ErrorException
		 */
		public function accessCollectionToMany(iCollection $collection, array $nameList);

		/**
		 * Возвращает значения всех свойств сущности
		 * @param iEntity $entity сущность
		 * @return array
		 */
		public function accessOneToAll(iEntity $entity);

		/**
		 * Возвращает значения всех свойств сущностей
		 * @param iEntity[] $entityList список сущностей
		 * @return array
		 */
		public function accessManyToAll(array $entityList);

		/**
		 * Возвращает значения всех свойств коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @return array
		 */
		public function accessCollectionToAll(iCollection $collection);

		/**
		 * Возвращает список имен свойств
		 * @return string[]
		 */
		public function getPropertyList();
	}