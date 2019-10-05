<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\iCharacteristic;
	use UmiCms\System\Orm\Entity\iCollection as iAbstractCollection;

	/**
	 * Интерфейс коллекции характеристик торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iCollection extends iAbstractCollection {

		/**
		 * Возвращает список характеристик
		 * @return iCharacteristic[]
		 */
		public function getList();

		/**
		 * Возвращает характеристику с заданным идентификатором
		 * @param int $id идентификатор
		 * @return iCharacteristic|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function get($id);

		/**
		 * Возвращает первую характеристику
		 * @return iCharacteristic|null
		 */
		public function getFirst();

		/**
		 * Возвращает список характеристик с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения, смотри константы класса
		 * @return iCharacteristic[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getListBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Возвращает список характеристик, отсортированный по заданному атрибуту
		 * @param string $name атрибут
		 * @param string $sortType тип сортировки, смотри константы
		 * @return iCharacteristic[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getSortedList($name, $sortType = self::SORT_TYPE_ASC);

		/**
		 * Возвращает характеристику с заданным значением указанного атрибута
		 * @param string $name атрибут
		 * @param mixed $value значение
		 * @param string $compareType тип сравнения
		 * @return iCharacteristic|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getFirstBy($name, $value, $compareType = self::COMPARE_TYPE_EQUALS);

		/**
		 * Помещает характеристику в коллекцию
		 * @param iCharacteristic|iEntity $characteristic характеристика
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function push(iEntity $characteristic);

		/**
		 * Помещает список характеристик в коллекцию
		 * @param iCharacteristic[] $characteristicList список характеристик
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function pushList(array $characteristicList);

		/**
		 * Удаляет характеристику из коллекции
		 * @param int $id идентификатор характеристики
		 * @return iCharacteristic|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function pull($id);

		/**
		 * Удаляет список характеристик из коллекции
		 * @param array $idList список идентификаторов
		 * @return iCharacteristic[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function pullList(array $idList);

		/**
		 * Фильтрует коллекцию по идентификатору объекта данных
		 * @param int $id идентификатор объекта данных
		 * @return Collection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function filterByDataObject($id);

		/**
		 * Фильтрует коллекцию по имени поля
		 * @param string $name имя поля
		 * @return Collection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function filterByField($name);

		/**
		 * Извлекает идентификаторы объектов данных характеристик
		 * @return array
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function extractDataObjectId();
	}