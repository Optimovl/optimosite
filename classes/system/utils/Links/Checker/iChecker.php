<?php

	namespace UmiCms\Classes\System\Utils\Links\Checker;

	/**
	 * Interface iUrlsChecker интерфейс проверщика ссылок.
	 * @package UmiCms\Classes\System\Utils\Links\Checker
	 */
	interface iChecker {

		/**
		 * Возвращает статус завершенности проверки
		 * @return bool
		 */
		public function isComplete();

		/**
		 * Проверяет установленные адреса
		 * @return iChecker
		 */
		public function checkBrokenUrls();

		/**
		 * Устанавливает состояние проверки ссылок
		 * @param iState $state экземпляр класса состояния
		 * @return iChecker
		 */
		public function setState(iState $state);

		/**
		 * Сохраняет состояние сбора в реестре
		 * @return iChecker
		 */
		public function saveState();

		/**
		 * Очищает сохраненное состояние
		 * @return iChecker
		 */
		public function flushSavedState();
	}
