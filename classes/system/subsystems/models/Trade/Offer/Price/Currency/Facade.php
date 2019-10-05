<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	use UmiCms\System\Trade\Offer\Price\iCurrency;
	use UmiCms\System\Trade\Offer\Price\Currency\Favorite\iFacade as iFavoriteCurrencyFacade;

	/**
	 * Класс фасада валют
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	class Facade implements iFacade {

		/** @var iRepository $repository репозиторий валют */
		private $repository;

		/** @var iCollection $collection коллекция валют */
		private $collection;

		/** @var \iConfiguration $configuration конфигурация системы */
		private $configuration;

		/** @var iCalculator $calculator калькулятор валют */
		private $calculator;

		/** @var iFavoriteCurrencyFacade $favoriteCurrencyFacade фасад любимых валют */
		private $favoriteCurrencyFacade;

		/** @inheritdoc */
		public function __construct(
			iRepository $repository,
			iCollection $collection,
			\iConfiguration $configuration,
			iCalculator $calculator,
			iFavoriteCurrencyFacade $favoriteCurrencyFacade
		) {
			$this->repository = $repository;
			$this->collection = $collection;
			$this->configuration = $configuration;
			$this->calculator = $calculator;
			$this->favoriteCurrencyFacade = $favoriteCurrencyFacade;
		}

		/** @inheritdoc */
		public function getList() {
			return $this->getCollection()
				->getAll();
		}

		/** @inheritdoc */
		public function getDefault() {
			$defaultCode = (string) $this->getConfiguration()
				->get('system', 'default-currency');

			if (!$defaultCode) {
				throw new \coreException('Default currency is not defined (system.default-currency)');
			}

			return $this->getByCode($defaultCode);
		}

		/** @inheritdoc */
		public function setDefault(iCurrency $currency) {
			$configuration = $this->getConfiguration();
			$configuration->set('system', 'default-currency', $currency->getCode());
			$configuration->save();
			return $this;
		}

		/** @inheritdoc */
		public function isDefault(iCurrency $currency) {
			return $this->getDefault()->getId() === $currency->getId();
		}

		/** @inheritdoc */
		public function getCurrent() {
			$id = $this->getUserCurrencyId();
			$currency = $this->getRepository()
				->get($id);

			if ($currency instanceof iCurrency) {
				return $currency;
			}

			return $this->getDefault();
		}

		/** @inheritdoc */
		public function setCurrent(iCurrency $currency) {
			return $this->setUserCurrencyId($currency->getId());
		}

		/** @inheritdoc */
		public function isCurrent(iCurrency $currency) {
			return $this->getCurrent()->getId() === $currency->getId();
		}

		/** @inheritdoc */
		public function getByCode($code) {
			return $this->getCollection()
				->getBy(iCurrency::CODE, $code);
		}

		/** @inheritdoc */
		public function get($id) {
			return $this->getCollection()
				->getBy(iCurrency::ID, $id);
		}

		/** @inheritdoc */
		public function save(iCurrency $currency) {
			$this->getRepository()
				->save($currency);
			return $this;
		}

		/** @inheritdoc */
		public function calculate($price, iCurrency $from = null, iCurrency $to = null) {
			$from = $from ?: $this->getDefault();
			$to = $to ?: $this->getCurrent();

			return $this->getCalculator()
				->calculate($price, $from, $to);
		}

		/** @inheritdoc */
		public function reload() {
			$collection = $this->getCollection();

			foreach ($collection->getAll() as $currency) {
				$collection->unload($currency->getId());
			}

			return $this->fillCollection($collection);
		}

		/**
		 * Возвращает идентификатор валюты, предпочитаемой текущим пользователем
		 * @return int|null
		 */
		private function getUserCurrencyId() {
			return $this->getFavoriteCurrencyFacade()->getId();
		}

		/**
		 * Устанавливает предпочитаемую валюту текущего пользователя
		 * @param int $id идентификатор валюты
		 * @return $this
		 */
		private function setUserCurrencyId($id) {
			$this->getFavoriteCurrencyFacade()->setId($id);
			return $this;
		}

		/**
		 * Возвращает репозиторий валют
		 * @return iRepository
		 */
		private function getRepository() {
			return $this->repository;
		}

		/**
		 * Возвращает коллекцию валют
		 * @return iCollection
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		private function getCollection() {
			$collection = $this->collection;
			$currencyList = $collection->getAll();

			if (empty($currencyList)) {
				$this->fillCollection($collection);
			}

			return $this->collection;
		}

		/**
		 * Заполняет коллекцию валютами
		 * @param iCollection $collection коллекция валют
		 * @return $this
		 * @throws \selectorException
		 * @throws \wrongParamException
		 */
		private function fillCollection(iCollection $collection) {
			$currencyList = $this->getRepository()
				->getAll();
			$collection->loadList($currencyList);
			return $this;
		}

		/**
		 * Возвращает конфигурация системы
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает калькулятор валют
		 * @return iCalculator
		 */
		private function getCalculator() {
			return $this->calculator;
		}

		/**
		 * Возвращает фасад любимых валют
		 * @return iFavoriteCurrencyFacade
		 */
		private function getFavoriteCurrencyFacade() {
			return $this->favoriteCurrencyFacade;
		}
	}

