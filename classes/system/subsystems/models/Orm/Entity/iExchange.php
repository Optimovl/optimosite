<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use \iXmlExporter as iBaseExporter;
	use UmiCms\System\Import\UmiDump\Entity\iBaseImporter;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Интерфейс фасада импорта и экспорта сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iExchange {

		/**
		 * Конструктор
		 * @param iImporter $importer импортер сущностей
		 * @param iExporter $exporter экспортер сущностей
		 * @param iFacade $facade фасад сущностей
		 * @param iBuilder $builder строитель сущностей
		 * @param iAccessor $accessor аксессор связей сущности
		 * @param iDemolisher $demolisher экземпляр класса удаления импортированных сущностей
		 */
		public function __construct(
			iImporter $importer, iExporter $exporter, iFacade $facade,
			iBuilder $builder, iAccessor $accessor, iDemolisher $demolisher
		);

		/**
		 * Импортирует одну сущность
		 * @param array $attributeList список атрибутов сущности
		 * @example:
		 *
		 * [
		 * 		'id' => 'Foo',
		 * 		'name' => 'Bar'
		 * ]
		 *
		 * @param iBaseImporter $baseImporter базовый импортер
		 * @return iEntity|null
		 */
		public function importOne(array $attributeList, iBaseImporter $baseImporter);

		/**
		 * Импортирует несколько сущностей
		 * @param array $attributeListSet набор списков атрибутов сущности
		 * @param iBaseImporter $baseImporter базовый импортер
		 * @return iCollection
		 * @example:
		 *
		 * [
		 * 		[
		 * 			'id' => 'Foo',
		 * 			'name' => 'Bar'
		 * 		],
		 * 		[
		 * 			'id' => 'Baz',
		 * 			'name' => 'Umi'
		 * 		]
		 * ]
		 *
		 */
		public function importMany(array $attributeListSet, iBaseImporter $baseImporter);

		/**
		 * Возвращает список внутренних идентификаторов сущностей
		 * @param array $externalIdList список внешних идентификаторов
		 * @param iBaseImporter $baseImporter базовый импортер
		 * @return int[]
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getInternalIdList(array $externalIdList, iBaseImporter $baseImporter);

		/**
		 * Экспортирует одну сущность
		 * @param int $id идентификатор сущности
		 * @param iBaseExporter $baseExporter базовый экспортер
		 * @return array
		 *
		 * @example:
		 *
		 * [
		 * 		'id' => 123,
		 * 		'name' => 'Bar'
		 * ]
		 *
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function exportOne($id ,iBaseExporter $baseExporter);

		/**
		 * Экспортирует список сущностей
		 * @param array $idList список идентификаторов сущностей
		 * @param iBaseExporter $baseExporter базовый экспортер
		 * @return array
		 *
		 * @example:
		 *
		 * [
		 * 		123 => [
		 * 			'id' => 123,
		 * 			'name' => 'Foo'
		 * 		],
		 * 		321 => [
		 * 			'id' => 321,
		 * 			'name' => 'Bar'
		 * 		]
		 * ]
		 *
		 *
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function exportMany(array $idList, iBaseExporter $baseExporter);

		/**
		 * Возвращает список внешних идентификаторов сущностей
		 * @param array $internalIdList список внутренних идентификаторов сущностей
		 * @param iBaseExporter $baseExporter базовый экспортер
		 * @return string[]
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getExternalIdList(array $internalIdList, iBaseExporter $baseExporter);

		/**
		 * Возвращает список зависимостей, который необходимо выгрузить вместе со списком экспортируемых сущностей
		 * @param int $id идентификатор экспортируемой сущности
		 * @return array
		 * @example:
		 *
		 * [
		 * 		'umiObjectTypeExchange' => [
		 * 			1, 2, 3
		 * 		],
		 *		'TradeOfferExchange' => [
		 * 			1, 2, 3
		 * 		],
		 * 		'FooBarBazExchange' => [
		 * 			1, 2, 3
		 * 		]
		 * ]
		 *
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getDependenciesForExportOne($id);

		/**
		 * Возвращает список зависимостей, который необходимо выгрузить вместе с выгружаемыми сущностями
		 * @param int[] $idList
		 * @return array
		 * @example:
		 *
		 * [
		 * 		'umiObjectTypeExchange' => [
		 * 			1, 2, 3
		 * 		],
		 *		'TradeOfferExchange' => [
		 * 			1, 2, 3
		 * 		],
		 * 		'FooBarBazExchange' => [
		 * 			1, 2, 3
		 * 		]
		 * ]
		 *
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getDependenciesForExportMany(array $idList);

		/**
		 * Удаляет список импортированных сущностей
		 * @param int[] $externalIdList список внешних идентификаторов
		 * @param iSourceIdBinder $sourceIdBinder связыватель внешних и внутренних идентификаторов
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function demolishByExternalIdList($externalIdList, iSourceIdBinder $sourceIdBinder);
	}