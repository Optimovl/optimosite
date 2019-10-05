<?php

	namespace UmiCms\System\Response\UpdateTime;

	/**
	 * Интерфейс вычислителя времени последнего обновления данных ответа
	 * @package UmiCms\System\Cache\Request
	 */
	interface iCalculator {

		/**
		 * Конструктор
		 * @param \iUmiHierarchy $pageCollection коллекция страниц
		 * @param \iUmiObjectsCollection $objectCollection коллекция объектов
		 */
		public function __construct(\iUmiHierarchy $pageCollection, \iUmiObjectsCollection $objectCollection);

		/**
		 * Вычисляет время (timestamp) последнего обновления данных ответа
		 * @return int
		 */
		public function calculate();
	}
