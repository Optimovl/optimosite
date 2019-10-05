<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\Entity\iMapper as iAbstractMapper;

	/**
	 * Интерфейс маппера цен
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	interface iMapper extends iAbstractMapper {

		/** @var string OFFER_ID имя столбца в бд для хранения идентификатора торгового предложения */
		const OFFER_ID = 'offer_id';

		/** @var string VALUE имя столбца в бд для хранения идентификатора валюты */
		const CURRENCY_ID = 'currency_id';

		/** @var string VALUE имя столбца в бд для хранения значения */
		const VALUE = 'value';

		/** @var string VALUE имя столбца в бд для хранения идентификатора типа */
		const TYPE_ID = 'type_id';

		/** @var string IS_ACTIVE имя столбца в бд для хранения флага цены "Основная" */
		const IS_MAIN = 'is_main';

		/** @var string OFFER имя связи - торговое предложение  */
		const OFFER = 'offer';

		/** @var string CURRENCY имя связи - валюта  */
		const CURRENCY = 'currency';

		/** @var string TYPE имя связи - тип  */
		const TYPE = 'type';
	}