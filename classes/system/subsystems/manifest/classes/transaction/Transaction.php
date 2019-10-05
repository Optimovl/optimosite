<?php

	/** Класс транзакции. */
	class Transaction implements iTransaction, iStateFileWorker {

		use tStateFileWorker;
		use tReadinessWorker;

		/** @var string $name название */
		protected $name;

		/** @var iAction[] $actionList список команд */
		protected $actionList = [];

		/** @var iAtomicOperationCallback|null $callback обработчик хода выполнения манифеста */
		protected $callback;

		/** @inheritdoc */
		public function __construct($name) {
			$this->name = (string) $name;
		}

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function getTitle() {
			return getLabel('label-' . $this->getName());
		}

		/** @inheritdoc */
		public function addAction(iAction $action) {
			$this->actionList[$action->getName()] = $action;
		}

		/** @inheritdoc */
		public function execute() {
			$readyList = $this->getReadyList();

			foreach ($this->actionList as $action) {
				if (in_array($action->getName(), $readyList)) {
					continue;
				}

				$this->executeAction($action);

				if (!$action instanceof iReadinessWorker) {
					$readyList[] = $action->getName();
				} elseif ($action->isReady()) {
					$readyList[] = $action->getName();
				}

				$this->setReadyList($readyList);
			}

			if (umiCount($this->getReadyList()) == umiCount($this->actionList)) {
				$this->setIsReady();
				$this->resetState();
			}

			$this->saveState();
		}

		/** @inheritdoc */
		public function rollback() {
			$this->getCallback()->onBeforeRollback($this);

			/** @var string[] $reversedReadyList */
			$reversedReadyList = array_reverse($this->getReadyList());

			foreach ($reversedReadyList as $actionName) {
				try {
					$action = $this->actionList[$actionName];
					$this->getCallback()->onBeforeRollback($action);
					$action->rollback();
					$this->getCallback()->onAfterRollback($action);
				} catch (Exception $exception) {
					$this->getCallback()->onException($action, $exception);
				}
			}

			$this->resetState();
			$this->saveState();
			$this->getCallback()->onAfterRollback($this);
		}

		/** @inheritdoc */
		public function setCallback(iAtomicOperationCallback $callback) {
			$this->callback = $callback;
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
		 * Выполняет команду
		 * @param iAction $action команда
		 * @throws Exception
		 */
		protected function executeAction(iAction $action) {
			try {
				$this->getCallback()->onBeforeExecute($action);
				$action->execute();
				$this->getCallback()->onAfterExecute($action);
			} catch (Exception $exception) {
				$this->getCallback()->onException($action, $exception);
				$this->getCallback()->onBeforeRollback($action);
				$action->rollback();
				$this->getCallback()->onAfterRollback($action);
				throw $exception;
			}
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
	}
