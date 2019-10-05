<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Entity\iCollection as iAbstractCollection;

	/**
	 * Интерфейс коллекции складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	interface iCollection extends iAbstractCollection {

		/**
		 * Возвращает список складских остатков
		 * @return iBalance[]
		 */
		public function getList();

		/**
		 * Возвращает складской остаток с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iBalance|null
		 */
		public function get($id);

		/**
		 * Возвращает первый складской остаток
		 * @return iBalance|null
		 */
		public function getFirst();

		/**
		 * Возвращает список складских остатков с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения, смотри константы
		 * @return iBalance[]
		 */
		public function getListBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Возвращает список складских остатков, отсортированный по заданному атрибуту
		 * @param string $name атрибут
		 * @param string $sortType тип сортировки, смотри константы
		 * @return iBalance[]
		 * @throws \ErrorException
		 */
		public function getSortedList($name, $sortType = self::SORT_TYPE_ASC);

		/**
		 * Возвращает складской остаток с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения, смотри константы
		 * @return iBalance|null
		 */
		public function getFirstBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Помещает складской остаток в коллекцию
		 * @param iBalance|iEntity $balance складской остаток
		 * @return $this
		 * @throws \ErrorException
		 */
		public function push(iEntity $balance);

		/**
		 * Помещает список остатков в коллекцию
		 * @param iEntity[]|iBalance[] $balanceList список остатков
		 * @return $this
		 */
		public function pushList(array $balanceList);

		/**
		 * Удаляет складской остаток из коллекции
		 * @param int $id складской остаток
		 * @return iBalance
		 */
		public function pull($id);

		/**
		 * Фильтрует коллекцию складских остатков по складу
		 * @param int $id идентификатор склада
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function filterByStock($id);

		/**
		 * Фильтрует коллекцию складских остатков по торговому предложению
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
		 * Извлекает список идентификаторов складов
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractStockId();

		/**
		 * Извлекает список уникальных идентификаторов складов
		 * @return int[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractUniqueStockId();
	}