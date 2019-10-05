<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	/**
	 * Класс правила аутентификации временного пользователя по идентификатору
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	class FakeUser extends UserId {

		/** @inheritdoc */
		public function validate() {
			$userId = parent::validate();

			if ($userId) {
				return $userId;
			}

			return $this->getByCustomerId();
		}

		/**
		 * Возвращает ID временного пользователя
		 * @return bool|int
		 */
		private function getByCustomerId() {
			$userId = $this->getUserId();

			try {
				$queryBuilder = $this->getSelectorFactory()
					->createObjectTypeGuid('emarket-customer');
				$queryBuilder->where('id')->equals($userId);
				$queryBuilder->option('return')->value('id');
				$queryBuilder->option('no-length')->value(true);
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
	}
