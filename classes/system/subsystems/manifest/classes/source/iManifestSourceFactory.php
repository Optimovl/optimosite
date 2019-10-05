<?php

	/** Интерфейс фабрики источников манифестов */
	interface iManifestSourceFactory {

		/** @const int CORE тип "ядро" */
		const CORE = 1;

		/** @const int MODULE тип "модуль" */
		const MODULE = 2;

		/** @const int SOLUTION тип "решение" */
		const SOLUTION = 3;

		/**
		 * Создает источник манифеста
		 * @param int $type тип источника манифеста
		 * @param string|null $name название (используется для типов "модуль" и "решение"
		 * @return iManifestSource
		 */
		public function create($type = self::CORE, $name = null);
	}
