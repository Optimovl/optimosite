<?php

	/** Тип экспорта предложений в формате CommerceML 2.0 */
	class offersCommerceMLExporter extends umiExporter {

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('catalog', 'object');
			foreach ($exportList as $branch) {
				$sel->where('hierarchy')->page($branch->id)->level(1000);
			}

			$exporter = $this->createXmlExporter('CommerceML2');
			$exporter->addElements($sel->result());
			$exporter->setIgnoreRelations();
			$exporter->excludeBranches($ignoreList);
			$umiDump = $exporter->execute();

			$template = './xsl/export/' . $this->type . '.xsl';
			if (!is_file($template)) {
				throw new publicException("Can't load exporter {$template}");
			}

			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$doc->loadXML($umiDump->saveXML());

			$templater = umiTemplater::create('XSLT', $template);
			return $templater->parse($doc);
		}

	}
