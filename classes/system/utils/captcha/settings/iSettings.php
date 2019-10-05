<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Settings;

	/**
	 * Интерфейс настроек капчи
	 * @package UmiCms\Classes\System\Utils\Captcha\Settings
	 */
	interface iSettings {

		/**
		 * Возвращает название стратегии капчи
		 * @return string
		 */
		public function getStrategyName();

		/**
		 * Устанавливает название стратегии капчи
		 * @param string $name новое название
		 * @return $this
		 */
		public function setStrategyName($name);

		/**
		 * Возвращает настройку "Запоминать успешно пройденную пользователем CAPTCHA"
		 * @return bool
		 */
		public function shouldRemember();

		/**
		 * Устанавливает настройку "Запоминать успешно пройденную пользователем CAPTCHA"
		 * @param bool $flag новое значение
		 * @return $this
		 */
		public function setShouldRemember($flag);

		/**
		 * Возвращает настройку "Класс отрисовки изображений CAPTCHA"
		 * @return string
		 */
		public function getDrawerName();

		/**
		 * Устанавливает настройку "Класс отрисовки изображений CAPTCHA"
		 * @param string $name название класса
		 * @return $this
		 */
		public function setDrawerName($name);

		/**
		 * Возвращает настройку "параметр sitekey"
		 * @return string
		 */
		public function getSitekey();

		/**
		 * Устанавливает настройку "параметр sitekey"
		 * @param string $sitekey новое значение
		 * @return $this
		 */
		public function setSitekey($sitekey);

		/**
		 * Возвращает настройку "параметр secret"
		 * @return string
		 */
		public function getSecret();

		/**
		 * Устанавливает настройку "параметр secret"
		 * @param string $secret новое значение
		 * @return $this
		 */
		public function setSecret($secret);
	}
