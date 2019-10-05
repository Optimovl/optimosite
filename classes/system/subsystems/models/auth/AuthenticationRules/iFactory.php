<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Auth\PasswordHash\iAlgorithm;
	use UmiCms\System\Selector\iFactory as SelectorFactory;
	use UmiCms\System\Protection\iHashComparator;

	/**
	 * Интерфейс фабрики правил аутентификации
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param iAlgorithm $algorithm алгоритм хеширования паролей
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 * @param iHashComparator $hashComparator сравнитель хэшей
		 */
		public function __construct(iAlgorithm $algorithm, SelectorFactory $selectorFactory,
			iHashComparator $hashComparator);

		/**
		 * Создает правило аутентификации пользователя по логину и паролю
		 * @param string $login логин
		 * @param string $password пароль
		 * @return iRule
		 */
		public function createByLoginAndPassword($login, $password);

		/**
		 * Создает правило аутентификации пользователя по логину и хешу пароля
		 * @param string $login логин
		 * @param string $hash хеш
		 * @return iRule
		 */
		public function createByLoginAndHash($login, $hash);

		/**
		 * Создает правило аутентификации пользователя по коду активации
		 * @param string $activationCode код активации
		 * @return iRule
		 */
		public function createByActivationCode($activationCode);

		/**
		 * Создает правило аутентификации пользователя по логину и названию провайдера данных пользователя (социальной сети)
		 * @param string $login логин
		 * @param string $provider название провайдера данных пользователя (социальной сети)
		 * @return iRule
		 */
		public function createByLoginAndProvider($login, $provider);

		/**
		 * Создает правило аутентификации пользователя по его идентификатору
		 * @param int $userId идентификатор пользователя
		 * @return iRule
		 */
		public function createByUserId($userId);

		/**
		 * Создает правило аутентификации временного пользователя по его идентификатору
		 * @param int $userId идентификатор временного пользователя
		 * @return iRule
		 */
		public function createByFakeUser($userId);
	}
