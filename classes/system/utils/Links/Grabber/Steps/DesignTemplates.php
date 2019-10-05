<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber\Steps;

	/**
	 * Class DesignTemplates шаг сбора ссылок из шаблонов сайта
	 * @package UmiCms\Classes\System\Utils\Links\Grabber\Steps
	 */
	class DesignTemplates extends Step implements
		\iUmiTemplatesInjector,
		\iUmiDirectoriesInjector {

		use \tUmiTemplatesInjector;
		use \tUmiDirectoriesInjector;

		/** @var int $currentTemplateId идентификатор текущего обрабатываемого шаблона */
		private $currentTemplateId;

		/** @var array $templatesIds список идентификатор шаблонов, которые нужно обработать */
		private $templatesIds;

		/** @const string STEP_NAME имя шага */
		const STEP_NAME = 'DesignTemplates';

		/** @const string TEMPLATES_KEY ключ списка идентификаторов шаблонов */
		const TEMPLATES_KEY = 'templates';

		/** @const string CURRENT_KEY ключ идентификатора текущего обрабатываемого шаблона */
		const CURRENT_KEY = 'current';

		/** @inheritdoc */
		public function getName() {
			return self::STEP_NAME;
		}

		/** @inheritdoc */
		public function getStartStateStructure() {
			$templates = $this->getTemplatesCollection()
				->getFullTemplatesList();
			$templatesIds = [];
			$firstTemplateId = null;

			foreach ($templates as $template) {
				if ($firstTemplateId === null) {
					$firstTemplateId = (int) $template->getId();
				}

				$templatesIds[] = $template->getId();
			}

			return [
				self::TEMPLATES_KEY => $templatesIds,
				self::CURRENT_KEY => $firstTemplateId,
				self::COMPLETE_KEY => false,
			];
		}

		/** @inheritdoc */
		public function setState(array $state) {
			if (!isset($state[self::TEMPLATES_KEY])) {
				throw new \wrongParamException('Cant detect templates ids');
			}

			$templatesIds = $state[self::TEMPLATES_KEY];

			if (!isset($state[self::CURRENT_KEY])) {
				throw new \wrongParamException('Cant detect current template id');
			}

			$currentTemplateId = $state[self::CURRENT_KEY];

			if (!isset($state[self::COMPLETE_KEY])) {
				throw new \wrongParamException('Cant detect complete status');
			}

			$completeStatus = $state[self::COMPLETE_KEY];

			$this->setTemplatesIds($templatesIds)
				->setCurrentTemplateId($currentTemplateId)
				->setCompleteStatus($completeStatus);
			return $this;
		}

		/** @inheritdoc */
		public function grab() {
			if ($this->isComplete()) {
				return $this;
			}

			$currentTemplate = $this->getCurrentTemplate();
			$templatesDir = $currentTemplate->getTemplatesDirectory();
			$templatesExt = $currentTemplate->getFileExtension();

			$directoriesHandlerClass = get_class($this->getDirectoriesHandler());
			/** @var \iUmiDirectory $directoriesHandler */
			$directoriesHandler = new $directoriesHandlerClass($templatesDir);

			$filesOnlyTypeId = 1;
			$filesNamesMasc = '';
			$fileMustBeReadable = true;
			$templatesFiles = $directoriesHandler->getAllFiles($filesOnlyTypeId, $filesNamesMasc, $fileMustBeReadable);

			$templatesLinks = [];

			foreach ($templatesFiles as $filePath => $fileName) {
				if (!preg_match("/\.$templatesExt$/", $fileName)) {
					continue;
				}

				$templateContent = (string) file_get_contents($filePath);
				$templatesLinks[$filePath] = $this->getLinksFromHtmlText($templateContent);
			}

			$this->setResult($templatesLinks);
			$this->switchToNextTemplate();
			return $this;
		}

		/** @inheritdoc */
		public function getState() {
			return [
				self::TEMPLATES_KEY => (array) $this->getTemplatesIds(),
				self::CURRENT_KEY => (int) $this->getCurrentTemplateId(),
				self::COMPLETE_KEY => (bool) $this->isComplete(),
			];
		}

		/**
		 * Переключает следующий шаблон
		 * @return DesignTemplates
		 * @throws \Exception
		 */
		private function switchToNextTemplate() {
			$currentTemplate = $this->getCurrentTemplate();
			$currentTemplateId = $currentTemplate->getId();

			$templatesIds = $this->getTemplatesIds();
			$currentTemplateIdMatched = false;
			$nextTemplateId = null;

			foreach ($templatesIds as $templateId) {
				if ($currentTemplateId == $templateId) {
					$currentTemplateIdMatched = true;
					continue;
				}

				if ($currentTemplateIdMatched) {
					$nextTemplateId = $templateId;
					break;
				}
			}

			if (is_numeric($nextTemplateId)) {
				return $this->setCurrentTemplateId($nextTemplateId);
			}

			return $this->setCompleteStatus(true);
		}

		/**
		 * Устанавливает список идентификатор шаблонов, которые нужно обработать
		 * @param array $templatesIds список идентификатор шаблонов
		 * @return $this
		 * @throws \wrongParamException
		 */
		private function setTemplatesIds($templatesIds) {
			if (!is_array($templatesIds) || umiCount($templatesIds) == 0) {
				throw new \wrongParamException('Wrong templates ids given');
			}
			$this->templatesIds = $templatesIds;
			return $this;
		}

		/**
		 * Возвращает список идентификатор шаблонов
		 * @return array
		 * @throws \wrongParamException
		 */
		private function getTemplatesIds() {
			if ($this->templatesIds === null) {
				throw new \wrongParamException('You should set templates ids first');
			}

			return $this->templatesIds;
		}

		/**
		 * Устанавливает идентификатор текушего обрабатываемого шаблона
		 * @param int $currentTemplateId идентификатор текушего обрабатываемого шаблона
		 * @return $this
		 * @throws \wrongParamException
		 */
		private function setCurrentTemplateId($currentTemplateId) {
			if (!is_numeric($currentTemplateId)) {
				throw new \wrongParamException('Wrong current template given');
			}
			$this->currentTemplateId = $currentTemplateId;
			return $this;
		}

		/**
		 * Возвращает идентификатор текушего обрабатываемого шаблона
		 * @return int
		 * @throws \wrongParamException
		 */
		private function getCurrentTemplateId() {
			if ($this->currentTemplateId === null) {
				throw new \wrongParamException('You should set current template id first');
			}

			return $this->currentTemplateId;
		}

		/**
		 * Возвращает экземпляр текущего обрабатываемого шаблона
		 * @return \iTemplate|\iUmiEntinty
		 * @throws \Exception
		 */
		private function getCurrentTemplate() {
			$currentTemplateId = $this->getCurrentTemplateId();
			$template = $this->getTemplatesCollection()
				->getTemplate($currentTemplateId);

			if (!$template instanceof \iTemplate) {
				throw new \ExpectTemplateException('Cant get current template');
			}

			return $template;
		}
	}
