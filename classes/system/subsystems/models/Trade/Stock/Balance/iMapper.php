<?php
	namespace UmiCms\System\Trade\Stock\Balance;

	use UmiCms\System\Orm\Entity\iMapper as iAbstractMapper;

	/**
	 * Интерфейс маппера складских остатков
	 * @package UmiCms\System\Trade\Stock\Balance
	 */
	interface iMapper extends iAbstractMapper {

		/** @var string OFFER_ID имя столбца в бд для хранения идентификатора торгового предложения */
		const OFFER_ID = 'offer_id';

		/** @var string STOCK_ID имя столбца в бд для хранения идентификатора склада */
		const STOCK_ID = 'stock_id';

		/** @var string VALUE имя столбца в бд для хранения значения остатка */
		const VALUE = 'value';

		/** @var string OFFER имя связи - склад  */
		const OFFER = 'offer';

		/** @var string STOCK имя связи - торговое предложение */
		const STOCK = 'stock';
	}