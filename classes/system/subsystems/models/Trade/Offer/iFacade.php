<?php
	namespace UmiCms\System\Trade\Offer;

	use \iUmiObject as iObject;
	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\iOffer;
	use UmiCms\System\Orm\Entity\iFacade as iAbstractFacade;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;
	use UmiCms\System\Trade\Offer\Price\iFacade as iOfferPriceFacade;
	use UmiCms\System\Trade\Stock\Balance\iFacade as iStockBalanceFacade;
	use UmiCms\System\Trade\Offer\Data\Object\Type\iFacade as iTypeFacade;
	use UmiCms\System\Trade\Offer\Data\Object\iFacade as iDataObjectFacade;
	use UmiCms\System\Trade\Offer\Vendor\Code\iGenerator as VendorCodeGenerator;

	/**
	 * Интерфейс фасада торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iFacade extends iAbstractFacade {

		/**
		 * Возвращает торговое предложение по его идентификатору
		 * @param int $id идентификатор торгового предложения
		 * @return iOffer|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список торговых предложений с заданными идентификаторами
		 * @param int[] $idList список идентификаторов торговых предложений
		 * @return iOffer[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getList(array $idList);

		/**
		 * Возвращает коллекцию торговых предложений с заданным значением указанного атрибута
		 * @param string $name имя поля
		 * @param mixed $value значение
		 * @return iOfferCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionBy($name, $value);

		/**
		 * Возвращает коллекцию торговых предложений со значением указанного атрибута из
		 * заданного списка
		 * @param string $name имя поля
		 * @param array $valueList список значений
		 * @return iOfferCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByValueList($name, array $valueList);

		/**
		 * Возвращает коллекцию торговых предложений с заданными идентификаторами
		 * @param array $idList список идентификаторов торговых предложений
		 * @return iOfferCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByIdList(array $idList);

		/**
		 * Перемещает коллекцию торговых предложений с заданным режимом перемещения
		 * @param iOfferCollection $collection коллекция торговых предложений
		 * @param iOffer $staticOffer предложение, относительно которого происходит перемещение
		 * @param string $mode режим перемещения
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function moveCollectionByMode(iCollection $collection, iOffer $staticOffer, $mode);

		/**
		 * Перемещает коллекцию торговых предложений после заданного предложения
		 * @param iOfferCollection $collection коллекция торговых предложений
		 * @param iOffer $staticOffer предложение, относительно которого происходит перемещение
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function moveCollectionAfter(iCollection $collection, iOffer $staticOffer);

		/**
		 * Перемещает коллекцию торговых предложений до заданного предложения
		 * @param iOfferCollection $collection коллекция торговых предложений
		 * @param iOffer $staticOffer предложение, относительно которого происходит перемещение
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function moveCollectionBefore(iCollection $collection, iOffer $staticOffer);

		/**
		 * Формирует коллекцию торговых предложений
		 * @param array $offerList список торговых предложений
		 * @return iOfferCollection
		 */
		public function mapCollection(array $offerList);

		/**
		 * Создает торговое предложение
		 * @param array $attributeList атрибуты торгового предложения
		 * @return iOffer
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function create(array $attributeList = []);

		/**
		 * Создает торговое предложение для товара
		 * @param iObject $product товар
		 * @return iOffer
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function createForProduct(iObject $product);

		/**
		 * Сохраняет торговое предложение
		 * @param iEntity|iOffer $offer
		 * @return $this
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \databaseException
		 * @throws \ReflectionException
		 */
		public function save(iEntity $offer);

		/**
		 * Удаляет торговое предложение с заданным идентификатором
		 * @param int $id идентификатор предложения
		 * @return $this
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 */
		public function delete($id);

		/**
		 * Копирует торговое предложение
		 * @param iEntity $source копируемое предложение
		 * @return iEntity|iOffer
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \databaseException
		 * @throws \Exception
		 */
		public function copy(iEntity $source);

		/**
		 * Устанавливает фасад объектов данных торговых предложений
		 * @param iDataObjectFacade $facade фасад
		 * @return $this
		 */
		public function setDataObjectFacade(iDataObjectFacade $facade);

		/**
		 * Устанавливает фасад цен торговых предложений
		 * @param iOfferPriceFacade $facade фасад цен торговых предложений
		 * @return $this
		 */
		public function setOfferPriceFacade(iOfferPriceFacade $facade);

		/**
		 * Устанавливает генератор артикулов
		 * @param VendorCodeGenerator $generator генератор
		 * @return $this
		 */
		public function setVendorCoderGenerator(VendorCodeGenerator $generator);

		/**
		 * Устанавливает фасад типов предложений
		 * @param iTypeFacade $typeFacade фасад типов предложений
		 * @return $this
		 */
		public function setTypeFacade(iTypeFacade $typeFacade);

		/**
		 * Устанавливает фасад складских остатков
		 * @param iStockBalanceFacade $stockBalanceFacade
		 * @return $this
		 */
		public function setStockBalanceFacade(iStockBalanceFacade $stockBalanceFacade);
	}