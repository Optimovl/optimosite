<?php

	/** Интерфейс редиректа */
	interface iUmiRedirect {

		/**
		 * Возвращает адрес, откуда перенаправлять
		 * @return string
		 */
		public function getSource();

		/**
		 * Возвращает адрес, куда перенаправлять
		 * @return string
		 */
		public function getTarget();

		/**
		 * Возвращает статус редиректа
		 * @return int
		 */
		public function getStatus();

		/**
		 * Сделан ли редирект пользователем (не автоматически)
		 * @return bool
		 */
		public function isMadeByUser();

		/**
		 * Устанавливает адрес, откуда перенаправлять
		 * @param string $source адрес, откуда перенаправлять
		 * @return mixed
		 */
		public function setSource($source);

		/**
		 * Устанавливает адрес, куда перенаправлять
		 * @param string $target адрес, куда перенаправлять
		 * @return mixed
		 */
		public function setTarget($target);

		/**
		 * Устанавливает статус редиректа
		 * @param int $status код статуса редиректа
		 * @return mixed
		 */
		public function setStatus($status);

		/**
		 * Устанавливает флаг редиректа "сделан пользователем"
		 * @param bool $isMadeByUser значение флага
		 * @return mixed
		 */
		public function setIsMadeByUser($isMadeByUser);

		/**
		 * Возвращает текстовое значение статуса перенаправления
		 * @param int $status код статуса редиректа
		 * @return string|bool
		 */
		public static function getRedirectMessage($status);
	}
