<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления доменов
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Domain extends Demolisher {

		/** @var \iDomainsCollection $domainCollection коллекция доменов */
		private $domainCollection;

		/**
		 * Конструктор
		 * @param \iDomainsCollection $domainCollection коллекция доменов
		 */
		public function __construct(\iDomainsCollection $domainCollection) {
			$this->domainCollection = $domainCollection;
		}

		/** @inheritdoc */
		protected function execute() {
			$sourceIdBinder = $this->getSourceIdBinder();
			$sourceId = $this->getSourceId();
			$domainCollection = $this->getDomainCollection();

			foreach ($this->getDomainExtIdList() as $extId) {
				$id = $sourceIdBinder->getNewDomainIdRelation($sourceId, $extId);

				if ($id === false || $sourceIdBinder->isDomainRelatedToAnotherSource($sourceId, $id)) {
					$this->pushLog(sprintf('Domain "%s" was ignored', $extId));
					continue;
				}

				$domainCollection->delDomain($id);
				$this->pushLog(sprintf('Domain "%s" was deleted', $id));
			}
		}

		/**
		 * Возвращает список внешних идентификаторов удаляемых доменов
		 * @return string[]
		 */
		private function getDomainExtIdList() {
			return $this->getNodeValueList('/umidump/domains/domain/@id');
		}

		/**
		 * Возвращает коллекцию доменов
		 * @return \iDomainsCollection
		 */
		private function getDomainCollection() {
			return $this->domainCollection;
		}
	}
