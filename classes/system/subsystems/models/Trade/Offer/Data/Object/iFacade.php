<?php
	namespace UmiCms\System\Trade\Offer\Data\Object;

	use \iUmiObject as iObject;
	use UmiCms\System\Trade\iOffer;
	use \iUmiObjectsCollection as iCollection;
	use UmiCms\System\Trade\Offer\Data\Object\Type\iFacade as iTypeFacade;

	/**
	 * Интерфейс фасада объектов данных торговых предложений
	 * @package UmiCms\System\Trade\Offer\Data\Object
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iCollection $collection коллекция объектов
		 * @param iTypeFacade $typeFacade фасад типов
		 */
		public function __construct(iCollection $collection, iTypeFacade $typeFacade);

		/**
		 * Возвращает объект с заданным идентификатором
		 * @param int $id идентификатор
		 * @return null|iObject
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function get($id);

		/**
		 * Возвращает список объектов с заданными идентификаторами
		 * @param array $idList список идентификаторов
		 * @return iObject[]
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function getList(array $idList);

		/**
		 * Создает объект данных
		 * @param string $name название
		 * @param int|null $typeId идентификатор типа
		 * @return iObject
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function create($name, $typeId = null);

		/**
		 * Создает объект данных торгового предложения
		 * @param iOffer $offer торговое предложение
		 * @return iObject
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function createByOffer(iOffer $offer);

		/**
		 * Сохраняет объект данных
		 * @param iObject $object объект данных
		 * @return $this
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function save(iObject $object);

		/**
		 * Удаляет объект данных с заданным идентификатором
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function delete($id);

		/**
		 * Копирует объект данных
		 * @param iObject $source копируемый объект данных
		 * @return iObject
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function copy(iObject $source);
	}