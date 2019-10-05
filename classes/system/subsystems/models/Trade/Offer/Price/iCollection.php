<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Orm\Entity\iCollection as iAbstractCollection;

	/**
	 * Интерфейс коллекции цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	interface iCollection extends iAbstractCollection {

		/**
		 * Возвращает список цен
		 * @return iPrice[]
		 */
		public function getList();

		/**
		 * Возвращает цену с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iPrice|null
		 */
		public function get($id);

		/**
		 * Возвращает первую цену
		 * @return iPrice|null
		 */
		public function getFirst();

		/**
		 * Возвращает список цен с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения, смотри константы
		 * @return iPrice[]
		 */
		public function getListBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Возвращает список цен, отсортированный по заданному атрибуту
		 * @param string $name атрибут
		 * @param string $sortType тип сортировки, смотри константы
		 * @return iPrice[]
		 * @throws \ErrorException
		 */
		public function getSortedList($name, $sortType = self::SORT_TYPE_ASC);

		/**
		 * Возвращает цену с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения, смотри константы
		 * @return iPrice|null
		 */
		public function getFirstBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Возвращает основную цену
		 * @return iPrice|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getMain();

		/**
		 * Помещает цену в коллекцию
		 * @param iEntity|iPrice $price цена
		 * @return $this
		 * @throws \ErrorException
		 */
		public function push(iEntity $price);

		/**
		 * Помещает список цен в коллекцию
		 * @param iEntity[]|iPrice[] $priceList список цен
		 * @return $this
		 */
		public function pushList(array $priceList);

		/**
		 * Удаляет цену из коллекции
		 * @param int $id идентификатор цены
		 * @return iPrice
		 */
		public function pull($id);

		/**
		 * Фильтрует коллекцию цен по типу
		 * @param int $id идентификатор типа
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function filterByType($id);

		/**
		 * Фильтрует коллекцию цен по торговому предложению
		 * @param int $id идентификатор торгового предложения
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function filterByOffer($id);

		/**
		 * Извлекает список идентификаторов торговых предложений
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractOfferId();

		/**
		 * Извлекает список уникальных идентификаторов торговых предложений
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractUniqueOfferId();

		/**
		 * Извлекает список идентификаторов типов цен
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractTypeId();

		/**
		 * Извлекает список уникальных идентификаторов типов цен
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractUniqueTypeId();

		/**
		 * Извлекает список идентификаторов валют
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractCurrencyId();

		/**
		 * Извлекает список уникальных идентификаторов валют
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractUniqueCurrencyId();
	}