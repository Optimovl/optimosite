<?php

	/** Интерфейс работника с картой констант */
	interface iUmiConstantMapInjector {

		/**
		 * Возвращает экземпляр класса с константами
		 * @return iUmiConstantMap
		 */
		public function getMap();

		/**
		 * Устанавливает экземпляр класса с константами
		 * @param iUmiConstantMap $mapInstance экземпляр класса
		 */
		public function setMap(iUmiConstantMap $mapInstance);
	}

