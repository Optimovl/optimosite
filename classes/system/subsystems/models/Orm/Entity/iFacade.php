<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;

	/**
	 * Интерфейс фасада сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iFacade {

		/** @var string MOVE_MODE_AFTER_ENTITY режим перетаскивания "После" */
		const MOVE_MODE_AFTER_ENTITY = 'after';

		/** @var string MOVE_MODE_BEFORE_ENTITY режим перетаскивания "До" */
		const MOVE_MODE_BEFORE_ENTITY = 'before';

		/** @var string MOVE_MODE_AS_ENTITY_CHILD режим перетаскивания "В" */
		const MOVE_MODE_AS_ENTITY_CHILD = 'child';

		/**
		 * Конструктор
		 * @param iCollection $collection коллекция сущностей
		 * @param iRepository $repository репозиторий сущностей
		 * @param iFactory $factory фабрика сущностей
		 * @param iAccessor $attributeAccessor аксессор атрибутов сущностей
		 * @param iAccessor $relationAccessor аксессор связей сущностей
		 * @param iBuilder $builder строитель атрибутов сущностей
		 */
		public function __construct(
			iCollection $collection,
			iRepository $repository,
			iFactory $factory,
			iAccessor $attributeAccessor,
			iAccessor $relationAccessor,
			iBuilder $builder
		);

		/**
		 * Возвращает сущность по ее идентификатору
		 * @param int $id идентификатор сущности
		 * @return iEntity|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function get($id);

		/**
		 * Возвращает коллекцию всех сущностей
		 * @return iCollection
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getAll();

		/**
		 * Возвращает список сущностей с заданными идентификаторами
		 * @param int[] $idList список идентификаторов
		 * @return iEntity[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getList(array $idList);

		/**
		 * Извлекает массив атрибутов сущности
		 * @param iEntity $entity сущность
		 * @return array
		 * @throws \ErrorException
		 */
		public function extractAttributeList(iEntity $entity);

		/**
		 * Извлекает массив связей сущности
		 * @param iEntity $entity сущность
		 * @return array
		 */
		public function extractRelationList(iEntity $entity);

		/**
		 * Извлекает массив свойств (атрибутов и связей) сущности
		 * @param iEntity $entity сущность
		 * @return array
		 * @throws \ErrorException
		 */
		public function extractPropertyList(iEntity $entity);

		/**
		 * Загружает связанные сущности для коллекции сущностей
		 * @param iCollection $collection коллекция сущностей
		 * @param string[] $ignoredRelationList игнорируемые связи
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function loadRelations(iCollection $collection, array $ignoredRelationList = []);

		/**
		 * Импортирует список атрибутов в сущность
		 * @param iEntity $entity сущность
		 * @param array $attributeList список атрибутов
		 * @return iEntity
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function importToEntity(iEntity $entity, array $attributeList);

		/**
		 * Копирует сущность
		 * @param iEntity $source копируемая сущность
		 * @return iEntity
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function copy(iEntity $source);

		/**
		 * Возвращает коллекцию сущностей с заданным значением указанного атрибута
		 * @param string $name имя поля
		 * @param mixed $value значение
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionBy($name, $value);

		/**
		 * Возвращает коллекцию сущностей со значением указанного атрибута из
		 * заданного списка
		 * @param string $name имя поля
		 * @param array $valueList список значений
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByValueList($name, array $valueList);

		/**
		 * Возвращает коллекцию сущностей с заданными идентификаторами
		 * @param array $idList список идентификаторов торговых предложений
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByIdList(array $idList);

		/**
		 * Формирует коллекцию сущностей
		 * @param array $entityList список сущностей
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function mapCollection(array $entityList);

		/**
		 * Формирует коллекцию сущностей с загрузкой связанных сущностей
		 * @param iEntity[] $entityList список сущностей
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function mapCollectionWithRelations(array $entityList);

		/**
		 * Создает сущность
		 * @param array $attributeList атрибуты сущности
		 * @return iEntity
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function create(array $attributeList = []);

		/**
		 * Сохраняет сущность
		 * @param iEntity $entity
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function save(iEntity $entity);

		/**
		 * Удаляет сущность с заданными идентификатором
		 * @param int $id идентификатор сушности
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 */
		public function delete($id);

		/**
		 * Удаляет список сущностей с заданными идентификаторами
		 * @param array $idList список идентификаторов
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function deleteList(array $idList);
	}