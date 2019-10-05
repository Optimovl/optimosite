<?php
	namespace UmiCms\System\Trade\Stock;

	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Trade\iStock;
	use UmiCms\System\Orm\Composite\iEntity;

	/**
	 * Интерфейс складского остатка
	 * @package UmiCms\System\Trade\Stock
	 */
	interface iBalance extends iEntity {

		/**
		 * Возвращает идентификатор торгового предложения
		 * @return int|null
		 */
		public function getOfferId();

		/**
		 * Устанавливает идентификатор торгового предложения
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setOfferId($id);

		/**
		 * Возврашает идентификатор склада
		 * @return int|null
		 */
		public function getStockId();

		/**
		 * Устанавливает идентификатор склада
		 * @param int $id идентификатор
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setStockId($id);

		/**
		 * Возвращает остаток
		 * @return int
		 */
		public function getValue();

		/**
		 * Устанавливает остаток
		 * @param int $value остаток
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setValue($value);

		/**
		 * Возвращает торговое предложение
		 * @return iOffer|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getOffer();

		/**
		 * Устанавливает торговое предложение
		 * @param iOffer $offer торговое предложение
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setOffer(iOffer $offer);

		/**
		 * Возвращает склад
		 * @return iStock|null
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getStock();

		/**
		 * Устанавливает склад
		 * @param iStock $stock склад
		 * @return $this
		 * @throws \ErrorException
		 */
		public function setStock(iStock $stock);
	}