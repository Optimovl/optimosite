<?php

	/** @deprecated */
	interface iSessionValidation {

		const START_TIME_KEY = 'starttime';

		/** Фиксирует начало активности сессии */
		public function startActiveTime();

		/** Фиксирует конец активности сессии */
		public function endActiveTime();

		/**
		 * Проверяет закончилось ли время активности сессии
		 * @return bool
		 */
		public function isActiveTimeExpired();

		/**
		 * Возвращает время, которое сесиия будет активна, в минутах
		 * @return int
		 */
		public function getActiveTime();

		/**
		 * Возвращает максимальное время активности сессии в минутах
		 * @return int
		 */
		public function getMaxActiveTime();
	}
