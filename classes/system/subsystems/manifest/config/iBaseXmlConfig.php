<?php

	/** Интерфейс менеджера конфигурации в xml файле */
	interface iBaseXmlConfig {

		/**
		 * Конструктор
		 * @param string $configFileName путь до xml файла
		 */
		public function __construct($configFileName);

		/**
		 * Возвращает значение ноды, соответствующей запросу
		 * @param string $xpath xpath запрос
		 * @return string
		 * @throws Exception если по запросу было получено больше одной ноды
		 */
		public function getValue($xpath);

		/**
		 * Возвращает список значений нод, соответствующих запросу, или заданные атрибуты
		 * @param string $xpath xpath запрос
		 * @param array $attributes список атрибутов нод, которые требуется получить
		 *
		 * [
		 *      'название параметра' => '+params' // список параметров для ноды
		 *      'название параметра' => '.' //значение ноды
		 *      'название параметра' => '/foo/bar/baz' // значение xpath запроса foo/bar/baz в контексте ноды
		 *      'название параметра' => '@foo' // значение атрибута foo нодф
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      # => [
		 *          string | 'название параметра' => string|array
		 *      ]
		 * ]
		 */
		public function getList($xpath, array $attributes = []);

		/**
		 * Возвращает имя конфига
		 * @return string
		 */
		public function getName();
	}
