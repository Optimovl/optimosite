<?php
	namespace UmiCms\System\Orm\Entity;

	use \iXmlExporter as iBaseExporter;

	/**
	 * Интерфейс экспортера сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iExporter {

		/**
		 * Конструктор
		 * @param iFacade $facade фасад сущностей
		 * @param iSchema $schema схема хранения сущностей
		 */
		public function __construct(iFacade $facade, iSchema $schema);

		/**
		 * Экспортирует атрибуты заданных сущностей
		 * @param int[] $idList список идентификаторов экспортируемых сущностей
		 * @param iBaseExporter $exporter базовый экспортер
		 * @return array
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function export(array $idList, iBaseExporter $exporter);

		/**
		 * Возвращает список связей между внешними и внутренними идентификаторами
		 * @param array $internalIdList  список внутренних идентификаторов сущностей
		 * @param iBaseExporter $exporter базовый экспортер
		 * @return string[]
		 *
		 * [
		 *		internal_id => external_id
		 * ]
		 *
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getRelationMap(array $internalIdList, iBaseExporter $exporter);
	}