<?php

	use UmiCms\Service;

	/** Предоставляет доступ к свойствам шаблона дизайна */
	class template extends umiEntinty implements iTemplate {

		protected $store_type = 'template';

		private $name;

		private $filename;

		private $type;

		private $title;

		private $domain_id;

		private $lang_id;

		private $is_default;

		private $resourcesDirectory;

		private $templatesDirectory;

		private $filePath;

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function getFilename() {
			return $this->filename;
		}

		/** @inheritdoc */
		public function getResourcesDirectory($httpMode = false) {
			if ($httpMode) {
				return $this->resourcesDirectory ? '/templates/' . $this->name . '/' : '/';
			}

			return $this->resourcesDirectory;
		}

		/** @inheritdoc */
		public function getTemplatesDirectory() {
			return $this->templatesDirectory;
		}

		/** @inheritdoc */
		public function getFilePath() {
			return $this->filePath;
		}

		/** @inheritdoc */
		public function getType() {
			return $this->type;
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->title;
		}

		/** @inheritdoc */
		public function getDomainId() {
			return $this->domain_id;
		}

		/** @inheritdoc */
		public function getLangId() {
			return $this->lang_id;
		}

		/** @inheritdoc */
		public function getIsDefault() {
			return $this->is_default;
		}

		/** @inheritdoc */
		public function setName($name) {
			if ($this->getName() != $name) {
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setFilename($filename) {
			if ($this->getFilename() != $filename) {
				$this->filename = $filename;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setTitle($title) {
			if ($this->getTitle() != $title) {
				$this->title = $title;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setType($type) {
			if ($this->getType() != $type) {
				$this->type = $type;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function setDomainId($domainId) {
			if (!Service::DomainCollection()->isExists($domainId)) {
				return false;
			}

			if ($this->getDomainId() != $domainId) {
				$this->domain_id = (int) $domainId;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function setLangId($langId) {
			if (!Service::LanguageCollection()->isExists($langId)) {
				return false;
			}

			if ($this->getLangId() != $langId) {
				$this->lang_id = (int) $langId;
				$this->setIsUpdated();
			}

			return true;
		}

		/** @inheritdoc */
		public function setIsDefault($isDefault) {
			$isDefault = (bool) $isDefault;

			if ($this->getIsDefault() != $isDefault) {
				$this->is_default = $isDefault;
				$this->setIsUpdated();
			}
		}

		/** @inheritdoc */
		public function getFileExtension() {
			switch ($this->getType()) {
				case 'xslt' : {
					return 'xsl';
				}
				case 'php' : {
					return 'phtml';
				}
				case 'tpls' : {
					return 'tpl';
				}
				default : {
					throw new coreException('Unsupported type given: ' . $this->getType());
				}
			}
		}

		/** @inheritdoc */
		public function getConfigPath() {
			$name = $this->getName();

			if (is_string($name) && !empty($name)) {
				return $this->resourcesDirectory . 'config.ini';
			}

			return null;
		}

		/** @inheritdoc */
		public function getUsedPages($limit = 0, $offset = 0) {
			$limitString = '';
			$connection = ConnectionPool::getInstance()->getConnection();
			$escapedLimit = $connection->escape($limit);
			$escapedOffset = $connection->escape($offset);

			if (is_numeric($limit) && $limit > 0) {
				$limitString = "LIMIT ${escapedOffset}, ${escapedLimit}";
			}

			$sql = <<<QUERY
SELECT SQL_CALC_FOUND_ROWS
	     h.id,
       o.NAME
FROM   cms3_hierarchy h,
       cms3_objects o
WHERE  h.tpl_id = '{$this->id}'
       AND o.id = h.obj_id
       AND h.is_deleted = '0'
       AND h.domain_id = '{$this->domain_id}'
       ${limitString}
QUERY;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$res = [];

			foreach ($result as $row) {
				list($id, $name) = $row;
				$res[] = [$id, $name];
			}

			return $res;
		}

		/** @inheritdoc */
		public function getTotalUsedPages() {
			$query = <<<QUERY
SELECT count(`id`)
FROM   cms3_hierarchy h USE INDEX (PRIMARY)
WHERE  h.tpl_id = '{$this->id}'
       AND h.is_deleted = '0'
       AND h.domain_id = '{$this->domain_id}'
QUERY;
			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult($query);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$count = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$count = (int) array_shift($fetchResult);
			}

			return $count;
		}

		/** @inheritdoc */
		public function getRelatedPages($limit = 0, $offset = 0) {
			$relatedPages = $this->getUsedPages($limit, $offset);
			$relatedPagesIdList = [];

			/** @var array $pageData */
			foreach ($relatedPages as $pageData) {
				if (isset($pageData[0]) && is_numeric($pageData[0])) {
					$relatedPagesIdList[] = $pageData[0];
				}
			}
			$hierarchy = umiHierarchy::getInstance();
			return $hierarchy->loadElements($relatedPagesIdList);
		}

		/** @inheritdoc */
		public function setUsedPages($pages) {
			if ($pages === null) {
				return false;
			}

			$defaultTplId = templatesCollection::getInstance()
				->getDefaultTemplate($this->domain_id, $this->lang_id)
				->getId();
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
UPDATE cms3_hierarchy
SET tpl_id = '{$defaultTplId}'
WHERE tpl_id = '{$this->id}' AND is_deleted = '0' AND domain_id = '{$this->domain_id}'
SQL;
			$connection->query($sql);
			$hierarchy = umiHierarchy::getInstance();

			if (!is_array($pages)) {
				return false;
			}

			if (is_array($pages) && !empty($pages)) {
				foreach ($pages as $elementId) {
					$page = $hierarchy->getElement($elementId);
					if ($page instanceof iUmiHierarchyElement) {
						$page->setTplId($this->id);
						$page->commit();
						unset($page);
						$hierarchy->unloadElement($elementId);
					}
				}
			}

			return true;
		}

		/** @inheritdoc */
		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < 8) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT id, name, filename, type, title, domain_id, lang_id, is_default 
FROM cms3_templates WHERE id = $escapedId LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 8) {
				return false;
			}

			list($id, $name, $filename, $type, $title, $domainId, $langId, $isDefault) = $row;
			$this->name = (string) $name;
			$this->filename = $filename;
			$this->type = (string) $type;
			$this->title = $title;
			$this->domain_id = (int) $domainId;
			$this->lang_id = (int) $langId;
			$this->is_default = (bool) $isDefault;

			if (!empty($this->filename)) {
				// определяем полный путь к шаблону, а так же путь к директории с ресурсами
				$templateExt = pathinfo($this->filename, PATHINFO_EXTENSION);

				if ($this->type === '') {
					switch(mb_strtolower($templateExt)) {
						case 'xsl':
							$this->type = 'xslt';
							break;
						case 'tpl':
							$this->type = 'tpls';
							break;
						case 'phtml':
							$this->type = 'php';
							break;
						default:
							$this->type = $templateExt !== '' ? $templateExt : 'xslt';
					}
				}

				$config = mainConfiguration::getInstance();

				// TODO: refactoring
				if ($this->type == 'xslt') {
					$this->templatesDirectory = $templateDir = $config->includeParam('templates.xsl');
				} elseif ($this->type == 'tpls') {
					$this->templatesDirectory = $config->includeParam('templates.tpl');
					$templateDir = $this->templatesDirectory . 'content/';
				} elseif ($this->type == 'php') {
					$this->templatesDirectory = $config->includeParam('templates.php');
					$templateDir = $this->templatesDirectory . 'content/';
				} else {
					$this->templatesDirectory = $templateDir = $config->includeParam('templates.' . $this->type) . '/';
				}

				if ($this->name !== '') {
					$this->resourcesDirectory = CURRENT_WORKING_DIR . '/templates/' . $this->name . '/';
					$templateDir = $this->templatesDirectory = $this->resourcesDirectory . $this->type . '/';
					if ($this->type == 'tpls') {
						$templateDir = $this->templatesDirectory . 'content/';
					}
				}

				// mobile mode template
				if (Service::Request()->isMobile() && is_file($templateDir . 'mobile/' . $this->filename)) {
					$this->filePath = $templateDir . 'mobile/' . $this->filename;
				} else {
					// standart mode template
					$this->filePath = $templateDir . $this->filename;
				}
			}

			return true;
		}

		/**
		 * Сохраняет изменения в БД
		 * @return bool true, если не возникло ошибки
		 */
		protected function save() {
			$name = self::filterInputString($this->name);
			$filename = self::filterInputString($this->filename);
			$type = self::filterInputString($this->type);
			$title = self::filterInputString($this->title);
			$domainId = (int) $this->domain_id;
			$langId = (int) $this->lang_id;
			$isDefault = (int) $this->is_default;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = <<<SQL
UPDATE cms3_templates
SET name = '{$name}', filename = '{$filename}', type = '{$type}', title = '{$title}',
    domain_id = '{$domainId}', lang_id = '{$langId}', is_default = '{$isDefault}'
WHERE id = '{$this->id}'
SQL;
			$connection->query($sql);
			return true;
		}
	}
