<?php

	/** Карта констант коллекции шаблонов писем */
	class mailTemplatesConstantMap extends baseUmiCollectionConstantMap {

		/** @const string имя таблицы, которая содержит данные о шаблонах */
		const TABLE_NAME = 'cms3_mail_templates';

		/** @const string имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_mail_templates';

		/** @const string название столбца для идентификатора уведомления */
		const NOTIFICATION_ID_FIELD_NAME = 'notification_id';

		/** @const string название столбца для типа шаблона */
		const TYPE_FIELD_NAME = 'type';

		/** @const string название столбца для содержимого */
		const CONTENT_FIELD_NAME = 'content';
	}
