<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Класс правила аутентификации пользователя по идентификатору
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	class UserId extends Rule {

		/** int $userId идентификатор пользователя */
		private $userId;

		/**
		 * Конструктор
		 * @param int $userId идентификатор пользователя
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct($userId, SelectorFactory $selectorFactory) {
			$this->userId = (int) $userId;
			$this->selectorFactory = $selectorFactory;
		}

		/** @inheritdoc */
		public function validate() {
			$userId = $this->getUserId();

			try {
				$queryBuilder = $this->getQueryBuilder();
				$queryBuilder->where('id')->equals($userId);
				$queryBuilder->where('is_activated')->equals(true);
				$queryBuilder->limit(0, 1);
				$queryResultSet = $queryBuilder->result();
			} catch (\Exception $e) {
				return false;
			}

			if (umiCount($queryResultSet) === 0) {
				return false;
			}

			$queryResultItem = array_shift($queryResultSet);
			return (int) $queryResultItem['id'];
		}

		/**
		 * Возвращает идентификатор пользователя
		 * @return int
		 */
		protected function getUserId() {
			return $this->userId;
		}
	}
