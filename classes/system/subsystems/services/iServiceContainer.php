<?php

	/** Интерфейс сервис контейнера */
	interface iServiceContainer {

		/**
		 * Конструктор
		 * @param array $rules правила инстанциирования сервисов
		 * @param array $parameters параметры для инстанцирования сервисов
		 */
		public function __construct(array $rules = [], array $parameters = []);

		/**
		 * Возвращает экземпляр сервиса по его имени
		 * @param string $name имя сервиса
		 * @return mixed
		 * @throws Exception
		 */
		public function get($name);

		/**
		 * Возвращает новый экземпляр сервиса по его имени
		 * @param string $name имя сервиса
		 * @return mixed
		 * @throws Exception
		 */
		public function getNew($name);

		/**
		 * Устанавливает сервис
		 * @param string $name имя сервиса
		 * @param object $service экземпляр сервиса
		 * @return $this
		 * @throws Exception
		 */
		public function set($name, $service);

		/**
		 * Существуют ли правила инстанциирования для сервиса
		 * @param string $name имя сервиса
		 * @return bool
		 */
		public function hasRules($name);

		/**
		 * Добавить правила инстанциирования сервисов
		 * @param array $rules
		 */
		public function addRules(array $rules);

		/**
		 * Добавить параметры инстанциирования сервисов
		 * @param array $params
		 */
		public function addParameters(array $params);

		/**
		 * Существуют ли параметры инстанциирования сервиса
		 * @param string $name имя сервиса
		 * @return bool
		 */
		public function hasParameter($name);

		/**
		 * Применяет правила инициализации к экземпляру сервиса
		 * @param string $name имя сервиса
		 * @param object $service экземпляр сервиса
		 * @return object
		 * @throws Exception
		 */
		public function initService($name, $service);
	}
