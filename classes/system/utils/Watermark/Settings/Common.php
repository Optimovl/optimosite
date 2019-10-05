<?php

	namespace UmiCms\Classes\System\Utils\Watermark\Settings;

	use UmiCms\Classes\System\Utils\Settings\Common as SettingsCommon;

	/**
	 * Класс для работы с настройками водяного знака, общими для всех сайтов
	 * @package UmiCms\Classes\System\Utils\Watermark\Settings
	 */
	class Common extends SettingsCommon implements iSettings {

		use \tUmiRegistryInjector;

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getImagePath() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/image");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setImagePath($path) {
			$this->getRegistry()->set("{$this->getPrefix()}/image", $path);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getAlpha() {
			return (int) $this->getRegistry()->get("{$this->getPrefix()}/alpha");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setAlpha($alpha) {
			$this->getRegistry()->set("{$this->getPrefix()}/alpha", $alpha);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getVerticalAlign() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/valign");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setVerticalAlign($align) {
			$this->getRegistry()->set("{$this->getPrefix()}/valign", $align);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getHorizontalAlign() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/halign");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setHorizontalAlign($align) {
			$this->getRegistry()->set("{$this->getPrefix()}/halign", $align);
			return $this;
		}

		/**
		 * Возвращает общий для настроек префикс в реестре
		 * @return string
		 */
		private function getPrefix() {
			return '//settings/watermark';
		}
	}
