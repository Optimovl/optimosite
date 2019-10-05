<?php

	/**
	 * Интерфейс сервиса
	 * @deprecated
	 */
	interface iUmiService {

		/**
		 * Конструктор
		 * @param string $serviceName имя сервиса
		 */
		public function __construct($serviceName);

		/**
		 * Возвращает имя сервиса
		 * @return string
		 */
		public function getServiceName();
	}

?>
