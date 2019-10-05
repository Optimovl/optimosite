<?php

	namespace UmiCms\Classes\System\Utils\Seo\Settings;

	use UmiCms\Classes\System\Utils\Settings\Factory as SettingsFactory;

	/**
	 * Фабрика SEO настроек
	 * @package UmiCms\Classes\System\Utils\Seo\Settings
	 */
	class Factory extends SettingsFactory implements iFactory {

		/** @inheritdoc */
		protected function getCommon() {
			return new Common($this->getRegistry());
		}

		/**
		 * @inheritdoc
		 * @throws \ErrorException
		 */
		protected function getCustom($domainId = null, $langId = null) {
			return new Custom($domainId, $langId, $this->getRegistry());
		}
	}