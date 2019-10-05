<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Selector\iFactory as SelectorFactory;
	use UmiCms\System\Protection\iHashComparator;

	/**
	 * Класс правила аутентификации пользователя по логину и хешу пароля
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	class LoginAndHash extends Rule {

		/** @var string $login логин */
		private $login;

		/** @var string $hash хеш пароля */
		private $hash;

		/**
		 * Конструктор
		 * @param string $login логин
		 * @param string $hash хеш пароля
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 * @param iHashComparator $hashComparator сравнитель хэшей
		 */
		public function __construct($login, $hash, SelectorFactory $selectorFactory, iHashComparator $hashComparator) {
			$this->login = (string) $login;
			$this->hash = (string) $hash;
			$this->selectorFactory = $selectorFactory;
			$this->hashComparator = $hashComparator;
		}

		/** @inheritdoc */
		public function validate() {
			$login = $this->getLogin();

			try {
				$queryBuilder = $this->getQueryBuilder();
				$queryBuilder->option('return')->value(['id', 'password']);
				$queryBuilder->option('or-mode')->fields('login', 'e-mail');
				$queryBuilder->where('login')->equals($login);
				$queryBuilder->where('e-mail')->equals($login);
				$queryBuilder->where('is_activated')->equals(true);
				$queryBuilder->limit(0, 1);
				$queryResultSet = $queryBuilder->result();
			} catch (\Exception $e) {
				return false;
			}

			if (umiCount($queryResultSet) === 0) {
				return false;
			}

			$correctHash = (string) $queryResultSet[0]['password'];

			$hashComparator = $this->getHashComparator();
			if (!$hashComparator->equals($correctHash, $this->getHash())) {
				return false;
			}

			return (int) $queryResultSet[0]['id'];
		}

		/**
		 * Возвращает логин
		 * @return string
		 */
		private function getLogin() {
			return $this->login;
		}

		/**
		 * Возвращает хеш пароля
		 * @return string
		 */
		private function getHash() {
			return $this->hash;
		}
	}
