<?php

	interface iSingleton {

		/**
		 * Возвращает экземпляр коллекции
		 * @param string|null $className имя возвращаемого класса
		 * @return iSingleton
		 */
		public static function getInstance($className = null);
	}
