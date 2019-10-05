<?php

	use UmiCms\Service;

	/** Тип экспорта "Перенос UMI.CMS в формате umiDump" */
	class transferExporter extends umiExporter {

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			set_time_limit(0);

			if (!umiCount($exportList)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0)->level(1);
				$exportList = (array) $sel->result();
			}

			if (getRequest('as_file') === '0') {
				$exporter = $this->createXmlExporter($this->getSourceName());
				$exporter->addBranches($exportList);
				$exporter->excludeBranches($ignoreList);
				$exporter = $this->putSettings($exporter);
				$exporter = $this->putEntities($exporter);
				$result = $exporter->execute();
				return $result->saveXML();
			}

			$temp_dir = $this->getExportPath();
			$id = getRequest('param0');
			$file_path = $temp_dir . $id . '.' . parent::getFileExt();
			$destination = $temp_dir . $id;

			if (!is_dir($destination)) {
				mkdir($destination, 0777, true);
			}

			if (file_exists($file_path) && !file_exists(SYS_TEMP_PATH . '/runtime-cache/' . md5($this->getSourceName()))) {
				unlink($file_path);
			}

			if ($exportList) {
				$dirs = [
					'./tpls/',
					'./xsltTpls/',
					'./css/',
					'./js/',
					'./usels/',
					'./umaps/',
					'./templates/',
				];

				foreach ($dirs as $dirName) {
					if (is_dir($dirName)) {
						$dir = new umiDirectory($dirName);
						$files = $dir->getAllFiles(1);
						foreach ($files as $path => $name) {
							$file = new umiFile($path);
							if (!is_dir($destination . ltrim($file->getDirName(), '.'))) {
								mkdir($destination . ltrim($file->getDirName(), '.'), 0777, true);
							}
							copy($file->getFilePath(), $destination . $file->getFilePath(true));
						}
					}
				}
			}

			$new_file_path = $file_path . '.tmp';

			$exporter = $this->createXmlExporter($this->getSourceName(), $this->getLimit());
			$exporter->addBranches($exportList);
			$exporter->excludeBranches($ignoreList);
			$exporter = $this->putSettings($exporter);
			$exporter = $this->putEntities($exporter);
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

		/**
		 * Добавляет к экспорту настройки сайтов из одноименного модуля
		 * @param iXmlExporter $exporter экспортер
		 * @return iXmlExporter
		 */
		protected function putSettings(iXmlExporter $exporter) {

			$cmsController = cmsController::getInstance();

			if ($cmsController->isModule('umiSettings')) {
				$cmsController->getModule('umiSettings');
				$settingsList = Service::SelectorFactory()
					->createObjectTypeGuid(umiSettings::ROOT_TYPE_GUID)
					->result();
				$exporter->addObjects($settingsList);
			}

			return $exporter;
		}

		/**
		 * Добавляет к эспорту ряд сущностей системы из модулей:
		 *
		 * 1) "Онлайн-запись";
		 * 2) "Слайдеры";
		 * 3) "Шаблоны писем";
		 * 4) "Редиректы";
		 *
		 * @param iXmlExporter $exporter экспортер
		 * @return iXmlExporter
		 */
		protected function putEntities(iXmlExporter $exporter) {

			$cmsController = cmsController::getInstance();

			if ($cmsController->isModule('appointment')) {
				$cmsController->getModule('appointment');
				$exporter->addEntities([
					'AppointmentServiceGroups' => [],
					'AppointmentServices' => [],
					'AppointmentEmployees' => [],
					'AppointmentEmployeesServices' => [],
					'AppointmentEmployeesSchedules' => [],
					'AppointmentOrders' => []
				]);
			}

			if ($cmsController->isModule('umiSliders')) {
				$cmsController->getModule('umiSliders');
				$exporter->addEntities([
					'SlidersCollection' => [],
					'SlidesCollection' => []
				]);
			}

			if ($cmsController->isModule('umiNotifications')) {
				$cmsController->getModule('umiNotifications');
				$exporter->addEntities([
					'MailNotifications' => [],
					'MailTemplates' => [],
					'MailVariables' => [],
				]);
			}

			if ($cmsController->isModule('umiRedirects')) {
				$cmsController->getModule('umiRedirects');
				$exporter->addEntities([
					'Redirects' => []
				]);
			}

			return $exporter;
		}
	}
