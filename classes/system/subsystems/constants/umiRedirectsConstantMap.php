<?php

	/** Карта констант коллекции редиректов */
	class umiRedirectsConstantMap extends baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME имя таблицы с редиректами */
		const TABLE_NAME = 'cms3_redirects';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_redirects';

		/** @const int MADE_BY_USER_FIELD_NAME имя столбца с флагом "Сделан пользователем" */
		const MADE_BY_USER_FIELD_NAME = 'made_by_user';

		/** string CONFIG_SECTION название секции в config.ini, которая солержит настройки класса */
		const CONFIG_SECTION = 'seo';

		/** string CONFIG_URL_SUFFIX_ENABLE название параметра в config.ini, который включает суффикс адреса */
		const CONFIG_URL_SUFFIX_ENABLE = 'url-suffix.add';

		/** string CONFIG_URL_SUFFIX название параметра в config.ini, который содержит суффикс адреса */
		const CONFIG_URL_SUFFIX = 'url-suffix';

		/** string CONFIG_AUTO_CREATE_REDIRECT_ENABLE название параметра в config.ini, который включает автоматические редиректы */
		const CONFIG_AUTO_CREATE_REDIRECT_ENABLE = 'watch-redirects-history';

		/** string AUTO_CREATE_REDIRECT_HANDLER_METHOD метод обработчика события изменения страниц */
		const AUTO_CREATE_REDIRECT_HANDLER_METHOD = 'onModifyPageWatchRedirects';

		/** string AUTO_CREATE_REDIRECT_HANDLER_MODULE модуль обработчик события изменения страниц */
		const AUTO_CREATE_REDIRECT_HANDLER_MODULE = 'content';
	}
