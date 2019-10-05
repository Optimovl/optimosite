<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Класс правила аутентификации пользователя по логину и названию провайдера данных пользователя (социальной сети)
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	class LoginAndProvider extends Rule {

		/** @var string $login логин */
		private $login;

		/** string $provider название провайдера данных пользователя (социальной сети) */
		private $provider;

		/**
		 * Конструктор
		 * @param string $login логин
		 * @param string $provider название провайдера данных пользователя (социальной сети)
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct($login, $provider, SelectorFactory $selectorFactory) {
			$this->login = (string) $login;
			$this->provider = (string) $provider;
			$this->selectorFactory = $selectorFactory;
		}

		/** @inheritdoc */
		public function validate() {
			$login = $this->getLogin();
			$provider = $this->getProvider();

			try {
				$queryBuilder = $this->getQueryBuilder();
				$queryBuilder->where('login')->equals($login);
				$queryBuilder->where('loginza')->equals($provider);
				$queryBuilder->where('is_activated')->equals(true);
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
		 * Возвращает логин
		 * @return string
		 */
		private function getLogin() {
			return $this->login;
		}

		/**
		 * Возвращает название провайдера данных пользователя (социальной сети)
		 * @return string
		 */
		private function getProvider() {
			return $this->provider;
		}
	}
