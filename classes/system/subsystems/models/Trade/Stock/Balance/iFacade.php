<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Stock\iBalance;
	use UmiCms\System\Orm\Entity\iFacade as iAbstractFacade;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;
	use UmiCms\System\Trade\Stock\Balance\iCollection as iStockBalanceCollection;

	/**
	 * Интерфейс фасада складких остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	interface iFacade extends iAbstractFacade {

		/**
		 * Возвращает складской остаток по его идентификатору
		 * @param int $id идентификатор складского остатка
		 * @return iBalance|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список складских остатков с заданными идентификаторами
		 * @param int[] $idList список идентификаторов торговых предложений
		 * @return iBalance[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getList(array $idList);

		/**
		 * Возвращает коллекцию складских остатков с заданным значением указанного атрибута
		 * @param string $name имя поля
		 * @param mixed $value значение
		 * @return iStockBalanceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionBy($name, $value);

		/**
		 * Возвращает коллекцию складских остатков с указанного склада
		 * @param int $id идентификатор склада
		 * @return iStockBalanceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByStock($id);

		/**
		 * Возвращает коллекцию складских остатков указанного торгового предложения
		 * @param int $id идентификатор торгового предложения
		 * @return iStockBalanceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByOffer($id);

		/**
		 * Возвращает коллекцию складских остатков указанного списка торговых предложений
		 * @param array $idList список идентификаторов торговых предложений
		 * @return iStockBalanceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByOfferList(array $idList);

		/**
		 * Возвращает коллекцию складских остатков торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return iStockBalanceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByOfferCollection(iOfferCollection $offerCollection);

		/**
		 * Возвращает коллекцию складских остатков со значением указанного атрибута из
		 * заданного списка
		 * @param string $name имя поля
		 * @param array $valueList список значений
		 * @return iStockBalanceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByValueList($name, array $valueList);

		/**
		 * Формирует коллекцию складских остатков
		 * @param array $balanceList список складских остатков
		 * @return iStockBalanceCollection
		 */
		public function mapCollection(array $balanceList);

		/**
		 * Создает складской остаток
		 * @param array $attributeList атрибуты складского остатка
		 * @return iBalance
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function create(array $attributeList = []);

		/**
		 * Создает складской остаток по складу и торговому предложению
		 * @param int $offerId идентификатор торгового предложения
		 * @param int $stockId идентификатор склада
		 * @return iBalance
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function createByOfferAndStock($offerId, $stockId);

		/**
		 * Сохраняет складской остаток
		 * @param iEntity|iBalance $balance складской остаток
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function save(iEntity $balance);

		/**
		 * Удаляет складской остаток
		 * @param int $id идентификатор складского остатка
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function delete($id);
	}