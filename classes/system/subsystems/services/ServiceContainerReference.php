<?php

	/**
	 * Параметр инициализации сервиса - сервис контейнер.
	 * Зависимость от сервис контейнера очень плоха.
	 * Используйте только, если классу необходимо динамический получать различные сервисы.
	 * Если сервисы известны заранее, то необходимо использовать ServiceReference.
	 */
	class ServiceContainerReference extends AbstractReference {

		/** Конструктор */
		public function __construct() {
			parent::__construct(__CLASS__);
		}
	}
