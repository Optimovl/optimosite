<?php

	use UmiCms\Service;

	/** Класс манифеста */
	class Manifest implements iManifest, iStateFileWorker {

		use tStateFileWorker;
		use tReadinessWorker;

		/** @var iBaseXmlConfig $config конфигурация манифеста */
		protected $config;

		/** @var iAtomicOperationCallback|null $callback обработчик хода выполнения манифеста */
		protected $callback;

		/** @var iTransaction[] $transactionList список транзакций, которые составляют собой манифест */
		protected $transactionList = [];

		/** @var array $params параметры выполнения манифеста */
		protected $params = [];

		/** @var iManifestSource $source источник манифеста */
		protected $source;

		/** @inheritdoc */
		public function __construct(iBaseXmlConfig $config, iManifestSource $source, array $params = []) {
			$this->config = $config;
			$this->source = $source;
			$this->params = $params;
		}

		/** @inheritdoc */
		public function setCallback(iAtomicOperationCallback $callback) {
			$this->callback = $callback;
		}

		/** @inheritdoc */
		public function execute() {
			$this->getCallback()->onBeforeExecute($this);

			$readyList = $this->getReadyList();

			foreach ($this->transactionList as $transaction) {

				if (in_array($transaction->getName(), $readyList)) {
					continue;
				}

				try {
					$this->executeTransaction($transaction);
				} catch (Exception $exception) {
					$this->getCallback()->onException($this, $exception);
					$this->rollback();
					throw $exception;
				}

				if ($transaction->isReady()) {
					$readyList[] = $transaction->getName();
				}
			}

			$this->setReadyList($readyList);

			if (umiCount($this->getReadyList()) == umiCount($this->transactionList)) {
				$this->setIsReady();
				$this->resetState();
			}

			$this->saveState();
			$this->getCallback()->onAfterExecute($this);
			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			$this->getCallback()->onBeforeRollback($this);

			/** @var string[] $reversedReadyList */
			$reversedReadyList = array_reverse($this->getReadyList());

			foreach ($reversedReadyList as $transactionName) {
				try {
					$transaction = $this->transactionList[$transactionName];
					$transaction->rollback();
				} catch (Exception $exception) {
					$this->getCallback()->onException($this, $exception);
				}
			}

			$this->resetState();
			$this->saveState();

			$this->getCallback()->onAfterRollback($this);
		}

		/** @inheritdoc */
		public function getName() {
			return $this->config->getName();
		}

		/** @inheritdoc */
		public function loadTransactions() {
			$nodeProperties = [
				'name' => '@name'
			];

			$transactions = $this->config->getList('//transaction', $nodeProperties);
			$transactionFactory = Service::TransactionFactory();

			foreach ($transactions as $info) {
				$name = $info['name'];

				$transaction = $transactionFactory->create($name);
				$transaction->setCallback($this->getCallback());

				$this->loadActions($transaction);
				$this->transactionList[$transaction->getName()] = $transaction;
			}
		}

		/** @inheritdoc */
		public function getLog() {
			return $this->getCallback()
				->getLog();
		}

		/**
		 * Возвращает список имен транзакций, которые были выполнены
		 * @return array
		 */
		protected function getReadyList() {
			$readyList = $this->getStatePart('ready');
			return is_array($readyList) ? $readyList : [];
		}

		/**
		 * Устанавливает список имен транзакций, которые были выполнены
		 * @param array $readyList список имен транзакций
		 * @return $this
		 */
		protected function setReadyList(array $readyList) {
			return $this->setStatePart('ready', $readyList);
		}

		/**
		 * Возвращает параметры
		 * @return array
		 *
		 * [
		 *      'name' => 'value'
		 * ]
		 */
		protected function getParams() {
			return $this->params;
		}

		/**
		 * Выполняет транзакцию
		 * @param iTransaction $transaction транзакция
		 * @throws Exception
		 */
		protected function executeTransaction(iTransaction $transaction) {
			try {
				$this->getCallback()->onBeforeExecute($transaction);
				$transaction->execute();
				$this->getCallback()->onAfterExecute($transaction);
			} catch (Exception $exception) {
				$this->getCallback()->onException($transaction, $exception);
				$transaction->rollback();
				throw $exception;
			}
		}

		/**
		 * Загружает список команд транзакции
		 * @param iTransaction $transaction транзакция
		 * @throws Exception
		 */
		protected function loadActions(iTransaction $transaction) {
			$nodeProperties = [
				'name' => '@name',
				'params' => '+params'
			];

			$actions = $this->config->getList(
				"//transaction[@name = '{$transaction->getName()}']/action",
				$nodeProperties
			);

			$actionFactory = Service::ActionFactory();
			$paramValueList = $this->getParams();

			foreach ($actions as $action) {
				$name = $action['name'];
				$placeholderList = $action['params'];
				$paramList = $this->replacePlaceholders($placeholderList, $paramValueList);

				try {
					$actionPath = $this->getSource()
						->getActionFilePath($name);

					$action = $actionFactory->create($name, $paramList, $actionPath);
					$action->setCallback($this->getCallback());

					$transaction->addAction($action);
				} catch (Exception $e) {
					$this->getCallback()->onException($transaction, $e);
					throw $e;
				}
			}
		}

		/**
		 * Заменяет плесхолдеры на заданные значения.
		 * Форматы даты (@see Manifest::replaceDateFormat()) заменяются на даты.
		 *
		 * @param array $placeholderList список плейсхолдеров
		 *
		 * [
		 *      "foo" => "{bar}",
		 *      "baz" => "{Y-m-d-H-i-s}"
		 * ]
		 *
		 * @param array $valueList список значений
		 *
		 * [
		 *      "foo" => 100500
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      "foo" => 100500,
		 *      "baz" => "2017-06-21-13-20-50"
		 * ]
		 */
		protected function replacePlaceholders(array $placeholderList, array $valueList) {
			$params = [];

			foreach ($placeholderList as $index => $placeholder) {

				foreach ($valueList as $name => $value) {
					$placeholder = str_replace('{' . $name . '}', $value, $placeholder);
				}

				$value = json_decode($placeholder, true);

				if ($value !== null) {
					$params[$index] = $value;
					continue;
				}

				$params[$index] = $this->replaceDateFormat($placeholder);
			}

			return $params;
		}

		/**
		 * Заменяет в строке вхождения формата даты (@see date()) на дату в этом формате:
		 *
		 * "{Y-m-d-H-i-s}" => "2017-06-21-13-20-50"
		 *
		 * @param string $haystack строка
		 * @return string
		 */
		protected function replaceDateFormat($haystack) {
			$result = $haystack;

			if (preg_match_all("/\{([^\}]+)\}/", $result, $out)) {
				foreach ($out[1] as $pattern) {
					$result = str_replace('{' . $pattern . '}', date($pattern), $result);
				}
			}

			return $result;
		}

		/**
		 * Возвращает обработчик хода выполнения манифеста
		 * @return iAtomicOperationCallback
		 * @throws Exception
		 */
		protected function getCallback() {
			if (!$this->callback instanceof iAtomicOperationCallback) {
				throw new Exception('You should set iAtomicOperationCallback before use it');
			}

			return $this->callback;
		}

		/**
		 * Возвращает источник манифеста
		 * @return iManifestSource
		 */
		protected function getSource() {
			return $this->source;
		}
	}
