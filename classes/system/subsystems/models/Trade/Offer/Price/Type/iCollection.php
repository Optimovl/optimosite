<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\Price\iType;
	use UmiCms\System\Orm\Entity\iCollection as iAbstractCollection;

	/**
	 * Интерфейс коллекции типов цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	interface iCollection extends iAbstractCollection {

		/**
		 * Возвращает список типов цен
		 * @return iType[]
		 */
		public function getList();

		/**
		 * Возвращает тип цены с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iType|null
		 */
		public function get($id);

		/**
		 * Возвращает первый тип цены
		 * @return iType|null
		 */
		public function getFirst();

		/**
		 * Возвращает список типов цен с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения
		 * @return iType[]
		 */
		public function getListBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Возвращает список типов цен, отсортированный по заданному атрибуту
		 * @param string $name атрибут
		 * @param string $sortType тип сортировки, смотри константы
		 * @return iType[]
		 * @throws \ErrorException
		 */
		public function getSortedList($name, $sortType = self::SORT_TYPE_ASC);

		/**
		 * Возвращает тип цены с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения
		 * @return iType|null
		 */
		public function getFirstBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Помещает тип цены в коллекцию
		 * @param iEntity|iType $type тип цены
		 * @return $this
		 * @throws \ErrorException
		 */
		public function push(iEntity $type);

		/**
		 * Помещает список типов цен в коллекцию
		 * @param iEntity[]|iType[] $typeList список типов цен
		 * @return $this
		 */
		public function pushList(array $typeList);

		/**
		 * Удаляет тип цены из коллекции
		 * @param int $id идентификатор
		 * @return iType
		 */
		public function pull($id);
	}