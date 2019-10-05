<?php

	namespace UmiCms\Classes\System\Utils\Seo\Settings;

	use UmiCms\Classes\System\Utils\Settings\iFactory as iSettingsFactory;

	/**
	 * Интерфейс фабрики SEO настроек
	 * @package UmiCms\Classes\System\Utils\Seo\Settings
	 */
	interface iFactory extends iSettingsFactory {

		/**
		 * @inheritdoc
		 * @return Common
		 */
		public function createCommon();

		/**
		 * @inheritdoc
		 * @return Custom
		 */
		public function createCustom($domainId = null, $langId = null);
	}