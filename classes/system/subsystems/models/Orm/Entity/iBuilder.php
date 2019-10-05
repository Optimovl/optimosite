<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;

	/**
	 * Интерфейс строителя сущности
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iBuilder {

		/**
		 * Конструктор
		 * @param iMapper $mapper маппер сущности
		 * @param \iServiceContainer $serviceContainer контейнер сервисов
		 * @param iMutator $relationMutator мутатор связей сущности
		 * @param iAccessor $attributeAccessor аксессор атрибутов сущности
		 * @param iMutator $attributeMutator мутатор атрибутов сущности
		 */
		public function __construct(
			iMapper $mapper,
			\iServiceContainer $serviceContainer,
			iMutator $relationMutator,
			iAccessor $attributeAccessor,
			iMutator $attributeMutator
		);

		/**
		 * Устанавливает все связи сущности
		 * @param iEntity $entity сущность
		 * @return iEntity
		 * @throws \Exception
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function buildAllRelations(iEntity $entity);

		/**
		 * Устанавливает все атрибуты сущности
		 * @param iEntity $entity сущность
		 * @param array $attributeList список атрибутов
		 * @example
		 *
		 * [
		 *		'id' => 123
		 * ]
		 *
		 * @return iEntity
		 * @throws \ErrorException
		 */
		public function buildAttributesList(iEntity $entity, array $attributeList);

		/**
		 * Устанавливает все связи для коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @return iCollection
		 * @throws \Exception
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function buildAllRelationsForCollection(iCollection $collection);

		/**
		 * Устанавливает все аттрибуты для списка сущностей
		 * @param array $entityList список сущностей
		 * @example
		 *
		 * [
		 *		1 => iEntity
		 * ]
		 *
		 * @param array $attributeListSet комплект списков атрибутов
		 * @example
		 *
		 * [
		 *		1 => [
		 * 			'id' => 123
		 * 		]
		 * ]
		 *
		 * @return array
		 * @throws \ErrorException
		 */
		public function buildAttributesListForMany(array $entityList, array $attributeListSet);

		/**
		 * Устанавливает выбранную связь сущности
		 * @param iEntity $entity сущность
		 * @param string $relation имя связи
		 * @return iEntity
		 * @throws \Exception
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function buildOneRelation(iEntity $entity, $relation);

		/**
		 * Устанавливает атрибут сущности
		 * @param iEntity $entity сущность
		 * @param string $attribute имя атрибута
		 * @param mixed $value значение атрибута
		 * @return iEntity
		 * @throws \ErrorException
		 */
		public function buildOneAttribute(iEntity $entity, $attribute, $value);

		/**
		 * Устанавливает выбранную связь коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @param string $relation имя связи
		 * @return iCollection
		 * @throws \Exception
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function buildOneRelationForCollection(iCollection $collection, $relation);

		/**
		 * Устанавливает выбранные связи коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @param string[] $relationList список имен связей
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function buildRelationListForCollection(iCollection $collection, array $relationList);
	}