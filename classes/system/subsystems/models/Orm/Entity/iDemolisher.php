<?php
	namespace UmiCms\System\Orm\Entity;

	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Интерфейс класса удаления импортированных сущностей
	 * @package UmiCms\System\Orm\Entity
	 */
	interface iDemolisher {

		/**
		 * Конструктор
		 * @param iFacade $facade фасад сущностей
		 * @param iSchema $schema схема хранения сущностей
		 */
		public function __construct(iFacade $facade, iSchema $schema);

		/**
		 * Удаляет список импортированных сущностей
		 * @param int[] $externalIdList список внешних идентификаторов
		 * @param iSourceIdBinder $sourceIdBinder связыватель внешних и внутренних идентификаторов
		 * @throws \ErrorException
		 * @return $this
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function demolishList($externalIdList, iSourceIdBinder $sourceIdBinder);

		/**
		 * Удаляет импортированную сущность
		 * @param int $externalId внешний идентификатор
		 * @param iSourceIdBinder $sourceIdBinder связыватель внешних и внутренних идентификаторов
		 * @return bool
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function demolish($externalId, iSourceIdBinder $sourceIdBinder);
	}