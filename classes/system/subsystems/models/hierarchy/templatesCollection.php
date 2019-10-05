<?php

	use UmiCms\Service;

	/**
	 * Управляет шаблонами дизайна (класс template) в системе.
	 * Синглтон, экземпляр коллекции можно получить через статический метод getInstance()
	 */
	class templatesCollection extends singleton implements iSingleton, iTemplatesCollection {

		/**
		 * @var array список всех загруженных шаблонов дизайна
		 * [
		 *     id => template
		 * ]
		 */
		private $templates = [];

		/** Конструктор, при вызове загружает список шаблонов */
		protected function __construct() {
			$this->loadTemplates();
		}

		/**
		 * @inheritdoc
		 * @return iTemplatesCollection
		 */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function addTemplate(
			$filename,
			$title,
			$domainId = false,
			$langId = false,
			$isDefault = false
		) {
			$domains = Service::DomainCollection();
			$langs = Service::LanguageCollection();

			if (!$domains->isExists($domainId)) {
				if ($domains->getDefaultDomain()) {
					$domainId = $domains->getDefaultDomain()->getId();
				} else {
					return false;
				}
			}

			if (!$langs->isExists($langId)) {
				if ($langs->getDefaultLang()) {
					$langId = $langs->getDefaultLang()->getId();
				} else {
					return false;
				}
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction("Create template $title");

			try {
				$sql = 'INSERT INTO cms3_templates VALUES()';
				$connection->query($sql);
				$id = $connection->insertId();

				$template = new template($id);
				$template->setFilename($filename);
				$template->setTitle($title);
				$template->setDomainId($domainId);
				$template->setLangId($langId);
				$template->setIsDefault($isDefault);
				$template->commit();
				$template->update();
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();

			if ($isDefault) {
				$this->setDefaultTemplate($id);
			}

			$this->templates[$id] = $template;
			return $id;
		}

		/** @inheritdoc */
		public function setDefaultTemplate($templateId, $domainId = false, $langId = false) {
			if (!$this->isExists($templateId)) {
				return false;
			}

			$domainId = $domainId ?: Service::DomainDetector()->detectId();
			$langId = $langId ?: Service::LanguageDetector()->detectId();

			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction("Set default template $templateId");

			try {
				$templateList = $this->getTemplatesList($domainId, $langId);
				foreach ($templateList as $template) {
					$isDefault = ($templateId == $template->getId());
					$template->setIsDefault($isDefault);
					$template->commit();
				}
			} catch (Exception $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();
			return true;
		}

		/** @inheritdoc */
		public function delTemplate($id) {
			$id = (int) $id;

			if (!$this->isExists($id)) {
				return false;
			}

			unset($this->templates[$id]);
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->startTransaction("Delete template $id");

			try {
				$defaultTemplate = $this->getDefaultTemplate();

				if ($defaultTemplate instanceof iTemplate && $defaultTemplate->getId() != $id) {
					$updateQuery = "UPDATE cms3_hierarchy SET tpl_id = '{$defaultTemplate->getId()}' WHERE tpl_id='{$id}'";
					$connection->query($updateQuery);
				}

				$deleteQuery = "DELETE FROM cms3_templates WHERE id = '{$id}'";
				$connection->query($deleteQuery);
			} catch (databaseException $e) {
				$connection->rollbackTransaction();
				throw $e;
			}

			$connection->commitTransaction();

			return true;
		}

		/** @inheritdoc */
		public function getTemplatesList($domainId, $langId) {
			$templateList = [];

			foreach ($this->templates as $template) {
				if ($template->getDomainId() == $domainId && $template->getLangId() == $langId) {
					$templateList[] = $template;
				}
			}

			return $templateList;
		}

		/** @inheritdoc */
		public function getFullTemplatesList() {
			return $this->templates;
		}

		/** @inheritdoc */
		public function getDefaultTemplate($domainId = false, $langId = false) {
			$domainId = $domainId ?: Service::DomainDetector()->detectId();
			$langId = $langId ?: Service::LanguageDetector()->detectId();

			$templateList = $this->getTemplatesList($domainId, $langId);
			foreach ($templateList as $template) {
				if ($template->getIsDefault()) {
					return $template;
				}
			}

			if (count($templateList) > 0) {
				$firstTemplate = $templateList[0];
				$this->setDefaultTemplate($firstTemplate->getId(), $domainId, $langId);
				return $firstTemplate;
			}

			return false;
		}

		/** @inheritdoc */
		public function getCurrentTemplate() {
			$controller = cmsController::getInstance();
			$currentPage = umiHierarchy::getInstance()
				->getElement($controller->getCurrentElementId(), true);

			if ($currentPage instanceof iUmiHierarchyElement) {
				return $this->getTemplate($currentPage->getTplId());
			}

			$methodTemplateId = $this->getHierarchyTypeTemplate($controller->getCurrentModule(), $controller->getCurrentMethod());
			if ($methodTemplateId) {
				return $this->getTemplate($methodTemplateId);
			}

			return $this->getDefaultTemplate();
		}

		/** @inheritdoc */
		public function getHierarchyTypeTemplate($module, $method) {
			$id = null;

			if (class_exists($module) && method_exists($module, 'setupTemplate')) {
				$id = call_user_func([$module, 'setupTemplate'], $method);
			}

			if (!$this->isExists($id)) {
				$id = mainConfiguration::getInstance()
					->get('templates', "{$module}.{$method}");
			}

			return $this->isExists($id) ? $id : false;
		}

		/** @inheritdoc */
		public function getTemplate($id) {
			return $this->isExists($id) ? $this->templates[$id] : false;
		}

		/** @inheritdoc */
		public function isExists($id) {
			return array_key_exists((int) $id, $this->templates);
		}

		/** @inheritdoc */
		public function isExistsForSite($templateId, $domainId = false, $langId = false) {
			$domainId = $domainId ?: Service::DomainDetector()->detectId();
			$langId = $langId ?: Service::LanguageDetector()->detectId();

			foreach ($this->getTemplatesList($domainId, $langId) as $candidate) {
				if ($candidate->getId() == $templateId) {
					return true;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function getFirstByName($name) {
			$list = $this->getListByName($name);

			if (empty($list)) {
				return null;
			}

			return array_shift($list);
		}

		/** @inheritdoc */
		public function getListByName($name) {
			return array_filter(
				$this->getFullTemplatesList(),
				function (iTemplate $template) use ($name) {
					return $template->getName() == $name;
				}
			);
		}

		/**
		 * Загружает список всех шаблонов дизайна в системе из БД
		 * @return bool
		 */
		private function loadTemplates() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT id, name, filename, type, title, domain_id, lang_id, is_default FROM cms3_templates';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$id = $row[0];

				try {
					$template = new template($id, $row);
				} catch (privateException $e) {
					$e->unregister();
					continue;
				}

				$this->templates[$id] = $template;
			}

			return true;
		}

		/** @inheritdoc */
		public function clearCache() {
			$this->templates = [];
			$this->loadTemplates();
		}
	}
