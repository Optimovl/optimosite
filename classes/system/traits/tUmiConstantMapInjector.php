<?php

	/** Трейт работника с картой констант */
	trait tUmiConstantMapInjector {

		/** @var string $map класс с константами */
		private $map;

		/**
		 * Возвращает экземпляр класса с константами
		 * @return iUmiConstantMap
		 * @throws Exception
		 */
		public function getMap() {
			if (!$this->map instanceof iUmiConstantMap) {
				throw new RequiredPropertyHasNoValueException('You should set iUmiConstantMap first');
			}

			return $this->map;
		}

		/**
		 * Устанавливает экземпляр класса с константами
		 * @param iUmiConstantMap $mapInstance экземпляр класса
		 */
		public function setMap(iUmiConstantMap $mapInstance) {
			$this->map = $mapInstance;
		}
	}

