<?php

	use UmiCms\Service;

	/** Тип экспорта в формате CommerceML */
	class commerceMLExporter extends umiExporter {

		/** @inheritdoc */
		public function getSourceName() {
			$umiConfig = mainConfiguration::getInstance();
			$siteId = $umiConfig->get('modules', 'exchange.commerceML.siteId');

			if (!$siteId) {
				$domain = Service::DomainCollection()->getDefaultDomain()->getHost();
				$siteId = md5($domain);
			}

			if (mb_strlen($siteId) > 2) {
				$siteId = mb_substr($siteId, 0, 2);
				$umiConfig->set('modules', 'exchange.commerceML.siteId', $siteId);
				$umiConfig->save();
			}

			return $siteId;
		}

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			if (!umiCount($exportList)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->level(0);
				$sel->types('hierarchy-type')->name('catalog', 'category');
				$sel->types('hierarchy-type')->name('catalog', 'object');
				$sel->option('no-length')->value(true);
				$exportList = $sel->result();
			}

			$exporter = $this->createXmlExporter($this->getSourceName());
			$exporter->addBranches($exportList);
			$exporter->setIgnoreRelations();
			$exporter->excludeBranches($ignoreList);
			$result = $exporter->execute();

			$umiDump = $result->saveXML();

			$template = './xsl/export/' . $this->type . '.xsl';
			if (!is_file($template)) {
				throw new publicException("Can't load exporter {$template}");
			}

			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$doc->loadXML($umiDump);

			$templater = umiTemplater::create('XSLT', $template);
			return $templater->parse($doc);
		}
	}
