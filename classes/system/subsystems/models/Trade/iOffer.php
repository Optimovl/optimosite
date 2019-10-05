<?php
	namespace UmiCms\System\Trade;

	use \iUmiObject as iObject;
	use \iUmiObjectType as iType;
	use UmiCms\System\Orm\Composite\iEntity;
	use UmiCms\System\Trade\Offer\Price\iCollection as iPriceCollection;
	use UmiCms\System\Trade\Stock\Balance\iCollection as iStockBalanceCollection;
	use UmiCms\System\Trade\Offer\Characteristic\iCollection as iCharacteristicCollection;

	/**
	 * Интерфейс торгового предложения
	 * @package UmiCms\System\Trade
	 */
	interface iOffer extends iEntity {

		/**
		 * Возвращает идентификатор типа данных
		 * @return int|null
		 */
		public function getTypeId();

		/**
		 * Устанавливает идентификатор типа данных
		 * @param int $id идентификатор типа данных
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setTypeId($id);

		/**
		 * Возвращает идентификатор объекта данных
		 * @return int|null
		 */
		public function getDataObjectId();

		/**
		 * Устанавливает идентификатор объекта данных
		 * @param int $id идентификатор объекта данных
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setDataObjectId($id);

		/**
		 * Определяет установлен ли идентификатор объекта данных
		 * @return bool
		 */
		public function hasDataObjectId();

		/**
		 * Возвращает артикул
		 * @return string|null
		 */
		public function getVendorCode();

		/**
		 * Устанавливает артикул
		 * @param string $code артикул
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setVendorCode($code);

		/**
		 * Определяет установлен ли артикул
		 * @return bool
		 */
		public function hasVendorCode();

		/**
		 * Возвращает название
		 * @return string|null
		 */
		public function getName();

		/**
		 * Устанавливает название
		 * @param string $name название
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setName($name);

		/**
		 * Возвращает штрихкод
		 * @return string|null
		 */
		public function getBarCode();

		/**
		 * Устанавливает штрихкод
		 * @param string $code штрихкод
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setBarCode($code);

		/**
		 * Возвращает общее количество на складе
		 * @return int
		 */
		public function getTotalCount();

		/**
		 * Устанавливает общее количество на складе
		 * @param int $count количество
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setTotalCount($count);

		/**
		 * Определяет активнось
		 * @return bool
		 */
		public function isActive();

		/**
		 * Устанавливает активность
		 * @param bool $flag значение
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setActive($flag = true);

		/**
		 * Возвращает индекс для сортировки
		 * @return int
		 */
		public function getOrder();

		/**
		 * Устанавливает индекс для сортировки
		 * @param int $index индекс
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setOrder($index);

		/**
		 * Определяет установлен ли индекс сортировки
		 * @return bool
		 */
		public function hasOrder();

		/**
		 * Возвращает вес
		 * @return int
		 */
		public function getWeight();

		/**
		 * Устанавливает вес
		 * @param int $weight вес
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setWeight($weight);

		/**
		 * Возвращает ширину
		 * @return int
		 */
		public function getWidth();

		/**
		 * Устанавливает ширину
		 * @param int $width ширина
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setWidth($width);

		/**
		 * Возвращает длину
		 * @return int
		 */
		public function getLength();

		/**
		 * Устанавливает длину
		 * @param int $length длина
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setLength($length);

		/**
		 * Возвращает высоту
		 * @return int
		 */
		public function getHeight();

		/**
		 * Устанавливает высоту
		 * @param int $height высота
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setHeight($height);

		/**
		 * Возвращает тип
		 * @return iType|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getType();

		/**
		 * Устанавливает тип
		 * @param iType $type тип
 		 * @return $this
		 * @throws \ErrorException
		 */
		public function setType(iType $type);

		/**
		 * Возвращает объект данных
		 * @return iObject|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getDataObject();

		/**
		 * Устанавливает объект данных
		 * @param iObject $object объект данных
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setDataObject(iObject $object);

		/**
		 * Возвращает коллекцию цен
		 * @return iPriceCollection|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getPriceCollection();

		/**
		 * Устанавливает коллекцию цен
		 * @param iPriceCollection $collection коллекция цен
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setPriceCollection(iPriceCollection $collection);

		/**
		 * Возвращает коллекцию складских остатков
		 * @return iStockBalanceCollection|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getStockBalanceCollection();

		/**
		 * Устанавливает коллекцию складских остатков
		 * @param iStockBalanceCollection $collection коллекция складских остатков
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setStockBalanceCollection(iStockBalanceCollection $collection);

		/**
		 * Возвращает коллекцию характеристик
		 * @return iCharacteristicCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getCharacteristicCollection();

		/**
		 * Устанавливает коллекцию характеристик
		 * @param iCharacteristicCollection $collection коллекция характеристик
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function setCharacteristicCollection(iCharacteristicCollection $collection);
	}