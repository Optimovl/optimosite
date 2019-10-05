<?php

	use UmiCms\Service;

	/**
	 * Реализует доступ и управление свойствами страниц.
	 * Страницы это то, что в системе фигурирует в структуре сайта.
	 */
	class umiHierarchyElement extends umiEntinty implements iUmiHierarchyElement {

		/** @const int количество свойств страницы, необходимое для корректного инстанцирования */
		const INSTANCE_ATTRIBUTE_COUNT = 21;

		private $rel;

		private $alt_name;

		private $ord;

		private $object_id;

		private $type_id;

		private $domain_id;

		private $lang_id;

		private $tpl_id;

		private $is_deleted = false;

		private $is_active = true;

		private $is_visible = true;

		private $is_default = false;

		private $update_time;

		private $fields;

		protected $store_type = 'element';

		/** @inheritdoc */
		public function getIsDeleted() {
			return $this->is_deleted;
		}

		/** @inheritdoc */
		public function getIsActive() {
			return $this->is_active;
		}

		/** @inheritdoc */
		public function getIsVisible() {
			return $this->is_visible;
		}

		/** @inheritdoc */
		public function getLangId() {
			return $this->lang_id;
		}

		/** @inheritdoc */
		public function getDomainId() {
			return $this->domain_id;
		}

		/** @inheritdoc */
		public function getTplId() {
			return $this->tpl_id;
		}

		/** @inheritdoc */
		public function getTypeId() {
			return $this->type_id;
		}

		/** @inheritdoc */
		public function getUpdateTime() {
			return $this->update_time;
		}

		/** @inheritdoc */
		public function getOrd() {
			return $this->ord;
		}

		/** @inheritdoc */
		public function getAltName() {
			return $this->alt_name;
		}

		/** @inheritdoc */
		public function getIsDefault() {
			return $this->is_default;
		}

		/** @inheritdoc */
		public function hasVirtualCopy() {
			$objectId = (int) $this->getObjectId();
			$query = <<<SQL
SELECT `id` FROM `cms3_hierarchy` WHERE `obj_id` = $objectId LIMIT 0, 2
SQL;
			$queryResult = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($query);

			return ($queryResult->length() > 1);
		}

		/** @inheritdoc */
		public function isOriginal() {
			$objectId = (int) $this->getObjectId();
			$query = <<<SQL
SELECT `id` FROM `cms3_hierarchy` WHERE `obj_id` = $objectId LIMIT 0, 1
SQL;
			try {
				$queryResult = ConnectionPool::getInstance()
					->getConnection()
					->queryResult($query)
					->setFetchType(IQueryResult::FETCH_ASSOC);
			} catch (databaseException $exception) {
				umiExceptionHandler::report($exception);
				return false;
			}

			if ($queryResult->length() === 0) {
				return false;
			}

			$queryResultRow = $queryResult->fetch();
			$originalPageId = array_shift($queryResultRow);

			return ($originalPageId == $this->getId());
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getObject() {
			$object = umiObjectsCollection::getInstance()
				->getObject($this->object_id);

			if (!$object instanceof iUmiObject) {
				throw new coreException(sprintf('Cannot load iUmiObject by id "%s"', $this->object_id));
			}

			return $object;
		}

		/** @inheritdoc */
		public function getParentId() {
			return $this->rel;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getName() {
			return $this->getObject()
				->getName();
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function setName($name) {
			if ($this->getName() != $name) {
				$this->getObject()
					->setName($name);
				$this->setIsUpdated();
			}
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getValue($propName, $params = null, $resetCache = false) {
			return $this->getObject()
				->getValue($propName, $params);
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function setValue($propName, $propValue) {
			$isSaved = $this->getObject()
				->setValue($propName, $propValue);

			if ($isSaved) {
				$this->setIsUpdated();
			}

			return $isSaved;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function loadFields() {
			$fields = $this->fields = umiTypesHelper::getInstance()
				->getFieldsByObjectTypeIds($this->getObjectTypeId());
			$this->fields = $fields[$this->getObjectTypeId()];
		}

		/** @inheritdoc */
		public function setIsVisible($isVisible = true) {
			$isVisible = (bool) $isVisible;

			if ($this->getIsVisible() !== $isVisible) {
				$this->is_visible = $isVisible;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setIsActive($isActive = true) {
			$isActive = (bool) $isActive;

			if ($this->getIsActive() !== $isActive) {
				$this->is_active = $isActive;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setDeleted($isDeleted = true) {
			$isDeleted = (bool) $isDeleted;

			if ($this->getIsDeleted() !== $isDeleted) {
				$this->is_deleted = $isDeleted;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setTypeId($typeId) {
			$typeId = (int) $typeId;

			if ($this->getTypeId() !== $typeId) {
				$this->type_id = $typeId;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setLangId($langId) {
			$langId = (int) $langId;

			if ($this->getLangId() !== $langId) {
				$this->lang_id = $langId;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setTplId($templateId) {
			$templateId = (int) $templateId;

			if ($this->getTplId() !== $templateId) {
				$this->tpl_id = (int) $templateId;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setDomainId($domainId) {
			$domainId = (int) $domainId;

			if ($this->getDomainId() !== $domainId) {
				$this->domain_id = $domainId;
				$this->setIsUpdated();
			}

			$hierarchy = umiHierarchy::getInstance();
			$children = $hierarchy->getChildrenTree($this->getId());

			foreach ($children as $child_id => $nl) {
				$child = $hierarchy->getElement($child_id, true, true);
				$child->setDomainId($domainId);
				$child->commit();
				$hierarchy->unloadElement($child_id);
				unset($child);
			}
		}

		/** @inheritdoc */
		public function setUpdateTime($updateTime = 0) {
			$updateTime = (int) $updateTime;

			if ($updateTime == 0) {
				$updateTime = umiHierarchy::getTimeStamp();
			}

			if ($this->getUpdateTime() !== $updateTime) {
				$this->update_time = $updateTime;
				parent::setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setOrd($ord) {
			$ord = (int) $ord;

			if ($this->getOrd() !== $ord) {
				$this->ord = $ord;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setRel($parentId) {
			$parentId = (int) $parentId;

			if ($parentId == $this->getId()) {
				throw new coreException('Page cannot be parent for itself');
			}

			if ($this->getParentId() !== $parentId) {
				$this->rel = $parentId;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setObject(iUmiObject $object, $shouldSetUpdated = true) {
			if ($this->getObjectId() != $object->getId()) {
				$this->object_id = $object->getId();
				$this->setIsUpdated();

				if ($shouldSetUpdated) {
					$object->setIsUpdated();
				}
			}
		}

		/**
		 * @inheritdoc
		 * @throws Exception
		 */
		public function setAltName($rawAltName, $autoConvert = true) {
			if (!$rawAltName) {
				$rawAltName = $this->getName();
			}

			if ($autoConvert) {
				$rawAltName = umiHierarchy::convertAltName($rawAltName);
				$rawAltName = $rawAltName ?: '_';
			}

			$fixedAltName = $this->getRightAltName(
				umiObjectProperty::filterInputString($rawAltName)
			);

			$newAltName = $fixedAltName ?: $rawAltName;

			if ($this->getAltName() !== $newAltName) {
				$this->alt_name = $newAltName;
				$this->setIsUpdated();
			}
		}

		/**
		 * При выгрузке страницы нужно выгружать связанный объект.
		 * Вся память там.
		 */
		public function __destruct() {
			$objectId = $this->object_id;
			parent::__destruct();
			umiObjectsCollection::getInstance()->unloadObject($objectId);
		}

		/**
		 * Разрешает коллизии в псевдостатическом адресе страницы
		 * @param string $altName псевдостатический адрес страницы
		 * @param bool $useDenseNumbering использовать все свободные цифры по порядку
		 * @return string откорректированный результат
		 * @throws Exception
		 */
		private function getRightAltName($altName, $useDenseNumbering = false) {
			return umiHierarchy::getInstance()->getRightAltName($altName, $this, $useDenseNumbering);
		}

		/** @inheritdoc */
		public function setIsDefault($isDefault = true) {
			$isDefault = (bool) $isDefault;

			if ($this->getIsDefault() !== $isDefault) {
				$this->is_default = (bool) $isDefault;
				$this->setIsUpdated();

				$umiHierarchy = umiHierarchy::getInstance();
				$umiHierarchy->clearDefaultElementCache();
			}
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getFieldId($fieldName) {
			return $this->getObject()
				->getType()
				->getFieldId($fieldName);
		}

		/**
		 * @inheritdoc
		 * @param array|bool $data полный набор свойств объекта или false
		 *
		 * [
		 *      0 => 'parent_id',
		 *      1 => 'type_id',
		 *      2 => 'lang_id',
		 *      3 => 'domain_id',
		 *      4 => 'tpl_id',
		 *      5 => 'object_id',
		 *      6 => 'ord',
		 *      7 => 'alt_name',
		 *      8 => 'is_active',
		 *      9 => 'is_visible',
		 *      10 => 'is_deleted',
		 *      11 => 'update_time',
		 *      12 => 'is_default',
		 *      13 => 'object: name',
		 *      14 => 'object: type_id',
		 *      15 => 'object: is_locked',
		 *      16 => 'object: owner_id',
		 *      17 => 'object: guid',
		 *      18 => 'object: type_guid',
		 *      19 => 'object: update_time',
		 *      20 => 'object: ord',
		 * ]
		 *
		 * @return bool
		 * @throws databaseException
		 */
		protected function loadInfo($data = false) {
			if (!is_array($data) || count($data) !== self::INSTANCE_ATTRIBUTE_COUNT) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT
	h.rel,
	h.type_id,
	h.lang_id,
	h.domain_id,
	h.tpl_id,
	h.obj_id,
	h.ord,
	h.alt_name,
	h.is_active,
	h.is_visible,
	h.is_deleted,
	h.updatetime,
	h.is_default,
	o.name,
	o.type_id,
	o.is_locked,
	o.owner_id,
	o.guid,
	t.guid,
	o.updatetime,
	o.ord
FROM cms3_hierarchy h, cms3_objects o, cms3_object_types t
WHERE 
	h.id = $escapedId 
	AND o.id = h.obj_id
	AND o.type_id = t.id
LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$data = $result->fetch();
			}

			if (!is_array($data) || count($data) !== self::INSTANCE_ATTRIBUTE_COUNT) {
				return false;
			}

			list(
				$parentId, $typeId, $langId, $domainId, $tplId, $objectId, $ord, $altName, $active, $visible, $deleted,
				$updateTime, $default, $objectName, $objectTypeId, $objectIsLocked, $objectOwnerId, $objectGuid,
				$objectTypeGuid, $objectUpdateTime, $objectOrd
				) = $data;

			$this->rel = (int) $parentId;
			$this->type_id = (int) $typeId;
			$this->lang_id = (int) $langId;
			$this->domain_id = (int) $domainId;
			$this->tpl_id = (int) $tplId;
			$this->object_id = (int) $objectId;
			$this->ord = (int) $ord;
			$this->alt_name = $altName;
			$this->is_active = (bool) $active;
			$this->is_visible = (bool) $visible;
			$this->is_deleted = (bool) $deleted;
			$this->is_default = (bool) $default;
			$this->update_time = (int) $updateTime ?: umiHierarchy::getTimeStamp();

			$objectData = [
				$objectName,
				$objectTypeId,
				$objectIsLocked,
				$objectOwnerId,
				$objectGuid,
				$objectTypeGuid,
				$objectUpdateTime,
				$objectOrd
			];

			umiObjectsCollection::getInstance()
				->getObject($objectId, $objectData); // предварительная загрузка объекта

			return true;
		}

		/**
		 * @inheritdoc
		 * @throws Exception
		 * @throws databaseException
		 */
		protected function save() {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$pageId = (int) $this->getId();
			$parentId = (int) $this->getParentId();
			$typeId = (int) $this->getTypeId();
			$languageId = (int) $this->getLangId();
			$domainId = (int) $this->getDomainId();

			try {
				$templateId = (int) $this->ensureTemplate()->getId();
			} catch (coreException $exception) {
				$templateId = 'NULL';
			}

			$objectId = (int) $this->getObjectId();
			$ord = (int) $this->getOrd();
			$altName = $connection->escape($this->getAltName());
			$isActive = (int) $this->getIsActive();
			$isVisible = (int) $this->getIsVisible();
			$isDeleted = (int) $this->getIsDeleted();
			$updateTime = (int) $this->getUpdateTime();
			$isDefault = (int) $this->getIsDefault();

			$connection->startTransaction("Updating page with id $pageId");

			try {
				if ($isDefault) {
					$sql = <<<SQL
UPDATE
	`cms3_hierarchy`
SET
	`is_default` = '0'
WHERE
	`is_default` = '1'
AND
	`lang_id` = $languageId
AND
	`domain_id` = $domainId
SQL;
					$connection->query($sql);
				}

				$sql = <<<SQL
UPDATE
	`cms3_hierarchy`
SET
	`rel` = $parentId, `type_id` = $typeId, `lang_id` = $languageId, `domain_id` = $domainId,
	`tpl_id` = $templateId, `obj_id` = $objectId, `ord` = $ord, `alt_name` = '$altName',
	`is_active` = $isActive, `is_visible` = $isVisible, `is_deleted` = $isDeleted,
	`updatetime` = $updateTime, `is_default` = $isDefault
WHERE
	`id` = $pageId
SQL;
				$connection->query($sql);
			} catch (databaseException $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();

			if (defined('PAGES_AUTO_INDEX') && PAGES_AUTO_INDEX) {
				searchModel::getInstance()
					->processPage($this);
			}

			if (!umiHierarchy::$ignoreSiteMap) {
				Service::SiteMapUpdater()
					->update($this);
			}

			try {
				$this->updateYML();
			} catch (Exception $e) {
				// ignored
			}

			return true;
		}

		/**
		 * Определяет шаблон, по которому должна выводиться страница.
		 * Если указанный у страницы шаблон недоступен на ее домене/языке,
		 * устанавливает для страницы шаблон по умолчанию для ее домена/языка.
		 * @return iTemplate
		 * @throws coreException
		 */
		private function ensureTemplate() {
			$templateId = $this->getTplId();
			$languageId = $this->getLangId();
			$domainId = $this->getDomainId();

			$umiTemplates = templatesCollection::getInstance();
			if ($umiTemplates->isExistsForSite($templateId, $domainId, $languageId)) {
				return $umiTemplates->getTemplate($templateId);
			}

			$default = $umiTemplates->getDefaultTemplate($domainId, $languageId);
			if (!$default instanceof iTemplate) {
				throw new coreException('Cannot get default template');
			}

			$this->setTplId($default->getId());
			return $default;
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 * @throws ErrorException
		 * @throws publicAdminException
		 * @throws publicException
		 */
		public function updateYML() {
			$dirName = SYS_TEMP_PATH . '/yml/';

			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$hierarchyCatalogObjectType = $hierarchyTypes->getTypeByName('catalog', 'object');
			$hierarchyCatalogCategoryType = $hierarchyTypes->getTypeByName('catalog', 'category');

			if (!$hierarchyCatalogObjectType || !$hierarchyCatalogCategoryType) {
				return false;
			}

			if ($this->getHierarchyType()->getId() == $hierarchyCatalogCategoryType->getId()) {
				$this->checkYMLinclude();

				if (!$this->is_active || $this->is_deleted) {
					$childsIds = $hierarchy->getChildrenList($this->getId(), false);
					foreach ($childsIds as $childId) {
						$xml = $dirName . $childId . '.txt';
						if (file_exists($xml)) {
							unlink($xml);
						}
					}
				}

				return true;
			}

			if ($this->getHierarchyType()->getId() != $hierarchyCatalogObjectType->getId()) {
				return false;
			}

			if (!is_dir($dirName)) {
				mkdir($dirName, 0777, true);
			}
			$xml = $dirName . "{$this->id}.txt";
			if (file_exists($xml)) {
				unlink($xml);
			}

			if ($this->is_active && !$this->is_deleted) {

				$matches = $this->checkYMLinclude();
				if (!umiCount($matches)) {
					return false;
				}

				$parentId = $this->getParentId();
				$parent = $hierarchy->getElement($parentId, true, true);
				if ($parent instanceof iUmiHierarchyElement) {
					if ($parent->getHierarchyType()->getId() != $hierarchyCatalogCategoryType->getId()) {
						$parentId = false;
						$parents = $hierarchy->getAllParents($this->id, true, true);
						for ($i = umiCount($parents) - 1; $i >= 0; $i--) {
							$newParentId = $parents[$i];
							$newParent = $hierarchy->getElement($newParentId, true);
							if ($newParent instanceof iUmiHierarchyElement &&
								$newParent->getHierarchyType()->getId() == $hierarchyCatalogCategoryType->getId()) {
								$parentId = $newParentId;
								break;
							}
						}
					}
				}
				if (!$parentId) {
					throw new publicAdminException(getLabel('error-update-yml'));
				}

				$exporter = new xmlExporter('yml');
				$exporter->addElements([$this->id]);
				$exporter->setIgnoreRelations();
				$umiDump = $exporter->execute();

				$style_file = CURRENT_WORKING_DIR . '/xsl/export/YML.xsl';
				if (!is_file($style_file)) {
					throw new publicException("Can't load exporter {$style_file}");
				}

				$doc = new DOMDocument('1.0', 'utf-8');
				$doc->formatOutput = XML_FORMAT_OUTPUT;
				$doc->loadXML($umiDump->saveXML());

				$templater = umiTemplater::create('XSLT', $style_file);
				$result = $templater->parse($doc);

				$dom = new DOMDocument();
				$dom->loadXML($result);

				$offers = $dom->getElementsByTagName('offer');
				if ($offers->length) {
					$content = '';
					foreach ($offers as $offer) {
						$category = $offer->getElementsByTagName('categoryId')->item(0);
						if ($category) {
							$category->nodeValue = $parentId;
						}
						if (function_exists('mb_convert_encoding')) {
							$content .= mb_convert_encoding($dom->saveXML($offer), 'CP1251', 'UTF-8');
						} else {
							$content .= iconv('UTF-8', 'CP1251//IGNORE', $dom->saveXML($offer));
						}
					}
					file_put_contents($xml, $content);
				}

				$currencies = $dom->getElementsByTagName('currencies')->item(0);
				$curr = iconv('UTF-8', 'CP1251//IGNORE', $dom->saveXML($currencies));
				file_put_contents($dirName . 'currencies', $curr);

				$shopName = $dom->getElementsByTagName('name')->item(0);
				$name = $shopName->nodeValue;
				$company = $dom->getElementsByTagName('company')->item(0);
				$companyName = $company->nodeValue;

				if (is_array($matches)) {
					$umiDomains = Service::DomainCollection();

					foreach ($matches as $exportId) {
						$domain = $umiDomains->getDomain($this->getDomainId());
						file_put_contents($dirName . 'shop' . $exportId,
							'<name>' . iconv('UTF-8', 'CP1251//IGNORE', $name) . '</name><company>' .
							iconv('UTF-8', 'CP1251', $companyName) . '</company><url>' . $domain->getUrl() . '</url>'
						);
					}
				}
			}
		}

		/**
		 * @deprecated
		 * TODO: Вынести из umiHierarchyElement
		 */
		protected function checkYMLinclude() {
			$dirName = SYS_TEMP_PATH . '/yml/';
			if (!is_dir($dirName)) {
				return false;
			}
			$dir = dir($dirName);

			$matches = [];
			$hierarchy = umiHierarchy::getInstance();
			$parents = $hierarchy->getAllParents($this->id, true, true);

			while (($file = $dir->read()) !== false) {
				if (mb_strpos($file, 'cat')) {

					$exportId = trim($file, 'cat');

					$excluded = [];
					if (file_exists($dirName . $exportId . 'excluded')) {
						$excluded = unserialize(file_get_contents($dirName . $exportId . 'excluded'));
					}

					if (umiCount(array_intersect($excluded, $parents))) {
						continue;
					}

					$parentsArray = unserialize(file_get_contents($dirName . $file));
					$childsArray = unserialize(file_get_contents($dirName . $exportId . 'el'));

					$intersect = array_keys(array_intersect($parents, $parentsArray));
					$categories = [];
					if (file_exists($dirName . 'categories' . $exportId)) {
						$categories = unserialize(file_get_contents($dirName . 'categories' . $exportId));
					}

					if (umiCount($intersect)) {

						$firstParentKey = $intersect[0];
						if ($parents[$firstParentKey] == $this->getId() && $this->getHierarchyType()->getMethod() == 'object') {
							if (isset($parents[$firstParentKey - 1])) {
								$firstParentKey--;
							}
						}

						for ($i = $firstParentKey, $cnt = umiCount($parents); $i < $cnt; $i++) {

							$parentId = $parents[$i];
							$parent = $hierarchy->getElement($parentId);
							if (!$parent instanceof iUmiHierarchyElement) {
								continue;
							}
							if (!$parent->getIsActive() || $parent->getIsDeleted()) {
								if ($this->getHierarchyType()->getMethod() == 'object') {
									return $matches;
								}
							}
							if ($parent->getHierarchyType()->getMethod() != 'category') {
								continue;
							}

							if ($parent->getIsActive() && !$parent->getIsDeleted()) {

								$categoryName = $parent->getName();
								$categoryName = iconv('UTF-8', 'CP1251//IGNORE', $categoryName);
								$categoryName = strtr($categoryName, ['&' => '&amp;', '<' => '&lt;', '>' => '&gt;']);

								$parentCategoryId = $parent->getParentId();
								if ($parentCategoryId && isset($categories[$parentCategoryId])) {
									$categories[$parentId] =
										'<category id="' . $parentId . '" parentId="' . $parentCategoryId . '">' . $categoryName .
										'</category>';
								} else {
									$categories[$parentId] = '<category id="' . $parentId . '">' . $categoryName . '</category>';
								}
							} else {
								if (isset($categories[$parentId])) {
									unset($categories[$parentId]);
								}
							}
						}

						if (!in_array($this->id, $childsArray) && $this->getHierarchyType()->getMethod() == 'object') {
							$childsArray[] = $this->id;
							file_put_contents($dirName . $exportId . 'el', serialize($childsArray));
						}
						$matches[] = $exportId;
					} elseif ($this->getHierarchyType()->getMethod() == 'category' &&
						(!$this->getIsActive() || $this->getIsDeleted())) {

						$childs = $hierarchy->getChildrenList($this->getId(), false);
						$intersect = array_intersect($childs, $parentsArray);
						if (umiCount($intersect)) {
							foreach ($childs as $key => $childId) {
								if (isset($categories[$childId])) {
									unset($categories[$childId]);
								}
							}
						}
					} else {
						$key = array_search($this->id, $childsArray);

						if ($key && $this->getHierarchyType()->getMethod() == 'object') {
							unset($childsArray[$key]);
							sort($childsArray);
							file_put_contents($dirName . $exportId . 'el', serialize($childsArray));
						}
					}

					file_put_contents($dirName . 'categories' . $exportId, serialize($categories));
				}
			}

			$dir->close();
			return $matches;
		}

		/** @inheritdoc */
		public function updateSiteMap($ignoreChildren = false) {
			$hierarchy = umiHierarchy::getInstance();
			$id = (int) $this->id;
			$updater = Service::SiteMapUpdater();

			if (!$ignoreChildren) {
				$children = $hierarchy->getChildrenTree($id, true, true, 1);

				if (is_array($children)) {
					foreach ($children as $childId => $value) {
						$child = $hierarchy->getElement($childId);

						if ($child instanceof iUmiHierarchyElement) {
							$updater->update($child);
						}
					}
				}
			}

			$updater->update($this);
		}

		/** @inheritdoc */
		public function setIsUpdated($isUpdated = true) {
			parent::setIsUpdated($isUpdated);

			if ($isUpdated === false) {
				return $this;
			}

			$this->update_time = time();

			$hierarchy = umiHierarchy::getInstance();
			$hierarchy->addUpdatedElementId($this->getId());
			$parentId = $this->getParentId();

			if ($parentId) {
				$hierarchy->addUpdatedElementId($parentId);
			}
		}

		/** @inheritdoc */
		public function commit() {
			try {
				$this->getObject()
					->commit();
			} catch (coreException $exception) {
				umiExceptionHandler::report($exception);
			}

			parent::commit();
		}

		/**
		 * @inheritdoc
		 * @throws coreException
		 */
		public function getObjectTypeId() {
			return $this->getObject()
				->getTypeId();
		}

		/** @inheritdoc */
		public function getHierarchyType() {
			return umiHierarchyTypesCollection::getInstance()
				->getType($this->type_id);
		}

		/** @inheritdoc */
		public function getObjectId() {
			return $this->object_id;
		}

		/**
		 * Синоним метода getHierarchyType(). Этот метод является устаревшим.
		 * @return iUmiHierarchyType
		 */
		protected function getType() {
			return umiHierarchyTypesCollection::getInstance()
				->getType($this->getTypeId());
		}

		/** @inheritdoc */
		public function getModule() {
			return $this->getType()->getName();
		}

		/** @inheritdoc */
		public function getMethod() {
			return $this->getType()->getExt();
		}

		/** @inheritdoc */
		public function delete() {
			umiHierarchy::getInstance()->delElement($this->id);
		}

		public function __get($varName) {
			switch ($varName) {
				case 'id':
					return $this->id;
				case 'objectId':
					return $this->object_id;
				case 'name':
					return $this->getName();
				case 'altName':
					return $this->getAltName();
				case 'isActive':
					return $this->getIsActive();
				case 'isVisible':
					return $this->getIsVisible();
				case 'isDeleted':
					return $this->getIsDeleted();
				case 'xlink':
					return 'upage://' . $this->id;
				case 'link': {
					$hierarchy = umiHierarchy::getInstance();
					return $hierarchy->getPathById($this->id);
				}

				default:
					return $this->getValue($varName);
			}
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $prop имя свойства
		 * @return bool
		 */
		public function __isset($prop) {
			switch ($prop) {
				case 'id':
				case 'objectId':
				case 'name':
				case 'altName':
				case 'isActive':
				case 'isVisible':
				case 'isDeleted':
				case 'xlink':
				case 'link': {
					return true;
				}
				default : {
					return is_numeric($this->getFieldId($prop));
				}
			}
		}

		public function __set($varName, $value) {
			switch ($varName) {
				case 'id':
					throw new coreException('Object id could not be changed');
				case 'name':
					return $this->setName($value);
				case 'altName':
					return $this->setAltName($value);
				case 'isActive':
					return $this->setIsActive($value);
				case 'isVisible':
					return $this->setIsVisible($value);
				case 'isDeleted':
					return $this->setIsDeleted($value);

				default:
					return $this->setValue($varName, $value);
			}
		}

		/**
		 * @deprecated
		 * @see iUmiHierarchyElement::setDeleted()
		 * @param bool $isDeleted
		 */
		public function setIsDeleted($isDeleted = false) {
			$this->setDeleted($isDeleted);
		}

		/** @deprecated */
		public function getIsBroken() {
			return false;
		}

		/** @inheritdoc */
		public function getRel() {
			return $this->rel;
		}
	}
