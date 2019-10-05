<?php

	namespace UmiCms\System\Registry;

	/**
	 * Интерфейс реестра общих настроек системы
	 * @package UmiCms\System\Registry
	 */
	interface iSettings extends iPart {

		/**
		 * Возвращает доменный лицензионный ключ
		 * @return string
		 */
		public function getLicense();

		/**
		 * Возвращает версию системы
		 * @return string
		 */
		public function getVersion();

		/**
		 * Возвращает ревизию системы
		 * @return string
		 */
		public function getRevision();

		/**
		 * Устанавливает ревизию
		 * @param string $revision ревизия
		 * @return $this
		 */
		public function setRevision($revision);

		/**
		 * Возвращает редакцию системы
		 * @return string
		 */
		public function getEdition();

		/**
		 * Возвращает timestamp последнего обнвовления
		 * @return int
		 */
		public function getUpdateTime();

		/**
		 * Возвращает статус автоматического обновления
		 * @return string
		 */
		public function getStatus();
	}
