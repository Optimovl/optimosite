<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Auth\PasswordHash\iAlgorithm;
	use UmiCms\System\Selector\iFactory as SelectorFactory;
	use UmiCms\System\Protection\iHashComparator;

	/**
	 * Класс абстрактного правила аутентификации пользователя
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	abstract class Rule implements iRule {

		/** @var iAlgorithm $hashAlgorithm алгоритм хеширования паролей */
		protected $hashAlgorithm;

		/** @var SelectorFactory $selectorFactory фабрика селекторов */
		protected $selectorFactory;

		/** @var iHashComparator $hashComparator сравнитель хэшей */
		protected $hashComparator;

		/** @inheritdoc */
		abstract public function validate();

		/**
		 * Возвращает фабрику селекторов
		 * @return SelectorFactory
		 */
		protected function getSelectorFactory() {
			return $this->selectorFactory;
		}

		/**
		 * Возвращает сравнителя хэшей
		 * @return iHashComparator
		 */
		protected function getHashComparator() {
			return $this->hashComparator;
		}

		/**
		 * Возвращает конструктор запросов, сконфигурированный для выборок пользователей
		 * @return \selector
		 * @throws \selectorException
		 */
		protected function getQueryBuilder() {
			$queryBuilder = $this->getSelectorFactory()
				->createObjectTypeName('users', 'user');
			$queryBuilder->option('return')->value('id');
			$queryBuilder->option('no-length')->value(true);
			return $queryBuilder;
		}
	}
