<?php

	namespace UmiCms\Classes\System\Utils\Seo\Settings;

	use UmiCms\Classes\System\Utils\Settings\iSettings as iMainSettings;

	/**
	 * Интерфейс SEO настроек
	 * @package UmiCms\Classes\System\Utils\Seo\Settings
	 */
	interface iSettings extends iMainSettings {

		/**
		 * Возвращает префикс title
		 * @return string
		 */
		public function getTitlePrefix();

		/**
		 * Изменяет префикс title
		 * @param string $prefix
		 * @return $this
		 */
		public function setTitlePrefix($prefix);

		/**
		 * Возвращает title по умолчанию
		 * @return string
		 */
		public function getDefaultTitle();

		/**
		 * Изменяет title по умолчанию
		 * @param string $title
		 * @return $this
		 */
		public function setDefaultTitle($title);

		/**
		 * Возвращает keywords по умолчанию
		 * @return string
		 */
		public function getDefaultKeywords();

		/**
		 * Изменяет keywords по умолчанию
		 * @param string $keywords
		 * @return $this
		 */
		public function setDefaultKeywords($keywords);

		/**
		 * Возвращает description по умолчанию
		 * @return string
		 */
		public function getDefaultDescription();

		/**
		 * Изменяет description по умолчанию
		 * @param string $description
		 * @return $this
		 */
		public function setDefaultDescription($description);

		/**
		 * Определяет чувствителен ли url к регистру
		 * @return bool
		 */
		public function isCaseSensitive();

		/**
		 * Изменяет чувствительность url к регистру
		 * @param bool $value
		 * @return $this
		 */
		public function setCaseSensitive($value);

		/**
		 * Возвращает статус ответа при обработке чувствительного к регистру URL
		 * @return bool
		 */
		public function getCaseSensitiveStatus();

		/**
		 * Изменяет статус ответа при обработке чувствительного к регистру URL
		 * @param bool $value
		 * @return $this
		 */
		public function setCaseSensitiveStatus($value);

		/**
		 * Определяет обрабатывать ли повторяющиеся слэши в url
		 * @return bool
		 */
		public function isProcessRepeatedSlashes();

		/**
		 * Устанавливает обрабатывать ли повторяющиеся слэши в url
		 * @param bool $value
		 * @return $this
		 */
		public function setProcessRepeatedSlashes($value);

		/**
		 * Возвращает статус ответа при обработке повторяющихся слэшей в url
		 * @return string
		 */
		public function getProcessRepeatedSlashesStatus();

		/**
		 * Изменяет статус ответа при обработке повторяющихся слэшей в url
		 * @param int $status
		 * @return $this
		 */
		public function setProcessRepeatedSlashesStatus($status);

		/**
		 * Добавлять ли к alt-name повторяющейся страницы ее идентификатор
		 * @return bool
		 */
		public function isAddIdToDuplicateAltName();

		/**
		 * Изменяет добавление к alt-name повторяющейся страницы ее идентификатора
		 * @param bool $value
		 * @return $this
		 */
		public function setAddIdToDuplicateAltName($value);
	}