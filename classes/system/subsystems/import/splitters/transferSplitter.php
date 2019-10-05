<?php

	use UmiCms\Service;

	/** Тип импорта "Перенос UMI.CMS в формате umiDump" */
	class transferSplitter extends umiDump20Splitter {

		/** @inheritdoc */
		protected function readDataBlock() {
			$doc = parent::readDataBlock();

			if ($doc->getElementsByTagName('domains')->length) {
				$domains = $doc->getElementsByTagName('domains')->item(0);
				if ($domains->getElementsByTagName('domain')->length) {
					$domain = $domains->getElementsByTagName('domain')->item(0);
					$domainId = false;

					$importId = getRequest('param0');
					if ($importId) {
						$elements = umiObjectsCollection::getInstance()->getObject($importId)->elements;
						if (is_array($elements) && umiCount($elements)) {
							$domainId = $elements[0]->getDomainId();
						}
					}

					$domainCollection = Service::DomainCollection();

					if ($domainId) {
						$newDomain = $domainCollection->getDomain($domainId);
					} else {
						$newDomain = $domainCollection->getDefaultDomain();
					}

					if ($newDomain instanceof iDomain) {
						$newHost = $newDomain->getHost();
						$domain->setAttribute('host', $newHost);
					}
				}
			}

			return $doc;
		}
	}
