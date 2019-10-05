<?php

	namespace UmiCms\Classes\System\Utils\Links\Grabber;

	/**
	 * Interface iState интефейс состояния сбора ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Grabber
	 */
	interface iState {

		/** @var string STEPS_KEY ключ состояния шагов сбора */
		const STEPS_KEY = 'steps';

		/** @var string CURRENT_STEP_KEY ключ имени текущего шага сбора */
		const CURRENT_STEP_KEY = 'current';

		/** @var string COMPLETE_KEY ключ статуса завершенности сбора */
		const COMPLETE_KEY = 'complete';

		/**
		 * Конструктор
		 * @param array $state данные состояния
		 */
		public function __construct(array $state);

		/**
		 * Устанавливает имя текущего шага сбора
		 * @param string $stepName имя текущего шага сбора
		 * @return iState
		 */
		public function setCurrentStepName($stepName);

		/**
		 * Устанавливает состояние шагов сбора
		 * @param array $stepsState состояние шагов сбора
		 * @return iState
		 */
		public function setStepsState($stepsState);

		/**
		 * Возвращает имя текущего шага сбора
		 * @return string
		 */
		public function getCurrentStepName();

		/**
		 * Возвращает имена шагов сбора
		 * @return array
		 */
		public function getStepsNames();

		/**
		 * Возвращает состояния шагов сбора
		 * @return array
		 */
		public function getStatesOfSteps();

		/**
		 * Возвращает состояние шага сбора
		 * @param Steps\iStep $step шаг сбора
		 * @return array
		 */
		public function getStateOfStep(Steps\iStep $step);

		/**
		 * Устанавливает состоение шага сбора
		 * @param Steps\iStep $step шаг сбора
		 * @return iState
		 */
		public function setStateOfStep(Steps\iStep $step);

		/**
		 * Устанавливает статус завершенности сбора
		 * @param bool $completeStatus статус завершенности
		 * @return iState
		 */
		public function setCompleteStatus($completeStatus);

		/**
		 * Возвращает статус завершенности сбора
		 * @return bool
		 */
		public function isComplete();

		/**
		 * Возвращает состояние сбора в виде массива
		 * @return array
		 */
		public function export();
	}
