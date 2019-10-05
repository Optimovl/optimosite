<?php

	namespace UmiCms\System\Cache\Browser;

	use UmiCms\System\Cache\Browser\Engine\iFactory;
	use UmiCms\System\Cache\Browser\Engine\None;
	use UmiCms\System\Cache\State\iValidator;

	/**
	 * Класс фасада над браузерным кешированием.
	 * Кеширование заключается в отправке специальных заголовков и их валидацию.
	 * @package UmiCms\System\Cache\Browser
	 */
	class Facade implements iFacade {

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @var iFactory $engineFactory фабрика реализация браузерного кеширования */
		private $engineFactory;

		/** @var iValidator $stateValidator валидатор состояния */
		private $stateValidator;

		/** @inheritdoc */
		public function __construct(\iConfiguration $configuration, iFactory $engineFactory, iValidator $stateValidator) {
			$this->configuration = $configuration;
			$this->engineFactory = $engineFactory;
			$this->stateValidator = $stateValidator;
		}

		/** @inheritdoc */
		public function process() {
			if (!$this->getStateValidator()->isValid()) {
				return false;
			}

			$this->getEngine()
				->process();

			return true;
		}

		/** @inheritdoc */
		public function isEnabled() {
			return !($this->getEngine() instanceof None);
		}

		/** @inheritdoc */
		public function enable() {
			return $this->setEngine(iFactory::LAST_MODIFIED);
		}

		/** @inheritdoc */
		public function disable() {
			return $this->setEngine(iFactory::NONE);
		}

		/** @inheritdoc */
		public function setEngine($name) {
			try {
				$this->getEngineFactory()
					->create($name);
			} catch (\wrongParamException $exception) {
				\umiExceptionHandler::report($exception);
				$name = iFactory::NONE;
			}

			$configuration = $this->getConfiguration();
			$configuration->set('cache', 'browser.engine', $name);
			$configuration->save();

			return $this;
		}

		/** @inheritdoc */
		public function getEngineName() {
			return trimNameSpace(get_class($this->getEngine()));
		}

		/**
		 * Возвращает используемую реализацию кеширования
		 * @return iEngine
		 */
		private function getEngine() {
			$name = $this->getConfiguration()
				->get('cache', 'browser.engine');
			$factory = $this->getEngineFactory();

			try {
				return $factory->create($name);
			} catch (\wrongParamException $exception) {
				\umiExceptionHandler::report($exception);
				return $factory->create(iFactory::NONE);
			}
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает фабрику реализаций браузерного кеширования
		 * @return iFactory
		 */
		private function getEngineFactory() {
			return $this->engineFactory;
		}

		/**
		 * Возвращает валидатор состояния
		 * @return iValidator
		 */
		private function getStateValidator() {
			return $this->stateValidator;
		}
	}
