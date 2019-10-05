<?php

	namespace UmiCms\System\Registry;

	/**
	 * Класс реестра общих настроек системы
	 * @package UmiCms\System\Registry
	 */
	class Settings extends Part implements iSettings {

		/** @const string PATH_PREFIX префикс пути для ключей */
		const PATH_PREFIX = '//settings';

		/** @inheritdoc */
		public function __construct(\iRegedit $storage) {
			parent::__construct($storage);
			parent::setPathPrefix(self::PATH_PREFIX);
		}

		/** @inheritdoc */
		public function setPathPrefix($prefix) {
			return $this;
		}

		/** @inheritdoc */
		public function getLicense() {
			return (string) $this->get('keycode');
		}

		/** @inheritdoc */
		public function getVersion() {
			return (string) $this->get('system_version');
		}

		/** @inheritdoc */
		public function getRevision() {
			return (string) $this->get('system_build');
		}

		/** @inheritdoc */
		public function setRevision($revision) {
			$this->set('system_build', $revision);
			return $this;
		}

		/** @inheritdoc */
		public function getEdition() {
			return (string) $this->get('system_edition');
		}

		/** @inheritdoc */
		public function getUpdateTime() {
			return (int) $this->get('last_updated');
		}

		/** @inheritdoc */
		public function getStatus() {
			return (string) $this->get('status');
		}
	}
