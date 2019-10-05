<?php
	namespace UmiCms\System\Orm\Entity;

	use \iXmlExporter as iBaseExporter;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;
	use UmiCms\System\Orm\Entity\Schema\tInjector as tSchemaInjector;
	use UmiCms\System\Orm\Entity\Facade\tInjector as tFacadeInjector;

	/**
	 * Абстрактный класс экспортера сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Exporter implements iExporter {

		use tSchemaInjector;
		use tFacadeInjector;

		/** @inheritdoc */
		public function __construct(iFacade $facade, iSchema $schema) {
			$this->setFacade($facade)
				->setSchema($schema);
		}

		/** @inheritdoc */
		public function export(array $idList, iBaseExporter $exporter) {
			$collection = $this->getFacade()->getCollectionByIdList($idList);
			$externalIdList = $this->getRelationMap($collection->extractId(), $exporter);
			return $this->changeIdInternalToExternal($collection->map(), $externalIdList, $exporter->getEntitySourceIdBinder());
		}

		/** @inheritdoc */
		public function getRelationMap(array $internalIdList, iBaseExporter $exporter) {
			return $exporter->getEntitySourceIdBinder()
				->getExternalIdList($internalIdList, $this->getTable());
		}

		/**
		 * Возвращает имя таблицы для связей экспортируемых сущностей
		 * @return string
		 * @throws \ReflectionException
		 */
		protected function getTable() {
			return $this->getSchema()->getExchangeName();
		}

		/**
		 * Заменяет внутренние идентификаторы сущностей коллекции на внешние
		 * @param array $rowList список атрибутов сущностей
		 * @param int[]|string[] $externalIdList список внешних идентификаторов сущностей
		 * @param iSourceIdBinder $sourceIdBinder связыватель внешних и внутренних идентификаторов
		 * @return array
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		protected function changeIdInternalToExternal(array $rowList, array $externalIdList, iSourceIdBinder $sourceIdBinder) {
			$relationList = [];
			$result = [];

			foreach ($rowList as $index => $row) {
				$internalId = $row['id'];

				if (isset($externalIdList[$internalId])) {
					$row['id'] = $externalIdList[$internalId];
				} else {
					$relationList[$internalId] = $internalId;
				}

				$id = $row['id'];
				$result[$id] = $row;
			}

			$sourceIdBinder->defineRelationList($relationList, $this->getTable());
			return $result;
		}
	}