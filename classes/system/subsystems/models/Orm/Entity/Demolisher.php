<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;
	use UmiCms\System\Orm\Entity\Schema\tInjector as tSchemaInjector;
	use UmiCms\System\Orm\Entity\Facade\tInjector as tFacadeInjector;

	/**
	 * Абстрактный класс удаления импортированных сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	abstract class Demolisher implements iDemolisher {

		use tSchemaInjector;
		use tFacadeInjector;

		/** @inheritdoc */
		public function __construct(iFacade $facade, iSchema $schema) {
			$this->setFacade($facade)
				->setSchema($schema);
		}

		/** @inheritdoc */
		public function demolishList($externalIdList, iSourceIdBinder $sourceIdBinder) {
			foreach ($externalIdList as $externalId) {
				$this->demolish($externalId, $sourceIdBinder);
			}

			return $this;
		}

		/** @inheritdoc */
		public function demolish($externalId, iSourceIdBinder $sourceIdBinder) {
			$table = $this->getSchema()->getExchangeName();
			$internalId = $sourceIdBinder->getInternalId($externalId, $table);

			if ($internalId === null || $sourceIdBinder->isRelatedToAnotherSource($internalId, $table)) {
				return false;
			}

			$this->getFacade()->delete($internalId);
			return true;
		}
	}