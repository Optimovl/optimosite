<?php

	namespace UmiCms\System\Cache\Key\Validator;

	use UmiCms\System\Cache\Key\iValidator;

	/**
	 * Интерфейс фабрики валидаторов ключей кеша
	 * @package UmiCms\System\Cache\Key\Validator
	 */
	interface iFactory {

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration конфигурация
		 */
		public function __construct(\iConfiguration $configuration);

		/**
		 * Создает валидатор ключей кеша
		 * @param string|null $name класс валидатора (BlackList/WhiteList/MixedList),
		 * если не передан - загрузит из конфигурации
		 * @return iValidator
		 */
		public function create($name = null);
	}
