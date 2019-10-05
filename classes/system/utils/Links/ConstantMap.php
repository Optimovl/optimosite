<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/** Карта констант коллекции ссылок */
	class ConstantMap extends \baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME имя таблицы с ссылками */
		const TABLE_NAME = 'cms3_links';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_links';

		/** @const string ADDRESS_FIELD_NAME название столбца с адресом ссылки */
		const ADDRESS_FIELD_NAME = 'address';

		/** @const string ADDRESS_HASH_FIELD_NAME название столбца с хешем адреса ссылки */
		const ADDRESS_HASH_FIELD_NAME = 'address_hash';

		/** @const string PLACE_FIELD_NAME название столбца с местом ссылки (адрес страницы, где она найдена) */
		const PLACE_FIELD_NAME = 'place';

		/** @const string BROKEN_FIELD_NAME название столбца со статусом работоспособности ссылки */
		const BROKEN_FIELD_NAME = 'broken';

	}
