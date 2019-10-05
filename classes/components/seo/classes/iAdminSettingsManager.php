<?php

	namespace UmiCms\Classes\Components\Seo;

	/**
	 * Интерфейс для управлением настройками SEO в административной панели
	 * @package UmiCms\Classes\Components\Seo
	 */
	interface iAdminSettingsManager {

		/**
		 * Возвращает настройки доступа к сайту (общие + специфические для каждого сайта)
		 * @return array
		 */
		public function getParams();

		/**
		 * Сохраняет настройки (общие + специфические для каждого сайта)
		 * @param array $params новые значения настроек
		 * @return $this
		 */
		public function setParams(array $params);
	}