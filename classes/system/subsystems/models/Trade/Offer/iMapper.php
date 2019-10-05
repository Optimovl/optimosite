<?php
	namespace UmiCms\System\Trade\Offer;

	use UmiCms\System\Orm\Entity\iMapper as iAbstractMapper;

	/**
	 * Интерфейс маппера торговых предложений
	 * @package UmiCms\System\Trade\Offer
	 */
	interface iMapper extends iAbstractMapper {

		/** @var string TYPE_ID имя столбца в бд для хранения идентификатора типа данных */
		const TYPE_ID = 'type_id';

		/** @var string DATA_OBJECT_ID имя столбца в бд для хранения идентификатора объекта данных */
		const DATA_OBJECT_ID = 'data_object_id';

		/** @var string NAME имя столбца в бд для хранения названия */
		const NAME = 'name';

		/** @var string VENDOR_CODE имя столбца в бд для хранения артикула */
		const VENDOR_CODE = 'vendor_code';

		/** @var string BAR_CODE имя столбца в бд для хранения штрихкода */
		const BAR_CODE = 'bar_code';

		/** @var string TOTAL_COUNT имя столбца в бд для хранения общего количества на складе */
		const TOTAL_COUNT = 'total_count';

		/** @var string IS_ACTIVE имя столбца в бд для хранения статуса активности */
		const IS_ACTIVE = 'is_active';

		/** @var string ORDER имя столбца в бд для хранения индекса сортировки */
		const ORDER = 'order';

		/** @var string WEIGHT имя столбца в бд для хранения веса */
		const WEIGHT = 'weight';

		/** @var string WIDTH имя столбца в бд для хранения ширины */
		const WIDTH = 'width';

		/** @var string LENGTH имя столбца в бд для хранения длины */
		const LENGTH = 'length';

		/** @var string HEIGHT имя столбца в бд для хранения высоты */
		const HEIGHT = 'height';

		/** @var string TYPE имя связи - тип торгового предложения  */
		const TYPE = 'type';

		/** @var string DATA_OBJECT имя связи - объект данных торгового предложения  */
		const DATA_OBJECT = 'data_object';

		/** @var string PRICE_COLLECTION имя связи - коллекция цен  */
		const PRICE_COLLECTION = 'price_collection';

		/** @var string STOCK_BALANCE_COLLECTION имя связи - коллекция складских остатков */
		const STOCK_BALANCE_COLLECTION = 'stock_balance_collection';

		/** @var string CHARACTERISTIC_COLLECTION имя связи - коллекция характеристик */
		const CHARACTERISTIC_COLLECTION = 'characteristic_collection';
	}