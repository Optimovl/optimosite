<?php

	/** Карта констант коллекции уведомлений */
	class mailNotificationsConstantMap extends baseUmiCollectionConstantMap {

		/** @const string имя таблицы, которая содержит данные о шаблонах */
		const TABLE_NAME = 'cms3_mail_notifications';

		/** @const string имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_mail_notifications';

		/** @const string название столбца для идентификатора языка */
		const LANG_ID_FIELD_NAME = 'lang_id';

		/** @const string название столбца для идентификатора домена */
		const DOMAIN_ID_FIELD_NAME = 'domain_id';

		/** @const string название столбца для модуля, в котором используется уведомление */
		const MODULE_FIELD_NAME = 'module';
	}
