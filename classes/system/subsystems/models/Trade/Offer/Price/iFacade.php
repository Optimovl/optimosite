<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\iEntity;
	use UmiCms\System\Trade\Offer\iPrice;
	use UmiCms\System\Orm\Entity\iFacade as iAbstractFacade;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;
	use UmiCms\System\Trade\Offer\Price\Type\iFacade as iTypeFacade;
	use UmiCms\System\Trade\Offer\Price\iCollection as iPriceCollection;
	use UmiCms\System\Trade\Offer\Price\Currency\iFacade as iCurrencyFacade;

	/**
	 * Интерфейс фасада цен торговых предложений
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	interface iFacade extends iAbstractFacade {

		/**
		 * Возвращает цену по ее идентификатору
		 * @param int $id идентификатор
		 * @return iPrice|null
		 * @throws \databaseException
		 * @throws \ErrorException
		 */
		public function get($id);

		/**
		 * Возвращает список цен с заданными идентификаторами
		 * @param int[] $idList список идентификаторов
		 * @return iPrice[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getList(array $idList);

		/**
		 * Возвращает список цен указанных торговых предложений
		 * @param array $idList список идентификаторов торговых предложений
		 * @return iPrice[]
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function getListByOfferIdList(array $idList);

		/**
		 * Возвращает коллекцию цен с заданным значением указанного атрибута
		 * @param string $name имя поля
		 * @param mixed $value значение
		 * @return iPriceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionBy($name, $value);

		/**
		 * Возвращает коллекцию цен указанного торгового предложения
		 * @param int $id идентификатор торгового предложения
		 * @return iPriceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByOffer($id);

		/**
		 * Возвращает коллекцию цен со значением указанного атрибута из
		 * заданного списка
		 * @param string $name имя поля
		 * @param array $valueList список значений
		 * @return iPriceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByValueList($name, array $valueList);

		/**
		 * Возвращает коллекцию цен указанного списка торговых предложений
		 * @param array $idList список идентификаторов торговых предложений
		 * @return iPriceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByOfferList(array $idList);

		/**
		 * Возвращает коллекцию цен коллекции торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return iPriceCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 * @throws \databaseException
		 */
		public function getCollectionByOfferCollection(iOfferCollection $offerCollection);

		/**
		 * Формирует коллекцию цен
		 * @param array $priceList список цен
		 * @return iPriceCollection
		 */
		public function mapCollection(array $priceList);

		/**
		 * Создает цену
		 * @param array $attributeList атрибуты цены
		 * @return iPrice
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function create(array $attributeList = []);

		/**
		 * Создает главную (основную) цену для торгового предложения
		 * @param int $offerId идентификатор торгового предложения
		 * @return iPrice
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function createMainPrice($offerId);

		/**
		 * Создает цену торгового предложения
		 * @param int $offerId идентификатор торгового предложения
		 * @param int $typeId идентификатор типа цены
		 * @return iPrice
		 * @throws \databaseException
		 * @throws \coreException
		 * @throws \ErrorException
		 * @throws \Exception
		 */
		public function createByOfferAndType($offerId, $typeId);

		/**
		 * Сохраняет цену
		 * @param iEntity|iPrice $price цена
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function save(iEntity $price);

		/**
		 * Удаляет цену
		 * @param int $id идентификатор цены
		 * @return $this
		 * @throws \databaseException
		 * @throws \ErrorException
		 * @throws \coreException
		 */
		public function delete($id);

		/**
		 * Устанавливает фасад валют
		 * @param iCurrencyFacade $facade фасад валют
		 * @return $this
		 */
		public function setCurrencyFacade(iCurrencyFacade $facade);

		/**
		 * Устанавливает фасад типов цен
		 * @param iTypeFacade $facade
		 * @return $this
		 */
		public function setTypeFacade(iTypeFacade $facade);
	}