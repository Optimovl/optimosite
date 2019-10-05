<?php

	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Интерфейс экспорта сущностей системы в формате UMIDUMP
	 */
	interface iXmlExporter {

		/**
		 * Добавляет объекты для экспорта
		 * @param array $objectList список объектов или их идентификаторов
		 */
		public function addObjects($objectList);

		/**
		 * Добавляет объектные типы данных для экспорта
		 * @param array $typeList список типов данных или их идентификаторов
		 */
		public function addTypes($typeList);

		/**
		 * Добавляет сущности, сгруппированные по сервисам, для экспорта.
		 * Если для сервиса передан пустой список, будут экспортированы все сущности этого сервиса.
		 * @param array $serviceList
		 *
		 * [
		 *      'modules_to_load' => [ // если для инициализации сервиса требуется загрузить некоторый модуль
		 *            'service1' => 'module1'
		 *        ],
		 *      'service1' => [
		 *          1, 2, 3, 4, 5
		 *      ],
		 *      'service2' => [
		 *          1, 2, 3, 4, 5
		 *      ]
		 * ]
		 */
		public function addEntities(array $serviceList);

		/**
		 * Выполняет одну итерацию экспорта
		 * @return DOMDocument
		 */
		public function execute();

		/**
		 * Возвращает статус завершенности экспорта
		 * @return bool
		 */
		public function isCompleted();

		/**
		 * Возвращает связывателя идентификаторов импортируемых сущностей с системными
		 * @return iSourceIdBinder
		 */
		public function getEntitySourceIdBinder();

		/**
		 * Устанавливает опцию сериализации
		 * @param string $name имя
		 * @param mixed $value значение
		 * @return $this
		 */
		public function setSerializeOption($name, $value);
	}

