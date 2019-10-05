<?php

	namespace UmiCms\System\Cache\Key;

	/**
	 * Интерфейс валидатора ключей кеша
	 * @package UmiCms\System\Cache\Key
	 */
	interface iValidator {

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(\iConfiguration $configuration);

		/**
		 * Определяет валидность ключа кеша
		 * @param string $key ключ кеша
		 * @return bool
		 */
		public function isValid($key);
	}
