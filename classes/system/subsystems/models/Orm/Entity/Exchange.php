<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use \iXmlExporter as iBaseExporter;
	use UmiCms\System\Import\UmiDump\Entity\iBaseImporter;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;
	use UmiCms\System\Orm\Entity\Facade\tInjector as tFacadeInjector;
	use UmiCms\System\Orm\Entity\Builder\tInjector as tBuilderInjector;
	use UmiCms\System\Orm\Entity\Importer\tInjector as tImporterInjector;
	use UmiCms\System\Orm\Entity\Exporter\tInjector as tExporterInjector;
	use UmiCms\System\Orm\Entity\Demolisher\tInjector as tDemolisherInjector;
	use UmiCms\System\Orm\Entity\Relation\Accessor\tInjector as tRelationAccessorInjector;

	/**
	 * Абстрактный класс фасада импорта и экспорта сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Exchange implements iExchange {

		use tFacadeInjector;
		use tBuilderInjector;
		use tImporterInjector;
		use tExporterInjector;
		use tDemolisherInjector;
		use tRelationAccessorInjector;

		/** @inheritdoc */
		public function __construct(
			iImporter $importer, iExporter $exporter, iFacade $facade,
			iBuilder $builder, iAccessor $accessor, iDemolisher $demolisher
		) {
			$this->setImporter($importer)
				->setExporter($exporter)
				->setFacade($facade)
				->setBuilder($builder)
				->setRelationAccessor($accessor)
				->setDemolisher($demolisher);
		}

		/** @inheritdoc */
		public function importOne(array $attributeList, iBaseImporter $baseImporter) {
			return $this->getImporter()
				->import($attributeList, $baseImporter);
		}

		/** @inheritdoc */
		public function importMany(array $attributeListSet, iBaseImporter $baseImporter) {
			$entityList = [];
			$importer = $this->getImporter();

			foreach ($attributeListSet as $attributeList) {
				$entity = $importer->import($attributeList, $baseImporter);

				if (!$entity instanceof iEntity) {
					continue;
				}

				$entityList[] = $entity;
			}

			return $this->getFacade()
				->mapCollection($entityList);
		}

		/** @inheritdoc */
		public function getInternalIdList(array $externalIdList, iBaseImporter $baseImporter) {
			$relationMap = $this->getImporter()
				->getRelationMap($externalIdList, $baseImporter);
			return array_values($relationMap);
		}

		/** @inheritdoc */
		public function exportOne($id ,iBaseExporter $baseExporter) {
			$idList = (array) $id;
			$result = $this->exportMany($idList, $baseExporter);
			return isset($result[$id]) ? $result[$id] : [];
		}

		/** @inheritdoc */
		public function exportMany(array $idList, iBaseExporter $baseExporter) {
			return $this->getExporter()
				->export($idList, $baseExporter);
		}

		/** @inheritdoc */
		public function getExternalIdList(array $internalIdList, iBaseExporter $baseExporter) {
			$relationMap = $this->getExporter()
				->getRelationMap($internalIdList, $baseExporter);
			return array_values($relationMap);
		}

		/** @inheritdoc */
		public function getDependenciesForExportOne($id) {
			$idList = (array) $id;
			return $this->getDependenciesForExportMany($idList);
		}

		/** @inheritdoc */
		public function getDependenciesForExportMany(array $idList) {
			/** @var iCollection $collection */
			$collection = $this->getFacade()->getCollectionByIdList($idList);

			if ($collection->getCount() === 0) {
				return [];
			}

			$this->getBuilder()->buildAllRelationsForCollection($collection);
			$relationAccessor = $this->getRelationAccessor();
			$dependencyIdList = [];

			foreach ($relationAccessor->accessCollectionToAll($collection) as $id => $relation) {
				foreach ($relation as $field => $dependency) {
					if ($dependency === null) {
						continue;
					}

					$class = $this->getDependencyExchangeClass($dependency);
					$idList = isset($dependencyIdList[$class]) ? $dependencyIdList[$class] : [];
					$dependencyIdList[$class] = array_merge($idList, $this->getDependencyIdList($dependency));
				}
			}

			foreach ($dependencyIdList as $class => $idList) {
				$dependencyIdList[$class] = array_unique($idList);
			}

			return $dependencyIdList;
		}

		/** @inheritdoc */
		public function demolishByExternalIdList($externalIdList, iSourceIdBinder $sourceIdBinder) {
			$this->getDemolisher()->demolishList($externalIdList, $sourceIdBinder);
			return $this;
		}

		/**
		 * Возвращает список идентификаторов зависимой сущности
		 * @param iEntity|\iUmiEntinty|iCollection|null $dependency зависимая сущность
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		protected function getDependencyIdList($dependency) {
			switch (true) {
				case is_callable([$dependency, 'getId']) : {
					return (array) $dependency->getId();
				}
				case is_callable([$dependency, 'extractId']) : {
					return $dependency->extractId();
				}
				default : {
					throw new \ErrorException('Unexpected dependency');
				}
			}
		}

		/**
		 * Возвращает имя класса, ответственного за экспорт и импорт зависимой сущности
		 * @param iEntity|\iUmiEntinty|iCollection|null $dependency зависимая сущность
		 * @return string
		 * @throws \ErrorException
		 */
		protected function getDependencyExchangeClass($dependency) {
			switch (true) {
				case is_callable([$dependency, 'getId']) : {
					$entityClass = get_class($dependency);
					break;
				}
				case is_callable([$dependency, 'extractId']) : {
					$entityClass = str_replace('Collection', '', get_class($dependency));
					break;
				}
				default : {
					throw new \ErrorException('Unexpected dependency');
				}
			}

			return $this->buildExchangeClass($entityClass);
		}

		/**
		 * Формирует имя класса, ответственного за экспорт и импорт зависимой сущности
		 * @example: UmiCms\System\Trade\Offer => TradeOfferExchange
		 * @param string $entityClass имя класса экспортируемой зависимой сущности
		 * @return string
		 */
		protected function buildExchangeClass($entityClass) {
			$entityClass = str_replace('UmiCms\System\\', '', $entityClass);
			$entityClass = explode('\\', $entityClass);
			$entityClass = implode('', $entityClass);
			return sprintf('%sExchange', $entityClass);
		}
	}