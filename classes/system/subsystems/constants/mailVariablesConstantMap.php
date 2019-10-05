<?php

	/** Карта констант коллекции переменных шаблонов писем */
	class mailVariablesConstantMap extends baseUmiCollectionConstantMap {

		/** @const string имя таблицы, которая содержит данные о перемнных */
		const TABLE_NAME = 'cms3_mail_variables';

		/** @const string имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_mail_variables';

		/** @const string название столбца для идентификатора шаблона */
		const TEMPLATE_ID_FIELD_NAME = 'template_id';

		/** @const string название столбца для переменной */
		const VARIABLE_FIELD_NAME = 'variable';
	}
