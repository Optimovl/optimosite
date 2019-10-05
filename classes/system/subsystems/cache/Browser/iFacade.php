<?php

	namespace UmiCms\System\Cache\Browser;

	use UmiCms\System\Cache\Browser\Engine\iFactory;
	use UmiCms\System\Cache\State\iValidator;

	/**
	 * Интерфейс фасада над браузерным кешированием
	 * @package UmiCms\System\Cache\Browser
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration конфигурация
		 * @param iFactory $engineFactory фабрика реализация браузерного кеширования
		 * @param iValidator $stateValidator валидатор состояния
		 */
		public function __construct(\iConfiguration $configuration, iFactory $engineFactory, iValidator $stateValidator);

		/**
		 * Запускает кеширование.
		 * Может привести к завершению обработки текущего запроса.
		 * @return bool
		 */
		public function process();

		/**
		 * Определяет включено ли кеширование
		 * @return bool
		 */
		public function isEnabled();

		/**
		 * Включает кеширование
		 * @return $this
		 */
		public function enable();

		/**
		 * Отключает кеширование
		 * @return $this
		 */
		public function disable();

		/**
		 * Устанавливает используемую реализацию кеширования
		 * @param string $name имя реализации
		 * @return $this
		 */
		public function setEngine($name);

		/**
		 * Возвращает имя используемой реализации кеширования
		 * @return string
		 */
		public function getEngineName();
	}
