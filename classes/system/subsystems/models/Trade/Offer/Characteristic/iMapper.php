<?php
	namespace UmiCms\System\Trade\Offer\Characteristic;

	use UmiCms\System\Orm\Entity\iMapper as iAbstractMapper;

	/**
	 * Интерфейс маппера характеристики торгового предложения
	 * @package UmiCms\System\Trade\Offer\Characteristic
	 */
	interface iMapper extends iAbstractMapper {

		/** @var string NAME ключ имени поля */
		const NAME = 'name';

		/** @var string TITLE ключ заголовка поля */
		const TITLE = 'title';

		/** @var string FIELD_TYPE ключ типа поля */
		const FIELD_TYPE = 'field_type';

		/** @var string VIEW_TYPE ключ типа отображения */
		const VIEW_TYPE = 'view_type';

		/** @var string IS_MULTIPLE ключ множественности поля */
		const IS_MULTIPLE = 'is_multiple';

		/** @var string DATA_OBJECT_ID ключ идентификатор объекта данных */
		const DATA_OBJECT_ID = 'data_object_id';

		/** @var string VALUE ключ значения поля */
		const VALUE = 'value';
	}