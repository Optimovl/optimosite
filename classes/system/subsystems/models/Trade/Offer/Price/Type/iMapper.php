<?php
	namespace UmiCms\System\Trade\Offer\Price\Type;

	use UmiCms\System\Orm\Entity\iMapper as iAbstractMapper;

	/**
	 * Интерфейс маппера типов цен
	 * @package UmiCms\System\Trade\Offer\Price\Type
	 */
	interface iMapper extends iAbstractMapper {

		/** @var string NAME имя столбца в бд для хранения названия */
		const NAME = 'name';

		/** @var string TITLE имя столбца в бд для хранения заголовка */
		const TITLE = 'title';

		/** @var string IS_DEFAULT имя столбца в бд для хранения значения флага "по умолчанию" */
		const IS_DEFAULT = 'is_default';
	}