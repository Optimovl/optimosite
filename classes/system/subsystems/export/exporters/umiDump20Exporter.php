<?php

	/** Тип экспорта в формате umiDump 2.0 */
	class umiDump20Exporter extends umiExporter {

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			set_time_limit(0);
			if (!umiCount($exportList)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->level(0);
				$exportList = (array) $sel->result();
			}

			$temp_dir = $this->getExportPath();
			$id = getRequest('param0');
			$file_path = $temp_dir . $id . '.' . parent::getFileExt();

			if (getRequest('as_file') === '0') {
				$exporter = $this->createXmlExporter($this->getSourceName());
				$exporter->addBranches($exportList);
				$exporter->excludeBranches($ignoreList);
				$result = $exporter->execute();
				return $result->saveXML();
			}

			if (file_exists($file_path) && !file_exists(SYS_TEMP_PATH . '/runtime-cache/' . md5($this->getSourceName()))) {
				unlink($file_path);
			}

			$new_file_path = $file_path . '.tmp';

			$exporter = $this->createXmlExporter($this->getSourceName(), $this->getLimit());
			$exporter->addBranches($exportList);
			$exporter->excludeBranches($ignoreList);
			$dom = $exporter->execute();

			if (file_exists($file_path)) {
				$reader = new XMLReader;
				$writer = new XMLWriter;

				$reader->open($file_path);
				$writer->openURI($new_file_path);
				$writer->startDocument('1.0', 'utf-8');

				// start root node
				$writer->startElement('umidump');
				$writer->writeAttribute('version', '2.0');
				$writer->writeAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

				$continue = $reader->read();
				while ($continue) {
					if ($reader->nodeType == XMLReader::ELEMENT) {
						$node_name = $reader->name;
						if ($node_name != 'umidump') {
							$writer->startElement($node_name);

							if ($node_name != 'meta') {
								if (!$reader->isEmptyElement) {
									$child_continue = $reader->read();
									while ($child_continue) {
										if ($reader->nodeType == XMLReader::ELEMENT) {
											$child_node_name = $reader->name;
											$writer->writeRaw($reader->readOuterXML());
											$child_continue = $reader->next();
										} elseif ($reader->nodeType == XMLReader::END_ELEMENT &&
											$reader->name == $node_name) {
											$child_continue = false;
										} else {
											$child_continue = $reader->next();
										}
									}
								}

								if ($dom->getElementsByTagName($node_name)->item(0)->hasChildNodes()) {
									$children = $dom->getElementsByTagName($node_name)->item(0)->childNodes;
									foreach ($children as $child) {
										$newdoc = new DOMDocument;
										$newdoc->formatOutput = true;
										$node = $newdoc->importNode($child, true);
										$newdoc->appendChild($node);
										$writer->writeRaw($newdoc->saveXML($node, LIBXML_NOXMLDECL));
									}
								}
							} elseif ($node_name == 'meta') {
								$writer->writeRaw($reader->readInnerXML());
								$exportList = $dom->getElementsByTagName('branches');
								if ($exportList->item(0)) {
									$writer->writeRaw($dom->saveXML($exportList->item(0), LIBXML_NOXMLDECL));
								}
							}

							$writer->fullEndElement();
							$continue = $reader->next();
							continue;
						}
					}
					$continue = $reader->read();
				}

				// finish root node
				$writer->fullEndElement();

				$reader->close();
				$writer->endDocument();
				$writer->flush();
				unlink($file_path);
				rename($new_file_path, $file_path);
			} else {
				file_put_contents($file_path, $dom->saveXML());
			}

			$this->completed = $exporter->isCompleted();
			return false;
		}
	}
