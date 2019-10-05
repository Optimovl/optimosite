<?php

	namespace UmiCms\Classes\System\Utils\Links;

	/**
	 * Карта констант коллекции источников ссылок
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	class SourceConstantMap extends \baseUmiCollectionConstantMap {

		/** @const string TABLE_NAME имя таблицы с источниками */
		const TABLE_NAME = 'cms3_links_sources';

		/** @const string EXCHANGE_RELATION_TABLE_NAME имя таблицы со связями импорта */
		const EXCHANGE_RELATION_TABLE_NAME = 'cms3_import_links_sources';

		/** @const string PLACE_FIELD_NAME название столбца с идентификатором ссылки */
		const LINK_ID_FIELD_NAME = 'link_id';

		/** @const string PLACE_FIELD_NAME название столбца с местом источника (адресом шаблона или объекта) */
		const PLACE_FIELD_NAME = 'place';

		/** @const string TYPE_FIELD_NAME название столбца с типом источника (шаблон или объект) */
		const TYPE_FIELD_NAME = 'type';
	}
