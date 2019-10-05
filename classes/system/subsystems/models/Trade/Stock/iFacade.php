<?php
	namespace UmiCms\System\Trade\Stock;

	use UmiCms\System\Trade\iStock;
	use \iUmiObjectsCollection as iDataObjectCollection;
	use \iUmiObjectTypesCollection as iDataObjectTypeCollection;

	/**
	 * Интерфейс складов
	 * @package UmiCms\System\Trade\Stock
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iFactory $factory фабрика складов
		 * @param iDataObjectCollection $objectCollection коллекция объектов данных
		 * @param iDataObjectTypeCollection $typeCollection коллекция типов объектов данных
		 */
		public function __construct(iFactory $factory, iDataObjectCollection $objectCollection, iDataObjectTypeCollection $typeCollection);

		/**
		 * Возвращает склад по идентификатору
		 * @param int $id идентификатор склада
		 * @return iStock|null
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список складов
		 * @return iStock[]
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \Exception
		 */
		public function getList();

		/**
		 * Создает склад
		 * @param string $name название склада
		 * @return iStock
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function create($name);

		/**
		 * Сохраняет склад
		 * @param iStock $stock склад
		 * @return $this
		 */
		public function save(iStock $stock);

		/**
		 * Удаляет склад с заданным идентификатором
		 * @param int $id идентификатор склада
		 * @return $this
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function delete($id);
	}