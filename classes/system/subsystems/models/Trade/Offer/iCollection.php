<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Entity\iCollection as iAbstractCollection;
	use UmiCms\System\Trade\Offer\Price\iCollection as iPriceCollection;
	use UmiCms\System\Trade\Stock\Balance\iCollection as iStockBalanceCollection;
	use UmiCms\System\Trade\Offer\Characteristic\iCollection as iCharacteristicCollection;

	/**
	 * Интерфейс коллекции торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iCollection extends iAbstractCollection {

		/**
		 * Возвращает список торговых предложений
		 * @return iOffer[]
		 */
		public function getList();

		/**
		 * Возвращает торговое предложение с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iOffer|null
		 */
		public function get($id);

		/**
		 * Возвращает первое торговое предложение
		 * @return iOffer|null
		 */
		public function getFirst();

		/**
		 * Возвращает список торговых предложений с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения, смотри константы
		 * @return iOffer[]
		 */
		public function getListBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Возвращает список торговых предложений, отсортированный по заданному атрибуту
		 * @param string $name атрибут
		 * @param string $sortType тип сортировки, смотри константы
		 * @return iOffer[]
		 * @throws \ErrorException
		 */
		public function getSortedList($name, $sortType = self::SORT_TYPE_ASC);

		/**
		 * Возвращает торговое предложение с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения, смотри константы
		 * @return iOffer|null
		 */
		public function getFirstBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Помещает торговое предложение в коллекцию
		 * @param iEntity|iOffer $offer торговое предложение
		 * @return $this
		 * @throws \ErrorException
		 */
		public function push(iEntity $offer);

		/**
		 * Помещает список предложений в коллекцию
		 * @param iEntity[]|iOffer[] $offerList список предложений
		 * @return $this
		 */
		public function pushList(array $offerList);

		/**
		 * Удаляет торговое предложение из коллекции
		 * @param int $id идентификатор предложения
		 * @return iOffer
		 */
		public function pull($id);

		/**
		 * Сортирует коллекцию предложений по коллекции цен
		 * @param iPriceCollection $collection коллекция цен
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function sortByPriceCollection(iPriceCollection $collection);

		/**
		 * Сортирует коллекцию предложений по коллекции складских остатков
		 * @param iStockBalanceCollection $collection коллекция складских остатков
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function sortByStockBalanceCollection(iStockBalanceCollection $collection);

		/**
		 * Сортирует коллекцию предложений по коллекции характеристик
		 * @param iCharacteristicCollection $collection коллекция характеристик
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function sortByCharacteristicCollection(iCharacteristicCollection $collection);

		/**
		 * Извлекает идентификаторы объектов данных сущностей коллекции
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractDataObjectId();

		/**
		 * Извлекает уникальные идентификаторы объектов данных сущностей коллекции
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractUniqueDataObjectId();

		/**
		 * Извлекает идентификаторы типов данных сущностей коллекции
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractTypeId();

		/**
		 * Извлекает уникальные идентификаторы типов данных сущностей коллекции
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractUniqueTypeId();
	}