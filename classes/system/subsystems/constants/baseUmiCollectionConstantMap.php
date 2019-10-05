<?php

	/** Базовая карта констант коллекции */
	class baseUmiCollectionConstantMap implements iUmiConstantMap {

		use tCommonConstantMap;

		/** @const string ID_FIELD_NAME название столбца для идентификатора сущности */
		const ID_FIELD_NAME = 'id';

		/** @const string NAME_FIELD_NAME название столбца для имени или названия сущности */
		const NAME_FIELD_NAME = 'name';

		/** @const string TITLE_FIELD_NAME название столбца для наименования сущности */
		const TITLE_FIELD_NAME = 'title';

		/** @const string TEXT_FIELD_NAME название столбца для текста */
		const TEXT_FIELD_NAME = 'text';

		/** @const string LINK_FIELD_NAME название столбца для ссылки */
		const LINK_FIELD_NAME = 'link';

		/** @const string IMAGE_FIELD_NAME название столбца для изображения */
		const IMAGE_FIELD_NAME = 'image';

		/** @const string IS_ACTIVE_FIELD_NAME название столбца для активности */
		const IS_ACTIVE_FIELD_NAME = 'is_active';

		/** @const string ORDER_FIELD_NAME порядка вывода */
		const ORDER_FIELD_NAME = 'order';

		/** @const string DOMAIN_ID_FIELD_NAME название столбца для идентификатора домена */
		const DOMAIN_ID_FIELD_NAME = 'domain_id';

		/** @const string LANGUAGE_ID_FIELD_NAME название столбца для идентификатора языка */
		const LANGUAGE_ID_FIELD_NAME = 'language_id';

		/** @const string DATE_FIELD_NAME название столбца для даты */
		const DATE_FIELD_NAME = 'date';

		/** @const string TIME_FIELD_NAME название столбца для времени */
		const TIME_FIELD_NAME = 'time';

		/** @const string PHONE_FIELD_NAME название столбца для телефона */
		const PHONE_FIELD_NAME = 'phone';

		/** @const string EMAIL_FIELD_NAME название столбца для почтового ящика */
		const EMAIL_FIELD_NAME = 'email';

		/** @const string COMMENT_FIELD_NAME название столбца для комментария */
		const COMMENT_FIELD_NAME = 'comment';

		/** @const string NAME_FIELD_NAME название столбца для стоимости */
		const PRICE_FIELD_NAME = 'price';

		/** @const string PHOTO_FIELD_NAME название столбца для фотографии */
		const PHOTO_FIELD_NAME = 'photo';

		/** @const string DESCRIPTION_FIELD_NAME название столбца для описания */
		const DESCRIPTION_FIELD_NAME = 'description';

		/** @const int STATUS_FIELD_NAME название столбца для статуса */
		const STATUS_FIELD_NAME = 'status';

		/** @const string SOURCE_FIELD_NAME название столбца для источника */
		const SOURCE_FIELD_NAME = 'source';

		/** @const string TARGET_FIELD_NAME название столбца для цели */
		const TARGET_FIELD_NAME = 'target';

		/** @const string EXTERNAL_ID_FIELD_NAME название столбца внешнего идентификатора */
		const EXTERNAL_ID_FIELD_NAME = 'external_id';

		/** @const string INTERNAL_ID_FIELD_NAME название столбца внутреннего идентификатора */
		const INTERNAL_ID_FIELD_NAME = 'internal_id';

		/** @const string UPDATE_TIME_FIELD_NAME название столбца даты обновления */
		const UPDATE_TIME_FIELD_NAME = 'update_time';

		/** @const string UPDATE_TIME_FIELD_NAME название столбца даты создания */
		const CREATE_TIME_FIELD_NAME = 'create_time';

		/** @const string TIME_START_FIELD_NAME название столбца для времени начала */
		const TIME_START_FIELD_NAME = 'time_start';

		/** @const string TIME_END_FIELD_NAME название столбца для времени окончания */
		const TIME_END_FIELD_NAME = 'time_end';

		/** @const string EMPLOYEE_ID_FIELD_NAME название столбца для идентификатора сотрудника */
		const EMPLOYEE_ID_FIELD_NAME = 'employee_id';

		/** @const string EMPLOYEE_ID_FIELD_NAME название столбца для идентификатора услуги */
		const SERVICE_ID_FIELD_NAME = 'service_id';

		/** @const string STATUS_ID_FIELD_NAME название столбца для идентификатора статуса */
		const STATUS_ID_FIELD_NAME = 'status_id';

		/** @const string GROUP_ID_FIELD_NAME название столбца для идентификатора группы */
		const GROUP_ID_FIELD_NAME = 'group_id';

		/** @const string INTEGER_FIELD_TYPE  идентификатор типа значения поля "число" */
		const INTEGER_FIELD_TYPE = 'int';

		/** @const string STRING_FIELD_TYPE  идентификатор типа значения поля "строка" */
		const STRING_FIELD_TYPE = 'string';

		/** @const string IMAGE_FIELD_TYPE тип поля "изображение" */
		const IMAGE_FIELD_TYPE = 'image';

		/** @const string DATE_FIELD_TYPE тип поля "дата" */
		const DATE_FIELD_TYPE = 'date';

		/** @const string FLOAT_FIELD_TYPE тип поля "число с точкой" */
		const FLOAT_FIELD_TYPE = 'float';

		/** @const string LIMIT_KEY ключ настроек ограничение количества */
		const LIMIT_KEY = 'limit';

		/** @const string OFFSET_KEY ключ настроек смещение */
		const OFFSET_KEY = 'offset';

		/** @const string COUNT_KEY ключ настроек количество */
		const COUNT_KEY = 'count';

		/** @const string ORDER_KEY ключ настроек сортировка */
		const ORDER_KEY = 'order_params';

		/** @const string ORDER_DIRECTION_ASC прямое направление сортировки */
		const ORDER_DIRECTION_ASC = 'ASC';

		/** @const string ORDER_DIRECTION_DESC обратное направление сортировки */
		const ORDER_DIRECTION_DESC = 'DESC';

		/** @const string LIKE_MODE_KEY ключ настроек режим "по вхождению строки" фильтра по значению поля */
		const LIKE_MODE_KEY = 'like_mode';

		/** @const string LIKE_MODE_COMPARE_MODE_KEY ключ параметров с настройками типа сравнения при фильтрации */
		const COMPARE_MODE_KEY = 'compare_mode';

		/** @const string CREATED_COUNTER_KEY ключ ответа со счетчиком импортированных редиректов */
		const CREATED_COUNTER_KEY = 'created';

		/** @const string UPDATED_COUNTER_KEY ключ ответа со счетчиком обновленных редиректов */
		const UPDATED_COUNTER_KEY = 'updated';

		/** @const string IMPORT_ERRORS_KEY ключ ответа со списком ошибок импорта */
		const IMPORT_ERRORS_KEY = 'errors';

		/** @const string CALCULATE_ONLY_KEY ключ полного вычисления количества не через FOUND_ROWS() */
		const CALCULATE_ONLY_KEY = 'calculate_only';

		/** @const string CHILDREN_COUNT_KEY ключ ответа с количеством дочерних сущностей */
		const CHILDREN_COUNT_KEY = '__children';

		/** @const string ENTITY_TYPE_KEY ключ ответа с типом сущности */
		const ENTITY_TYPE_KEY = '__type';

		/** @const string CONFIG_FIELD_REQUIRED_KEY ключ конфигурации поля со значением обязательности поля */
		const CONFIG_FIELD_REQUIRED_KEY = 'required';

		/** @const string CONFIG_FIELD_NAME_KEY ключ конфигурации поля с названием поля */
		const CONFIG_FIELD_NAME_KEY = 'name';

		/** @const string CONFIG_FIELD_TYPE_KEY ключ конфигурации поля с типом поля */
		const CONFIG_FIELD_TYPE_KEY = 'type';

		/** @const string MOVE_AFTER_MODE_KEY режим перемещения "После" */
		const MOVE_AFTER_MODE_KEY = 'after';

		/** @const string MOVE_BEFORE_MODE_KEY режим перемещения "До" */
		const MOVE_BEFORE_MODE_KEY = 'before';

		/** @const string MOVE_CHILD_MODE_KEY режим перемещения "Дочерним" */
		const MOVE_CHILD_MODE_KEY = 'child';
	}

