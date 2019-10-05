<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Import\UmiDump\Entity\iBaseImporter;

	/**
	 * Интерфейс импортера сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iImporter {

		/**
		 * Конструктор
		 * @param iFacade $facade фасад сущностей
		 * @param iSchema $schema схема хранения сущностей
		 */
		public function __construct(iFacade $facade, iSchema $schema);

		/**
		 * Импортирует заданную сущность
		 * @param array $attributeList атрибуты сущности
		 * @param iBaseImporter $baseImporter базовый импортер
		 * @return iEntity|null
		 */
		public function import(array $attributeList, iBaseImporter $baseImporter);

		/**
		 * Возвращает список связей между внешними и внутренними идентификаторами
		 * @param array $externalIdList  список внешних идентификаторов сущностей
		 * @param iBaseImporter $baseImporter базовый импортер
		 * @return int[]
		 *
		 * [
		 *		external_id => internal_id
		 * ]
		 *
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getRelationMap(array $externalIdList, iBaseImporter $baseImporter);
	}