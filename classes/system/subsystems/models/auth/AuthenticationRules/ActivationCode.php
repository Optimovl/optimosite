<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Класс правила аутентификации пользователя по коду активации
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	class ActivationCode extends Rule {

		/** @var string $activationCode код активации */
		private $activationCode;

		/**
		 * Конструктор
		 * @param string $activationCode код активации
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct($activationCode, SelectorFactory $selectorFactory) {
			$this->activationCode = (string) $activationCode;
			$this->selectorFactory = $selectorFactory;
		}

		/** @inheritdoc */
		public function validate() {
			$activationCode = $this->getActivationCode();

			try {
				$queryBuilder = $this->getQueryBuilder();
				$queryBuilder->where('activate_code')->equals($activationCode);
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
		 * Возвращает код активации пользователя
		 * @return string
		 */
		private function getActivationCode() {
			return $this->activationCode;
		}
	}
