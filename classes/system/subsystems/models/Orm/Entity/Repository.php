<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Orm\Entity\Repository\iHistory;
	use UmiCms\System\Orm\Entity\Mapper\tInjector as tMapperInjector;
	use UmiCms\System\Orm\Entity\Schema\tInjector as tSchemaInjector;
	use UmiCms\System\Orm\Entity\Factory\tInjector as tFactoryInjector;
	use UmiCms\System\Orm\Entity\Builder\tInjector as tBuilderInjector;
	use UmiCms\System\Orm\Entity\Attribute\Accessor\tInjector as tAttributeAccessorInjector;

	/**
	 * Класс абстрактного репозитория сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Repository implements iRepository {

		use tMapperInjector;
		use tSchemaInjector;
		use tFactoryInjector;
		use tBuilderInjector;
		use tAttributeAccessorInjector;

		/** @var \IConnection $connection подключение к бд */
		private $connection;

		/** @var iHistory $history история репозитория */
		private $history;

		/** @inheritdoc */
		public function __construct(
			\IConnection $connection,
			iHistory $history,
			iSchema $schema,
			iAccessor $accessor,
			iFactory $factory,
			iBuilder $builder
		) {
			$this->setConnection($connection)
				->setHistory($history)
				->setSchema($schema)
				->setAttributeAccessor($accessor)
				->setFactory($factory)
				->setBuilder($builder);
		}

		/** @inheritdoc */
		public function get($id) {
			$table = $this->getTable();
			$id = (int) $id;
			$sql = <<<SQL
SELECT * FROM `$table` WHERE `id` = $id LIMIT 0,1;
SQL;
			$result = $this->getConnection()
				->queryResult($sql)
				->setFetchAssoc();
			$entity = $this->mapEntity($result);

			if ($entity instanceof iEntity) {
				$this->getHistory()
					->logGet(iMapper::ID, $entity->getId());
			}

			return $entity;
		}

		/** @inheritdoc */
		public function getAll() {
			$table = $this->getTable();
			$sql = <<<SQL
SELECT * FROM `$table`;
SQL;
			$result = $this->getConnection()
				->queryResult($sql)
				->setFetchAssoc();
			$entityList = $this->mapEntityList($result);
			$this->logGetAll($entityList);
			return $entityList;
		}

		/** @inheritdoc */
		public function getListByIdList(array $idList) {
			return $this->getListByValueList(iMapper::ID, $idList);
		}

		/** @inheritdoc */
		public function getListByValueList($name, array $valueList) {
			if (isEmptyArray($valueList)) {
				return [];
			}

			$table = $this->getTable();
			$valueListCondition = $this->glueValueListCondition($valueList);
			$sql = <<<SQL
SELECT * FROM `$table` WHERE `$name` IN $valueListCondition;
SQL;
			$result = $this->getConnection()
				->queryResult($sql)
				->setFetchAssoc();
			$entityList = $this->mapEntityList($result);

			if (count($entityList) > 0) {
				$this->getHistory()
					->logGet($name, $valueList);
			}

			return $entityList;
		}

		/** @inheritdoc */
		public function getListBy($name, $value) {
			$table = $this->getTable();
			$connection = $this->getConnection();
			$escapedName = $connection->escape($name);
			$escapedValue = $connection->escape($value);
			$sql = <<<SQL
SELECT * FROM `$table` WHERE `$escapedName` = '$escapedValue';
SQL;
			$result = $this->getConnection()
				->queryResult($sql)
				->setFetchAssoc();
			$entityList = $this->mapEntityList($result);

			if (!isEmptyArray($entityList)) {
				$this->getHistory()
					->logGet($name, $value);
			}

			return $entityList;
		}

		/** @inheritdoc */
		public function save(iEntity $entity) {
			if (!$this->isValidEntity($entity)) {
				throw new \ErrorException('Incorrect entity given');
			}

			if ($entity->hasId()) {
				$entity = $this->update($entity);
			} else {
				$entity = $this->create($entity);
			}

			return $entity->setUpdated(false);
		}

		/** @inheritdoc */
		public function delete($id) {
			if (!is_numeric($id)) {
				return $this;
			}

			$table = $this->getTable();
			$id = (int) $id;
			$sql = <<<SQL
DELETE FROM `$table` WHERE `id` = $id;
SQL;
			$connection = $this->getConnection();
			$connection->query($sql);

			if ($connection->affectedRows()) {
				$this->getHistory()
					->logDelete($id);
			}

			return $this;
		}

		/** @inheritdoc */
		public function deleteList(array $idList) {
			if (isEmptyArray($idList)) {
				return $this;
			}

			$table = $this->getTable();
			$idListCondition = $this->glueValueListCondition($idList);
			$sql = <<<SQL
DELETE FROM `$table` WHERE `id` IN $idListCondition;
SQL;
			$connection = $this->getConnection();
			$connection->query($sql);

			if ($connection->affectedRows()) {
				$history = $this->getHistory();
				foreach ($idList as $id) {
					$history->logDelete($id);
				}
			}

			return $this;
		}

		/** @inheritdoc */
		public function clear() {
			$table = $this->getTable();
			$sql = <<<SQL
DELETE FROM `$table`;
SQL;
			$this->getConnection()
				->query($sql);
			return $this;
		}

		/** @inheritdoc */
		public function getHistory() {
			return $this->history;
		}

		/**
		 * Возвращает имя таблицы
		 * @return string
		 * @throws \ReflectionException
		 */
		protected function getTable() {
			return $this->getSchema()->getContainerName();
		}

		/**
		 * Определяет валидность сущности
		 * @param mixed $entity сущность
		 * @return bool
		 * @throws \ErrorException
		 */
		abstract protected function isValidEntity($entity);

		/**
		 * Формирует сущность из результатов выборки
		 * @param \IQueryResult $queryResult результат выборки
		 * @return null|iEntity
		 * @throws \ErrorException
		 */
		protected function mapEntity(\IQueryResult $queryResult) {
			if ($queryResult->length() === 0) {
				return null;
			}

			$entity = $this->getFactory()
				->create();
			$attributeList = $queryResult->fetch();
			return $this->getBuilder()
				->buildAttributesList($entity, $attributeList);
		}

		/**
		 * Формирует список сущностей из результатов выборки
		 * @param \IQueryResult $queryResult результат выборки
		 * @return iEntity[]
		 * @throws \ErrorException
		 */
		protected function mapEntityList(\IQueryResult $queryResult) {
			$result = [];

			if ($queryResult->length() === 0) {
				return $result;
			}

			$factory = $this->getFactory();
			$builder = $this->getBuilder();

			foreach ($queryResult as $row) {
				$entity = $factory->create();
				$builder->buildAttributesList($entity, $row);
				$result[] = $entity;
			}

			return $result;
		}

		/**
		 * Обновляет строку сущности
		 * @param iEntity $entity сущность
		 * @return iEntity
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		protected function update(iEntity $entity) {
			if (!$entity->isUpdated()) {
				return $entity;
			}

			$table = $this->getTable();
			$condition = $this->getUpdateCondition($entity);
			$id = (int) $entity->getId();
			$sql = <<<SQL
UPDATE `$table` SET $condition WHERE `id` = $id;
SQL;
			$connection = $this->getConnection();
			$connection->query($sql);

			if ($connection->affectedRows()) {
				$this->getHistory()
					->logUpdate($id);
			}

			return $entity;
		}

		/**
		 * Возвращает часть sql выражения для обновления строки сущности
		 * @param iEntity $entity сущность
		 * @return string
		 */
		protected function getUpdateCondition(iEntity $entity) {
			$condition = '';

			foreach ($this->getEscapedRow($entity) as $index => $value) {
				$value = ($value === null) ? 'NULL' : "'$value'";
				$condition .= " `$index` = $value,";
			}

			return rtrim($condition, ',');
		}

		/**
		 * Возвращает экранированные данные сущности
		 * @param iEntity $entity сущность
		 * @return array
		 *
		 * [
		 *		'field' => escaped value
		 * ]
		 */
		protected function getEscapedRow(iEntity $entity) {
			$attributeAccessor = $this->getAttributeAccessor();
			$row = [];
			$connection = $this->getConnection();

			foreach ($attributeAccessor->accessOneToAll($entity) as $index => $value) {
				$row[$index] = ($value === null) ? $value : $connection->escape($value);
			}

			return $row;
		}

		/**
		 * Создает строку сущности
		 * @param iEntity $entity сущность
		 * @return iEntity
		 * @throws \ErrorException
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		protected function create(iEntity $entity) {
			$table = $this->getTable();
			$condition = $this->getInsertCondition($entity);
			$connection = $this->getConnection();
			$sql = <<<SQL
INSERT INTO `$table` $condition
SQL;
			$connection->query($sql);
			$id = $connection->insertId();

			$this->getHistory()
				->logCreate($id);

			return $entity->setId($id);
		}

		/**
		 * Возвращает часть sql выражения для вставки строки сущности
		 * @param iEntity $entity сущность
		 * @return string
		 */
		protected function getInsertCondition(iEntity $entity) {
			$escapedRow = $this->getEscapedRow($entity);
			$fieldList = array_keys($escapedRow);
			$condition = '(`' . implode('`, `', $fieldList) . '`)';

			$valueList = [];

			foreach ($escapedRow as $index => $value) {
				$valueList[] = ($value === null) ? 'NULL' : "'$value'";
			}

			return $condition . ' VALUES (' . implode(', ', $valueList) . ')';
		}

		/**
		 * Подготавливает список значений для вставки в sql запрос
		 *
		 * array(1, 2, 3, 4) => '1, 2, 3, 4';
		 *
		 * @param array $idList список идентификаторов
		 * @return string
		 */
		protected function glueValueListCondition(array $idList) {
			if (isEmptyArray($idList)) {
				return '()';
			}

			$idList = array_map(function($id) {
				return $this->getConnection()->escape($id);
			}, $idList);
			$idList = array_unique($idList);
			return "('" . implode("', '", $idList) . "')";
		}

		/**
		 * Записывает в историю получение списка сущностей по всем возможным параметрам.
		 * Имеет смысл только при получении полного списка сущностей.
		 * @param iEntity[] $entityList список сущностей
		 * @return $this
		 */
		protected function logGetAll(array $entityList) {
			$history = $this->getHistory();

			if (!isEmptyArray($entityList)) {
				$history->logGetAll(count($entityList));
			}

			$attributeAccessor = $this->getAttributeAccessor();

			foreach ($entityList as $entity) {
				foreach ($attributeAccessor->accessOneToAll($entity) as $name => $value) {
					$history->logGet($name, $value);
				}
			}

			return $this;
		}

		/**
		 * Возвращает подключение к бд
		 * @return \IConnection
		 */
		protected function getConnection() {
			return $this->connection;
		}

		/**
		 * Устанавливает подключение к бд
		 * @param \IConnection $connection подключение к бд
		 * @return $this
		 */
		protected function setConnection(\IConnection $connection) {
			$this->connection = $connection;
			return $this;
		}

		/**
		 * Устанавливает историю репозитория
		 * @param iHistory $history история репозитория
		 * @return $this
		 */
		protected function setHistory(iHistory $history) {
			$this->history = $history;
			return $this;
		}
	}