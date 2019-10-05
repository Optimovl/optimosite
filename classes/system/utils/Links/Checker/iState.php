<?php

	namespace UmiCms\Classes\System\Utils\Links\Checker;

	/**
	 * Interface iState интерфейс состояния проверки ссылок
	 * @package UmiCms\Classes\System\Utils\Links\Checker
	 */
	interface iState {

		/** @var string OFFSET_KEY ключ смещения результатов выборки */
		const OFFSET_KEY = 'offset';

		/** @var string LIMIT_KEY ключ ограничения на количество результатов выборки */
		const LIMIT_KEY = 'limit';

		/** @var string COMPLETE_KEY ключ статуса завершенности сбора */
		const COMPLETE_KEY = 'complete';

		/**
		 * Конструктор
		 * @param array $state данные состояния
		 */
		public function __construct(array $state);

		/**
		 * Устанавливает смещение результата выборки
		 * @param int $offset смещение результата выборки
		 * @return iState
		 * @throws \Exception
		 */
		public function setOffset($offset);

		/**
		 * Возвращает смещение результата выборки
		 * @return int
		 * @throws \Exception
		 */
		public function getOffset();

		/**
		 * Устанавливает ограничение на количество результатов выборки
		 * @param int $limit ограничение на количество результатов выборки
		 * @return iState
		 * @throws \Exception
		 */
		public function setLimit($limit);

		/**
		 * Возвращает ограничение на количество результатов выборки
		 * @return int
		 * @throws \Exception
		 */
		public function getLimit();

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
