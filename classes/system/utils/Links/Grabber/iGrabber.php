<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber;

	/**
	 * Interface iGrabber интерфейс собирателя ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Grabber
	 */
	interface iGrabber {

		/**
		 * Запускает сбор ссылок
		 * @return iGrabber
		 */
		public function grab();

		/**
		 * Возвращает статус завершенности проверки
		 * @return bool
		 */
		public function isComplete();

		/**
		 * Возвращает название этапа сбора
		 * @return string
		 */
		public function getStateName();

		/**
		 * Возвращает результат сбора
		 * @return array
		 */
		public function getResult();

		/**
		 * Устанавливает состояние сбора ссылок
		 * @param iState $state экземпляр класса состояния
		 * @return iGrabber
		 */
		public function setState(iState $state);

		/**
		 * Сохраняет состояние сбора в реестре
		 * @return iGrabber
		 */
		public function saveState();

		/**
		 * Очищает сохраненное состояние
		 * @return iGrabber
		 */
		public function flushSavedState();

		/**
		 * Сохраняет результат сбора
		 * @return iGrabber
		 */
		public function saveResult();
	}
