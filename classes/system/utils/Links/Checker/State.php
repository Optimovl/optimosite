<?php

	namespace UmiCms\Classes\System\Utils\Links\Checker;

	/**
	 * Class State состояние проверки ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Checker
	 */
	class State implements iState {

		/** @var bool $isComplete статус завершенности проверки */
		private $isComplete;

		/** @var int $offset смещение результатов выборки */
		private $offset;

		/** @var int $limit ограничение на количество результатов выборки */
		private $limit;

		/** @inheritdoc */
		public function __construct(array $state) {
			if (!isset($state[iState::LIMIT_KEY])) {
				throw new \wrongParamException('Cant detect limit');
			}

			$limit = $state[iState::LIMIT_KEY];

			if (!isset($state[iState::OFFSET_KEY])) {
				throw new \wrongParamException('Cant detect offset');
			}

			$offset = $state[iState::OFFSET_KEY];

			if (!isset($state[iState::COMPLETE_KEY])) {
				throw new \wrongParamException('Cant detect complete status');
			}

			$completeStatus = $state[iState::COMPLETE_KEY];

			$this->setLimit($limit)
				->setOffset($offset)
				->setCompleteStatus($completeStatus);
		}

		/** @inheritdoc */
		public function setOffset($offset) {
			if (!is_numeric($offset)) {
				throw new \wrongParamException('Wrong offset given');
			}

			$this->offset = $offset;
			return $this;
		}

		/** @inheritdoc */
		public function getOffset() {
			if ($this->offset === null) {
				throw new \wrongParamException('You should set offset first');
			}

			return $this->offset;
		}

		/** @inheritdoc */
		public function setLimit($limit) {
			if (!is_numeric($limit)) {
				throw new \wrongParamException('Wrong limit given');
			}

			$this->limit = $limit;
			return $this;
		}

		/** @inheritdoc */
		public function getLimit() {
			if ($this->limit === null) {
				throw new \wrongParamException('You should set limit first');
			}

			return $this->limit;
		}

		/** @inheritdoc */
		public function setCompleteStatus($status) {
			if (!is_bool($status)) {
				throw new \wrongParamException('Wrong complete status given');
			}

			$this->isComplete = $status;
			return $this;
		}

		/** @inheritdoc */
		public function isComplete() {
			if ($this->isComplete === null) {
				throw new \wrongParamException('You should set is complete status first');
			}

			return $this->isComplete;
		}

		/** @inheritdoc */
		public function export() {
			return [
				iState::LIMIT_KEY => $this->getLimit(),
				iState::OFFSET_KEY => $this->getOffset(),
				iState::COMPLETE_KEY => $this->isComplete()
			];
		}
	}
