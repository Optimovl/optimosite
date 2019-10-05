<?php

	/** Тип импорта в формате CommerceML */
	class commerceML2Splitter extends umiImportSplitter {

		/** @inheritdoc */
		public $ignoreParentGroups = false;

		/** @inheritdoc */
		public $autoGuideCreation = true;

		/** @inheritdoc */
		public $renameFiles = true;

		protected function __getNodeParents(DOMNode $element) {
			$parents = [];
			$parents[] = $element->nodeName;
			if (($parent = $element->parentNode) instanceof DOMElement) {
				$parents = array_merge($this->__getNodeParents($element->parentNode), $parents);
			}

			return $parents;
		}

		protected function __getNodePath(DOMNode $element) {
			return implode('/', $this->__getNodeParents($element));
		}

		protected function __collectGroup(DOMDocument $doc, DOMNode $groups, DOMElement $group) {
			$xpath = new DOMXPath($doc);

			$id_nl = $group->getElementsByTagName('Ид');
			$id = $id_nl->item(0)->nodeValue;

			$found_nl = $xpath->evaluate(".//Группа[Ид='{$id}']", $groups);
			if ($found_nl instanceof DOMNodeList && $found_nl->length) {
				return $found_nl->item(0);
			}

			$new_group = $this->getNewCollectGroup($doc, $group);

			$parent = $group->parentNode ? $group->parentNode->parentNode : false;
			if ($parent && $parent->nodeName == 'Группа') {
				$cparent = $this->__collectGroup($doc, $groups, $parent);
				$cgroups = $this->getChildGroupsElement($cparent);
				if (!$cgroups) {
					$cgroups = $cparent->appendChild($doc->createElement('Группы'));
				}
				$cgroups->appendChild($new_group);
			} else {
				$groups->appendChild($new_group);
			}

			return $new_group;
		}

		/**
		 * Создает новую группу при рекурсивном перемещении групп
		 * @param DOMDocument $doc
		 * @param DOMElement $group
		 * @return DOMElement
		 */
		protected function getNewCollectGroup(DOMDocument $doc, DOMElement $group) {
			$nameNodeList = $group->getElementsByTagName('Наименование');
			$name = $nameNodeList->item(0)->nodeValue;
			$idNodeList = $group->getElementsByTagName('Ид');
			$id = $idNodeList->item(0)->nodeValue;
			$newGroup = $doc->createElement('Группа');
			$newGroup->appendChild($doc->createElement('Ид', $id));
			$newGroup->appendChild($doc->createElement('Наименование', $name));

			return $newGroup;
		}

		/**
		 * Извлекает элемент 'Группы' при рекурсивном перемещении групп
		 * @param DOMElement $parent - родительский элемент
		 * @return bool|DOMNode, false - в случае отсутствия искомого элемента
		 */
		protected function getChildGroupsElement(DOMElement $parent) {
			return $parent->childNodes->item(2);
		}

		/**
		 * Проверяет что текущий и предыдущий идентификатор предложения
		 * не являются одинаковыми
		 * @param DOMDocument $doc документ для чтения блока данных
		 * @param DOMDocument $offer документ со списком предложений
		 * @param int $collected порядковый номер предложения
		 * @return bool
		 */
		protected function __getOffersCompare(DOMDocument $doc, DOMDocument $offer, $collected) {
			$xpath = new DOMXPath($doc);
			$result = $xpath->evaluate('/КоммерческаяИнформация/ПакетПредложений/Предложения/Предложение/Ид')
				->item($collected - 1);

			if (!$result) {
				return true;
			}

			$previousOfferIds = explode('#', $result->nodeValue);
			$previousOfferId = $previousOfferIds[0];

			$offerXpath = new DOMXPath($offer);
			$namespace = $this->getNamespace($offerXpath);

			if ($namespace) {
				$offerXpath->registerNamespace('ns', $namespace);
				$offerResult = $offerXpath->evaluate('/ns:Предложение/ns:Ид')->item(0);
			} else {
				$offerResult = $offerXpath->evaluate('/Предложение/Ид')->item(0);
			}

			$offerIds = explode('#', $offerResult->nodeValue);
			$offerId = $offerIds[0];

			return $previousOfferId != $offerId;
		}

		/**
		 * Возвращает основное пространство имен или false в случае его отсутствия
		 * @param DOMXpath $path экземпляр DOMXpath
		 * @return string|bool
		 */
		protected function getNamespace(DOMXpath $path) {
			$namespace = $path->query("namespace::*[name()='']")
				->item(0);

			return isset($namespace) ? $namespace->nodeValue : false;
		}

		/** @inheritdoc */
		protected function readDataBlock() {
			$r = new XMLReader;
			$r->open($this->file_path);

			$config = mainConfiguration::getInstance();
			$scheme_file = $config->includeParam('system.kernel') . 'subsystems/import/schemes/' . $this->type . '.xsd';
			if (is_file($scheme_file)) {
				$r->setSchema($scheme_file);
			}

			$doc = $this->createDocumentForRead();

			$entities = [
				'Группа',
				'Товар',
				'Предложение',
			];

			$ignoreEntities = [
				'ОписаниеГрупп',
			];

			$collected = 0;
			$position = 0;
			$container = $doc;
			$continue = $r->read();

			while ($continue) {
				switch ($r->nodeType) {
					case XMLReader::ELEMENT: {
						if (in_array($r->name, $ignoreEntities)) {
							$continue = $r->next();
							continue 2;
						}
						if (in_array($r->name, $entities)) {
							if ($position++ < $this->offset) {
								$continue = $r->next();
								continue 2;
							}
							if (($collected + 1) > $this->block_size) {
								if ($r->name == 'Предложение') {
									secure_load_dom_document($r->readOuterXML(), $offer);

									if ($this->__getOffersCompare($doc, $offer, $collected)) {
										break 2;
									}
								} else {
									break 2;
								}
							}
							$collected++;
						}

						$el = $doc->createElement($r->name, $r->value);
						$container->appendChild($el);
						if (!$r->isEmptyElement) {
							$container = $el;
						}

						// create attributes
						if ($r->attributeCount) {
							while ($r->moveToNextAttribute()) {
								$attr = $doc->createAttribute($r->name);
								$attr->appendChild($doc->createTextNode($r->value));
								$el->appendChild($attr);
							}
						}

						$node_path = $this->__getNodePath($container);
						if ($node_path == 'КоммерческаяИнформация/Классификатор/Группы') {
							$groupsXML = $r->readOuterXML();
							secure_load_dom_document($groupsXML, $groups);
							$groups_nl = $groups->getElementsByTagName('Группа');
							foreach ($groups_nl as $group) {
								if ($position++ < $this->offset) {
									continue;
								}
								if (($collected + 1) > $this->block_size) {
									break;
								}
								$this->__collectGroup($doc, $el, $group);
								$collected++;
							}
							$container = $container->parentNode;
							$continue = $r->next();
							continue 2;
						}
					}
						break;

					case XMLReader::END_ELEMENT: {
						$container = $container->parentNode;
					}
						break;

					case XMLReader::ATTRIBUTE: {
						$attr = $doc->createAttribute($r->name);
						$attr->appendChild($doc->createTextNode($r->value));
						$container->appendChild($attr);
					}
						break;

					case XMLReader::TEXT: {
						$txt = $doc->createTextNode($r->value);
						$container->appendChild($txt);
					}
						break;

					case XMLReader::CDATA: {
						$cdata = $doc->createCDATASection($r->value);
						$container->appendChild($cdata);
					}
						break;

					case XMLReader::NONE:
					default:
				}

				$continue = $r->read();
			}

			$this->offset += $collected;

			if (!$continue) {
				$this->complete = true;
			}

			return $doc;
		}

		/**
		 * Создает документ для чтения блока данных
		 * @return DOMDocument
		 */
		protected function createDocumentForRead() {
			return new DomDocument('1.0', 'utf-8');
		}

		/** @inheritdoc */
		public function getRenameFiles() {
			$config = mainConfiguration::getInstance();
			$renameFiles = $config->get('modules', 'exchange.commerceML.renameFiles') !== null ? $config->get(
				'modules',
				'exchange.commerceML.renameFiles'
			) : $this->renameFiles;
			return (bool) $renameFiles;
		}
	}
