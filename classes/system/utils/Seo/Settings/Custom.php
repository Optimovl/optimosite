<?php

	namespace UmiCms\Classes\System\Utils\Seo\Settings;

	use UmiCms\Classes\System\Utils\Settings\Custom as SettingsCustom;

	/**
	 * Класс SEO настроек отдельных для каждого сайта
	 * @package UmiCms\Classes\System\Utils\Seo\Settings
	 */
	class Custom extends SettingsCustom implements iSettings {

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getTitlePrefix() {
			return (string) $this->getRegistry()
				->get("{$this->getMetaPrefix('title_prefix')}");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setTitlePrefix($prefix) {
			$this->getRegistry()->set("{$this->getMetaPrefix('title_prefix')}", $prefix);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getDefaultTitle() {
			return (string) $this->getRegistry()
				->get("{$this->getMetaPrefix('default_title')}");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setDefaultTitle($title) {
			$this->getRegistry()->set("{$this->getMetaPrefix('default_title')}", $title);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getDefaultKeywords() {
			return (string) $this->getRegistry()
				->get("{$this->getMetaPrefix('meta_keywords')}");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setDefaultKeywords($keywords) {
			$this->getRegistry()->set("{$this->getMetaPrefix('meta_keywords')}", $keywords);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getDefaultDescription() {
			return (string) $this->getRegistry()
				->get("{$this->getMetaPrefix('meta_description')}");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setDefaultDescription($description) {
			$this->getRegistry()->set("{$this->getMetaPrefix('meta_description')}", $description);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function isCaseSensitive() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/case-sensitive");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setCaseSensitive($value) {
			$this->getRegistry()->set("{$this->getPrefix()}/case-sensitive", $value);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getCaseSensitiveStatus() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/case-sensitive-status");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setCaseSensitiveStatus($value) {
			$this->getRegistry()->set("{$this->getPrefix()}/case-sensitive-status", $value);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function isProcessRepeatedSlashes() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/process-slashes");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setProcessRepeatedSlashes($value) {
			$this->getRegistry()->set("{$this->getPrefix()}/process-slashes", $value);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function getProcessRepeatedSlashesStatus() {
			return (string) $this->getRegistry()->get("{$this->getPrefix()}/process-slashes-status");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setProcessRepeatedSlashesStatus($status) {
			$this->getRegistry()->set("{$this->getPrefix()}/process-slashes-status", $status);
			return $this;
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function isAddIdToDuplicateAltName() {
			return (bool) $this->getRegistry()->get("{$this->getPrefix()}/add-id-to-alt-name");
		}

		/**
		 * @inheritdoc
		 * @throws \Exception
		 */
		public function setAddIdToDuplicateAltName($value) {
			$this->getRegistry()->set("{$this->getPrefix()}/add-id-to-alt-name", $value);
			return $this;
		}

		/** @inheritdoc */
		protected function getPrefix() {
			return "//settings/seo/{$this->domainId}/{$this->langId}";
		}

		/**
		 * Возвращает общий для мета-тэгов префикс в реестре
		 * @param string $name имя мета-тэга
		 * @return string
		 */
		private function getMetaPrefix($name) {
			return "//settings/$name/{$this->domainId}/{$this->langId}";
		}
	}