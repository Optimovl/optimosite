<?php

	/** Интерфейс фабрики контейнеров сервисов */
	interface iServiceContainerFactory {

		/** @const string DEFAULT_CONTAINER_TYPE тип контейнера по умолчанию */
		const DEFAULT_CONTAINER_TYPE = 'default';

		/**
		 * Создает контейнер сервисов заданного типа
		 * @param string $type тип создаваемого контейнера
		 * @param array $rules список прави инициализации сервисов
		 * @param array $parameters список параметров инициализации сервисов
		 * @return iServiceContainer
		 */
		public static function create($type = self::DEFAULT_CONTAINER_TYPE, array $rules = [], array $parameters = []);
	}
