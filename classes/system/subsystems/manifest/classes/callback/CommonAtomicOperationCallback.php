<?php

	/** Обработчика хода выполнения атомарного действия */
	class CommonAtomicOperationCallback implements iAtomicOperationCallback {

		/** @var array $log журнал */
		private $log = [];

		/** @inheritdoc */
		public function onBeforeExecute(iAtomicOperation $operation) {
			switch (true) {
				case $operation instanceof iManifest : {
					$message = getLabel('manifest-start');
					break;
				}
				case $operation instanceof iTransaction : {
					$message = getLabel('manifest-transaction-start') . $this->prepareTitle($operation->getTitle());
					break;
				}
				case $operation instanceof iAction : {
					$message = getLabel('manifest-action-start') . $this->prepareTitle($operation->getTitle());
					break;
				}
				default : {
					$message = 'Unknown execute start';
				}
			}

			$this->log($message);
		}

		/** @inheritdoc */
		public function onAfterExecute(iAtomicOperation $operation) {
			switch (true) {
				case $operation instanceof iManifest : {
					$message = getLabel('manifest-finish');
					break;
				}
				case $operation instanceof iTransaction : {
					$message = getLabel('manifest-transaction-finish') . $this->prepareTitle($operation->getTitle());
					break;
				}
				case $operation instanceof iAction : {
					$message = getLabel('manifest-action-finish') . $this->prepareTitle($operation->getTitle());
					break;
				}
				default : {
					$message = 'Unknown execute finish';
				}
			}

			$this->log($message);
		}

		/** @inheritdoc */
		public function onBeforeRollback(iAtomicOperation $operation) {
			switch (true) {
				case $operation instanceof iManifest : {
					$message = getLabel('manifest-rollback-start');
					break;
				}
				case $operation instanceof iTransaction : {
					$message = getLabel('transaction-rollback-start') . $this->prepareTitle($operation->getTitle());
					break;
				}
				case $operation instanceof iAction : {
					$message = getLabel('action-rollback-start') . $this->prepareTitle($operation->getTitle());
					break;
				}
				default : {
					$message = 'Unknown rollback start';
				}
			}

			$this->log($message, true);
		}

		/** @inheritdoc */
		public function onAfterRollback(iAtomicOperation $operation) {
			switch (true) {
				case $operation instanceof iManifest : {
					$message = getLabel('manifest-rollback-end');
					break;
				}
				case $operation instanceof iTransaction : {
					$message = getLabel('transaction-rollback-end') . $this->prepareTitle($operation->getTitle());
					break;
				}
				case $operation instanceof iAction : {
					$message = getLabel('action-rollback-end') . $this->prepareTitle($operation->getTitle());
					break;
				}
				default : {
					$message = 'Unknown rollback finish';
				}
			}

			$this->log($message, true);
		}

		/** @inheritdoc */
		public function onException(iAtomicOperation $operation, Exception $exception) {
			$this->log(getLabel('manifest-exception') . ': ' . $exception->getMessage(), true);
		}

		/**
		 * Возвращает журнал
		 * @return array
		 */
		public function getLog() {
			return $this->log;
		}

		/**
		 * Выводит сообщение лога
		 * @param string $message сообщение лога
		 * @param bool $isError сообщение повествует об ошибке
		 */
		protected function log($message, $isError = false) {
			$this->log[] = $message;
		}

		/**
		 * Подготавливает название операциии (транзакции или команды) к выводу в лог
		 * @param string $title название операциии (транзакции или команды)
		 * @return string
		 */
		protected function prepareTitle($title) {
			return sprintf(' "%s"', $title);
		}
	}
