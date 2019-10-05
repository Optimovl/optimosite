<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Import\UmiDump\Entity\iBaseImporter;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;
	use UmiCms\System\Orm\Entity\Schema\tInjector as tSchemaInjector;
	use UmiCms\System\Orm\Entity\Facade\tInjector as tFacadeInjector;

	/**
	 * Абстрактный класс импортера сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Importer implements iImporter {

		use tSchemaInjector;
		use tFacadeInjector;

		/** @inheritdoc */
		public function __construct(iFacade $facade, iSchema $schema) {
			$this->setFacade($facade)
				->setSchema($schema);
		}

		/** @inheritdoc */
		public function import(array $attributeList, iBaseImporter $baseImporter) {
			try {
				$sourceIdBinder = $baseImporter->getSourceIdBinder();
				$attributeList = $this->updateRelatedId($attributeList, $sourceIdBinder);
				$externalId = $this->extractId($attributeList);
				$table = $this->getTable();
				$internalId = $sourceIdBinder->getInternalId($externalId, $table);
				$facade = $this->getFacade();
				$existEntity = $facade->get($externalId);

				if (!$internalId && $existEntity instanceof iEntity) {
					$sourceIdBinder->defineRelation($externalId, $externalId, $table);
					$internalId = $externalId;
				}

				if ($internalId) {
					$existEntity = $facade->get($internalId);
				}

				$installOnly = $this->extractFlag($attributeList, $baseImporter::INSTALL_ONLY_FLAG);

				if ($existEntity instanceof iEntity && $installOnly) {
					return $existEntity;
				}

				if ($existEntity instanceof iEntity) {
					$this->update($existEntity->getId(), $attributeList);
					$baseImporter->logUpdated($externalId);
					return $existEntity;
				}

				$newEntity = $this->create($attributeList);
				$sourceIdBinder->defineRelation($externalId, $newEntity->getId(), $table);
				$baseImporter->logCreated($externalId);
			} catch (\Exception $exception) {
				$newEntity = null;
				$baseImporter->logError($exception->getMessage());
			}

			return $newEntity;
		}

		/** @inheritdoc */
		public function getRelationMap(array $externalIdList, iBaseImporter $baseImporter) {
			return $baseImporter->getSourceIdBinder()
				->getInternalIdList($externalIdList, $this->getTable());
		}

		/**
		 * Извлекает значение флага
		 * @param array $attributeList атрибуты сущности
		 * @param string $flag имя флага
		 * @param mixed $defaultValue значение флага по умолчанию
		 * @return mixed
		 */
		protected function extractFlag(array &$attributeList, $flag, $defaultValue = false) {

			if (!isset($attributeList[$flag])) {
				return $defaultValue;
			}

			$value = $attributeList[$flag];
			unset($attributeList[$flag]);
			return $value;
		}

		/**
		 * Обновляет сущность
		 * @param int $internalId внутренний идентификатор сущности
		 * @param array $attributeList атрибуты сущности
		 * @return iEntity
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		protected function update($internalId, array $attributeList) {
			$facade = $this->getFacade();
			$entity = $facade->get($internalId);

			if (!$entity instanceof iEntity) {
				throw new \ErrorException('Entity not found');
			}

			$facade->importToEntity($entity, $attributeList);
			return $entity;
		}

		/**
		 * Создает сущность
		 * @param array $attributeList атрибуты сущности
		 * @return iEntity
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		protected function create(array $attributeList) {
			return $this->getFacade()->create($attributeList);
		}

		/**
		 * Извлекает идентификатор сущности из списка ее атрибутов
		 * @param array $attributeList атрибуты сущности
		 * @return mixed
		 * @throws \ErrorException
		 */
		protected function extractId(array &$attributeList) {

			if (!isset($attributeList['id'])) {
				throw new \ErrorException('Entity id not found');
			}

			$id = $attributeList['id'];
			unset($attributeList['id']);
			return $id;
		}

		/**
		 * Возвращает имя таблицы для связей импортируемых сущностей
		 * @return string
		 * @throws \ReflectionException
		 */
		protected function getTable() {
			return $this->getSchema()->getExchangeName();
		}

		/**
		 * Обновляет идентификатор связанных сущностей
		 * @param array $attributeList список атрибутов импортирумой сущности
		 * @param iSourceIdBinder $sourceIdBinder связыватель внешних и внутренних идентификаторов
		 * @return array
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		protected function updateRelatedId(array $attributeList, iSourceIdBinder $sourceIdBinder) {
			$relatedExchangeNameList = $this->getSchema()
				->getRelatedExchangeNameList();

			foreach ($relatedExchangeNameList as $field => $table) {

				if (!isset($attributeList[$field])) {
					continue;
				}

				$externalId = $attributeList[$field];
				$internalId = $sourceIdBinder->getInternalId($externalId, $table);

				if ($internalId) {
					$attributeList[$field] = $internalId;
				}
			}

			return $attributeList;
		}
	}