<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber\Steps;

	/**
	 * Interface iStep интерфейс шага сбора
	 * @package UmiCms\Classes\System\Utils\Links\Grabber\Steps
	 */
	interface iStep {

		/**
		 * Возвращает имя шага
		 * @return string
		 */
		public function getName();

		/**
		 * Устанавливает состояние шага сбора
		 * @param array $state состояние
		 * @return iStep
		 */
		public function setState(array $state);

		/**
		 * Запускает шаг сбора
		 * @return iStep
		 */
		public function grab();

		/**
		 * Возвращает состояние шага сбора
		 * @return array
		 */
		public function getState();

		/**
		 * Возвращает статус завершенности шага сбора
		 * @return bool
		 */
		public function isComplete();

		/**
		 * Возвращает результат выполнения итерации шага сбора
		 * @return array
		 */
		public function getResult();

		/**
		 * Возвращает структуру данных для инициализации начального состояния шага
		 * @return array
		 */
		public function getStartStateStructure();
	}
