<?php

	/** Интерфейс карты констант */
	interface iUmiConstantMap {

		/**
		 * Возвращает значение константы или null, если она не объявлена
		 * @param string $constant имя константы
		 * @return mixed|null
		 */
		public function get($constant);
	}

