<?php

	/**
	 * Трейт сервиса
	 * @deprecated
	 */
	trait tUmiService {

		/** @var string $serviceName имя сервиса */
		private $serviceName;

		/**
		 * Конструктор
		 * @param string $serviceName имя сервиса
		 */
		public function __construct($serviceName) {
			$this->serviceName = $serviceName;
		}

		/**
		 * Возвращает имя сервиса
		 * @return string
		 */
		public function getServiceName() {
			return $this->serviceName;
		}
	}

?>
