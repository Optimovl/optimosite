<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber;

	use UmiCms\Classes\System\Utils\Links\Entity;
	use UmiCms\Classes\System\Utils\Links\Grabber\Steps;
	use UmiCms\Classes\System\Utils\Links\Injectors;

	/**
	 * Class Grabber собиратель ссылок.
	 * Разбирает шаблоны и данные системы и находит в них ссылки
	 *
	 * @example:
	 * $serviceContainer = ServiceContainerFactory::create();
	 * $grabber = $serviceContainer->get('linksGrabber');
	 * $isComplete = false;
	 *
	 * while (!$isComplete) {
	 *   $isComplete = $grabber->grab()
	 *     ->saveResult()
	 *     ->saveState()
	 *     ->isComplete();
	 *
	 *   if ($isComplete) {
	 *     $grabber->flushSavedState();
	 *   }
	 * }
	 *
	 * @package UmiCms\Classes\System\Utils\Links\Grabber
	 */
	class Grabber implements
		iGrabber,
		\iUmiService,
		\iUmiConfigInjector,
		\iUmiTemplatesInjector,
		\iUmiDirectoriesInjector,
		\iUmiDataBaseInjector,
		\iUmiPagesInjector,
		Injectors\iLinksCollection,
		Injectors\iLinksSourcesCollection {

		use \tUmiService;
		use \tUmiConfigInjector;
		use \tUmiTemplatesInjector;
		use \tUmiDirectoriesInjector;
		use \tUmiDataBaseInjector;
		use \tUmiPagesInjector;
		use Injectors\tLinksCollection;
		use Injectors\tLinksSourcesCollection;

		/** @var iState|null $state экземпляр класса состояния */
		private $state;

		/** @var Steps\iStep|null $step шаг сбора */
		private $step;

		/**
		 * @var array $result результат сбора
		 * @example [
		 *        'где найдено' => [
		 *            0 => 'адрес ссылки 1',
		 *            1 => 'адрес ссылки 2',
		 *        ]
		 * ]
		 */
		private $result;

		/** @var bool $isFirstLaunch определяет, что сбор ссылок начат сначала */
		private $isFirstLaunch = false;

		/** @const string STATE_FILE_NAME имя файла, в котором хранится состояние */
		const STATE_FILE_NAME = 'linksGrabberState';

		/**
		 * @const string CONFIG_CACHE_PATH_KEY имя секции и параметра, по которому хранится путь до директории кеша
		 * в конфигурации системы
		 */
		const CONFIG_CACHE_PATH_KEY = 'system.runtime-cache';

		/** @const string MYSQL_DUPLICATE_ENTRY_ERROR_TEXT сообщение об ошибке MySql о дублирование ключа */
		const MYSQL_DUPLICATE_ENTRY_ERROR_TEXT = 'Duplicate entry';

		/** @const string GRAB_STEP_SERVICE_NAME_PREFIX префикс имени сервиса шага сбора ссылок */
		const GRAB_STEP_SERVICE_NAME_PREFIX = 'UmiCms\Classes\System\Utils\Links\Grabber\Steps\\';

		/** @inheritdoc */
		public function grab() {
			if ($this->isComplete()) {
				return $this;
			}

			$step = $this->getCurrentStep();

			if ($this->isFirstLaunch()) {
				$this->deleteAllLinks();
				$this->setIsFirstLaunch(false);
			}

			$state = $this->getState();
			$stepState = $state->getStateOfStep($step);
			$step->setState($stepState);

			if ($step->isComplete()) {
				$this->switchToNextStep();
				$this->grab();
				return $this;
			}

			$step->grab();
			$state->setStateOfStep($step);
			$stepResult = $step->getResult();

			$this->setResult($stepResult);
			$this->switchToNextStep();
			return $this;
		}

		/** @inheritdoc */
		public function isComplete() {
			return $this->getState()
				->isComplete();
		}

		/** @inheritdoc */
		public function getStateName() {
			return $this->getState()
				->getCurrentStepName();
		}

		/** @inheritdoc */
		public function getResult() {
			if ($this->result === null) {
				throw new \RequiredPropertyHasNoValueException('You should grab first');
			}

			return $this->result;
		}

		/** @inheritdoc */
		public function setState(iState $state) {
			$this->state = $state;
			return $this;
		}

		/** @inheritdoc */
		public function saveState() {
			$stateFilePath = $this->getStateFilePath();
			$state = $this->getState()
				->export();

			if (!@file_put_contents($stateFilePath, json_encode($state))) {
				throw new \privateException('Cannot save state');
			}

			return $this;
		}

		/** @inheritdoc */
		public function flushSavedState() {
			$stateFilePath = $this->getStateFilePath();

			if (!@unlink($stateFilePath)) {
				throw new \privateException('Cannot flush state');
			}

			return $this;
		}

		/** @inheritdoc */
		public function saveResult() {

			foreach ($this->getResult() as $editLink => $links) {
				$this->createLinkEntities($editLink, $links);
				unset($this->result[$editLink]);
			}

			return $this;
		}

		/**
		 * Устанавливает, что сбор ссылок начат сначала
		 * @param bool $status
		 * @return iGrabber
		 */
		private function setIsFirstLaunch($status = true) {
			$this->isFirstLaunch = (bool) $status;
			return $this;
		}

		/**
		 * Проверяет, что сбор ссылок начат сначала и возвращает результат проверки
		 * @return bool
		 */
		private function isFirstLaunch() {
			return $this->isFirstLaunch;
		}

		/**
		 * Возвращает путь до файла, в котором хранится состояние сбора
		 * @return string
		 */
		private function getStateFilePath() {
			return $this->getConfiguration()
					->includeParam(self::CONFIG_CACHE_PATH_KEY) . self::STATE_FILE_NAME;
		}

		/**
		 * Создает сущности на основе собранных данных
		 * @param string $place место (url/адрес файла) где были получены ссылки
		 * @param array $addresses адреса ссылок
		 * @throws \Exception
		 * @throws \databaseException
		 */
		private function createLinkEntities($place, array $addresses) {
			$currentStepName = $this->getState()
				->getCurrentStepName();

			switch ($currentStepName) {
				case Steps\SitesUrls::STEP_NAME: {
					$this->createLinks($place, $addresses);
					break;
				}
				default : {
					$this->createLinksSources($place, $addresses);
				}
			}
		}

		/**
		 * Создает сущности ссылок
		 * @param string $place место url где были получены ссылки
		 * @param array $addresses адреса ссылок
		 * @throws \Exception
		 * @throws \RequiredPropertyHasNoValueException
		 * @throws \databaseException
		 */
		private function createLinks($place, array $addresses) {
			$linksCollection = $this->getLinksCollection();

			foreach ($addresses as $address) {
				if (!is_string($address) || trim($address) === '') {
					continue;
				}

				try {
					$linksCollection->createByAddressAndPlace($address, $place);
				} catch (\databaseException $e) {
					if (!contains($e->getMessage(), self::MYSQL_DUPLICATE_ENTRY_ERROR_TEXT)) {
						throw $e;
					}
				}
			}
		}

		/**
		 * Создает сущности источников ссылок
		 * @param string $place адрес (url/путь до шаблона) источника ссылок
		 * @param array $addresses адреса ссылок
		 * @throws \RequiredPropertyHasNoValueException
		 */
		private function createLinksSources($place, array $addresses) {
			$linksCollection = $this->getLinksCollection();
			$linksSourcesCollection = $this->getLinksSourcesCollection();

			foreach ($addresses as $address) {
				$link = $linksCollection->getByAddress($address);

				if (!$link instanceof Entity) {
					continue;
				}

				try {
					$linksSourcesCollection->createByLinkIdAndPlace($link->getId(), $place);
				} catch (\databaseException $e) {
					if (!contains($e->getMessage(), self::MYSQL_DUPLICATE_ENTRY_ERROR_TEXT)) {
						throw $e;
					}
				}
			}
		}

		/**
		 * Переключает сбор на следующий шаг
		 * @return Grabber
		 */
		private function switchToNextStep() {
			$step = $this->getCurrentStep();

			if (!$step->isComplete()) {
				return $this;
			}

			$nextStepName = $this->getNextStepName($step);
			$state = $this->getState();

			if (is_string($nextStepName)) {
				$state->setCurrentStepName($nextStepName);
				return $this->setStep(
					$this->loadStep()
				);
			}

			$state->setCompleteStatus(true);
			return $this;
		}

		/**
		 * Возвращает название следующего шага или null, если его нет
		 * @param Steps\iStep $currentStep текущий шаг
		 * @return string|null
		 */
		private function getNextStepName(Steps\iStep $currentStep) {
			$currentStepName = $currentStep->getName();
			$processedStepNameMatched = false;
			$state = $this->getState();
			$nextStepName = null;

			foreach ($state->getStepsNames() as $stepName) {
				if ($currentStepName == $stepName) {
					$processedStepNameMatched = true;
					continue;
				}

				if ($processedStepNameMatched) {
					$nextStepName = $stepName;
					break;
				}
			}

			return $nextStepName;
		}

		/**
		 * Возвращает текущий шаг сбора
		 * @return Steps\iStep
		 */
		private function getCurrentStep() {
			if ($this->step === null) {
				$this->setStep(
					$this->loadStep()
				);
			}

			return $this->step;
		}

		/**
		 * Устанавливает текущий шаг сбора
		 * @param Steps\iStep $step шаг сбора
		 * @return $this|Grabber
		 */
		private function setStep(Steps\iStep $step) {
			$this->step = $step;
			return $this;
		}

		/**
		 * Инициализирует текущий шаг сбора
		 * @param string|null $stepName имя шага, если не задано - возьмет текущий
		 * @return Steps\iStep
		 * @throws \privateException
		 */
		private function loadStep($stepName = null) {
			if ($stepName === null) {
				$state = $this->getState();
				$stepName = $state->getCurrentStepName();
			}

			$stepClass = self::GRAB_STEP_SERVICE_NAME_PREFIX . $stepName;

			if (!class_exists($stepClass)) {
				throw new \privateException(sprintf('Step %s not found', $stepClass));
			}

			/** @var Steps\iStep $step */
			$step = new $stepClass();

			switch ($step->getName()) {
				case Steps\DesignTemplates::STEP_NAME : {
					/** @var Steps\DesignTemplates $step */
					$step->setTemplatesCollection(
						$this->getTemplatesCollection()
					);
					$step->setDirectoriesHandler(
						$this->getDirectoriesHandler()
					);
					break;
				}
				case Steps\SitesUrls::STEP_NAME : {
					/** @var Steps\SitesUrls $step */
					$step->setPagesCollection(
						$this->getPagesCollection()
					);
					$step->setConnection($this->getConnection());
					break;
				}
				default : {
					/** @var Steps\ObjectsFields|Steps\ObjectsNames $step */
					$step->setConnection(
						$this->getConnection()
					);
				}
			}

			return $step;
		}

		/**
		 * Загружает состояние из реестра
		 * @return $this
		 * @throws \Exception
		 */
		private function loadState() {
			$stateFilePath = $this->getStateFilePath();
			$state = @file_get_contents($stateFilePath);
			$state = json_decode($state, true);

			if (!is_array($state)) {
				$this->setIsFirstLaunch();
				$state = $this->getStartStateStructure();
			}

			$state = new State($state);
			return $this->setState($state);
		}

		/**
		 * Возвращает структуру данных для инициализации начального состояния сбора
		 * @return array
		 */
		private function getStartStateStructure() {
			$structure = [
				iState::STEPS_KEY => [
					'SitesUrls' => [],
					'ObjectsFields' => [],
					'ObjectsNames' => [],
					'DesignTemplates' => []
				],
				iState::CURRENT_STEP_KEY => 'SitesUrls',
				iState::COMPLETE_KEY => false
			];

			foreach ($structure[iState::STEPS_KEY] as $stepName => $stepState) {
				$structure[iState::STEPS_KEY][$stepName] = $this->loadStep($stepName)
					->getStartStateStructure();
			}

			return $structure;
		}

		/**
		 * Возвращает состояние сбора ссылок
		 * @return iState
		 */
		private function getState() {
			if ($this->state === null) {
				$this->loadState();
			}

			return $this->state;
		}

		/**
		 * Устанавливает результат сбора ссылок
		 * @param array $result
		 * @return $this
		 */
		private function setResult(array $result) {
			if ($this->result === null) {
				$this->result = [];
			}

			$this->result = array_merge($this->result, $result);
			return $this;
		}

		/** Удаляет все ссылки */
		private function deleteAllLinks() {
			$this->getLinksCollection()
				->deleteAll();
		}
	}
