<?php

	/** Тип экспорта каталога товаров в формате CommerceML */
	class catalogCommerceMLExporter extends umiExporter {

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			if (!umiCount($exportList)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->level(0);
				$exportList = $sel->result();
			}

			$exporter = $this->createXmlExporter('commerceML2');
			$exporter->addBranches($exportList);
			$exporter->ignoreRelationsExcept('guides');
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
