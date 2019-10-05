<?php

	namespace UmiCms\Classes\System\Utils\Links;

	use UmiCms\Classes\System\Enums\Enum;
	use UmiCms\Classes\System\Enums\EnumElementNotExistsException;

	/**
	 * Перечисление типов источников ссылок
	 * @package UmiCms\Classes\System\Utils\Links
	 */
	class SourceTypes extends Enum {

		/** @const string OBJECT_KEY тип источника ссылок - в объекте */
		const OBJECT_KEY = 'object';

		/** @const string TEMPLATE_KEY тип источника ссылок - в шаблоне */
		const TEMPLATE_KEY = 'template';

		/** @inheritdoc */
		protected function getDefaultValue() {
			throw new EnumElementNotExistsException('SourceTypes not provides default value');
		}
	}
