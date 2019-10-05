<?php
	/** Интерфейс класса управления табами в админке модулей. */
	interface iAdminModuleTabs {

		/**
		 * Добавляет новый таб для метода $methodName
		 * @param string $methodName название метода класса-модуля
		 * @param array $aliases = NULL список методов-алиасов,
		 * при котором данный таб будет считаться активным (помимо $methodName)
		 * @return bool результат операции
		 */
		public function add($methodName, $aliases = null);

		/**
		 * Возвращает список алиасов для таба $methodName
		 * @param string $methodName название метода класса-модуля
		 * @return array|bool массив алиасов, либо false в случае ошибки
		 */
		public function get($methodName);

		/**
		 * Возвращает основной метод таба по методу-алиасу, либо по его основному методу
		 * @param string $methodOrAlias
		 * @return string $methodName, либо false в случае ошибки
		 */

		public function getTabNameByAlias($methodOrAlias);

		/**
		 * Удаляет таб метода $methodName из списка табов
		 * @param string $methodName название метода класса-модуля
		 * @return bool результат операции
		 */
		public function remove($methodName);

		/**
		 * Возвращает список табов для административной панели.
		 * Если вкладка одна, то считается, что у модуля нет вкладок.
		 * @return array
		 */
		public function getAll();

		/**
		 * Возвращает список всех табов
		 * @return array
		 */
		public function getRealAll();
	}


