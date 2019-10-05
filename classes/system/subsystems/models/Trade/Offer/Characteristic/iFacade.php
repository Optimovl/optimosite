<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use \iUmiField as iField;
	use UmiCms\System\Trade\iOffer;
	use \iUmiFieldsCollection as iFieldFacade;
	use UmiCms\System\Trade\Offer\iCharacteristic;
	use UmiCms\System\Trade\Offer\iCollection as iOfferCollection;
	use UmiCms\System\Trade\Offer\Data\Object\iFacade as iObjectFacade;
	use UmiCms\System\Trade\Offer\Data\Object\Type\iFacade as iTypeFacade;

	/**
	 * Интерфейс фасада характеристик торговых предложений
	 * @package UmiCms\System\Trade\Offer\Characteristic
	 */
	interface iFacade {

		/** @var string GROUP_NAME имя группы полей */
		const GROUP_NAME = 'trade_offers';

		/** @var string OFFER_LIST_FIELD_NAME имя поля со списком торговых предложений */
		const OFFER_LIST_FIELD_NAME = 'trade_offer_list';

		/** @var array FIELD_TYPE_WHITE_LIST список разрешенных типов полей */
		const FIELD_TYPE_WHITE_LIST = [
			'img_file', 'string', 'int', 'float', 'date', 'boolean', 'file', 'text', 'relation'
		];

		/**
		 * Конструктор
		 * @param iMapper $mapper маппер характеристик
		 * @param iFactory $factory фабрика характеристик
		 * @param iCollection $collection коллекция характеристик
		 * @param iObjectFacade $objectFacade фасад объектов данных торговых предложений
		 * @param iTypeFacade $typeFacade фасад типов торговых предложений
		 * @param iFieldFacade $fieldFacade фасад полей
		 */
		public function __construct(
			iMapper $mapper,
			iFactory $factory,
			iCollection $collection,
			iObjectFacade $objectFacade,
			iTypeFacade $typeFacade,
			iFieldFacade $fieldFacade
		);

		/**
		 * Возвращает коллекцию характеристик для заданного типа торговых предложений
		 * @param int $id идентификатор типа торгового предложения
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \expectObjectTypeException
		 */
		public function getCollectionByType($id);

		/**
		 * Возвращает коллекцию характеристик торгового предложения
		 * @param iOffer $offer торговое предложение
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		public function getCollectionByOffer(iOffer $offer);

		/**
		 * Возвращает коллекцию характеристик для коллекции торговых предложений
		 * @param iOfferCollection $offerCollection коллекция торговых предложений
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ExpectFieldGroupException
		 * @throws \ReflectionException
		 * @throws \coreException
		 * @throws \expectObjectException
		 * @throws \expectObjectTypeException
		 */
		public function getCollectionByOfferCollection(iOfferCollection $offerCollection);

		/**
		 * Создает характеристику для торгового предложения и заданного поля
		 * @param iOffer $offer торговое предложение
		 * @param iField $field поле
		 * @return iCharacteristic
		 * @throws \ErrorException
		 * @throws \coreException
		 * @throws \databaseException
		 * @throws \expectObjectException
		 */
		public function createByOfferAndField(iOffer $offer, iField $field);

		/**
		 * Создает характеристику для торгового предложения и поля с заданным именем
		 * @param iOffer $offer торговое предложение
		 * @param string $name имя поля
		 * @return iCharacteristic
		 * @throws \ErrorException
		 * @throws \ExpectFieldException
		 * @throws \coreException
		 * @throws \databaseException
		 * @throws \expectObjectException
		 */
		public function createByOfferAndFieldName(iOffer $offer, $name);

		/**
		 * Формирует коллекцию характеристик из списка
		 * @param iCharacteristic[] $characteristicList список характеристик
		 * @return iCollection
		 * @throws \ErrorException
		 * @throws \ReflectionException
		 */
		public function mapCollection(array $characteristicList);

		/**
		 * Возвращает маппер характеристик торгового предложения
		 * @return iMapper
		 */
		public function getMapper();
	}