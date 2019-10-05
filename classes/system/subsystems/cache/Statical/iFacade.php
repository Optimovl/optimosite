<?php

	namespace UmiCms\System\Cache\Statical;

	use UmiCms\System\Cache\Statical\Key\iGenerator;
	use UmiCms\System\Cache\Statical\Key\Validator\iFactory;
	use UmiCms\System\Cache\State\iValidator as RequestValidator;

	/**
	 * Интерфейс фасада над статическим кешем
	 * @package UmiCms\System\Cache\Statical
	 */
	interface iFacade {

		/** @const string DEBUG_SIGNATURE подпись кеша при отладке */
		const DEBUG_SIGNATURE = '<!-- Load from static cache -->';

		/**
		 * Конструктор
		 * @param \iConfiguration $config конфигурация
		 * @param RequestValidator $stateValidator валидатор состояния
		 * @param iFactory $keyValidatorFactory фабрика валидаторов ключей
		 * @param iGenerator $keyGenerator генератор ключей
		 * @param iStorage $storage хранилище
		 */
		public function __construct(
			\iConfiguration $config,
			RequestValidator $stateValidator,
			iFactory $keyValidatorFactory,
			iGenerator $keyGenerator,
			iStorage $storage
		);

		/**
		 * Сохраняет результат текущего запроса
		 * @param string $content результат текущего запроса
		 * @return bool
		 */
		public function save($content);

		/**
		 * Загружает результат текущего запроса
		 * @return string|bool
		 */
		public function load();

		/**
		 * Возвращает время жизни кеша
		 * @return int
		 */
		public function getTimeToLive();

		/**
		 * Определяет включено ли кеширование
		 * @return bool
		 */
		public function isEnabled();

		/**
		 * Включает статический кеш
		 * @return $this
		 */
		public function enable();

		/**
		 * Выключает статический кеш
		 * @return $this
		 */
		public function disable();

		/**
		 * Удаляет кеш для списка страниц
		 * @param int[] $idList список идентификаторов страниц
		 * @return bool
		 */
		public function deletePageListCache(array $idList);
	}
