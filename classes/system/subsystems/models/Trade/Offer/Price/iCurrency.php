<?php
	namespace UmiCms\System\Trade\Offer\Price;

	/**
	 * Интерфейс валюты
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	interface iCurrency {

		/** @const string ID имя поля с идентификатором */
		const ID = 'id';

		/** @const string NAME имя поля с названием */
		const NAME = 'name';

		/** @const string CODE имя поля с ОКВ кодом */
		const CODE = 'codename';

		/** @const string ISO_CODE имя поля с ISO кодом */
		const ISO_CODE = 'iso_code';

		/** @const string DENOMINATION имя поля с номиналом */
		const DENOMINATION = 'nominal';

		/** @const string RATE имя поля с курсом */
		const RATE = 'rate';

		/** @const string PREFIX имя поля с префиксом */
		const PREFIX = 'prefix';

		/** @const string SUFFIX имя поля с суффиксом */
		const SUFFIX = 'suffix';

		/** @const string TYPE_GUID гуид типа */
		const TYPE_GUID = 'emarket-currency';

		/**
		 * Конструктор
		 * @param \iUmiObject $dataObject объект данных валюты
		 */
		public function __construct(\iUmiObject $dataObject);

		/**
		 * Возвращает идентификатор валюты
		 * @return int
		 */
		public function getId();

		/**
		 * Возвращает название валюты
		 * @return string
		 */
		public function getName();

		/**
		 * Возвращает ОКВ код валюты
		 * @link https://ru.wikipedia.org/wiki/Общероссийский_классификатор_валют
		 * @return string
		 */
		public function getCode();

		/**
		 * Возвращает ISO код валюты
		 * @link https://ru.wikipedia.org/wiki/ISO_4217
		 * @return string
		 */
		public function getISOCode();

		/**
		 * Возвращает номинал валюты
		 * @return int
		 */
		public function getDenomination();

		/**
		 * Устанавливает номинал валюты
		 * @param int $denomination номинал
		 * @return $this
		 */
		public function setDenomination($denomination);

		/**
		 * Возвращает курс валюты, относительно валюты по умолчанию
		 * @return float
		 */
		public function getRate();

		/**
		 * Устанавливает курс валюты, относительно валюты по умолчанию
		 * @param float $rate курс
		 * @return $this
		 */
		public function setRate($rate);

		/**
		 * Возвращает префикс валюты
		 * @return string
		 */
		public function getPrefix();

		/**
		 * Возвращает суффикс валюты
		 * @return string
		 */
		public function getSuffix();

		/**
		 * Возвращает значение свойства
		 * @param string $name
		 * @return mixed
		 * @throws \wrongParamException
		 */
		public function getValue($name);

		/**
		 * Форматирует цену
		 * @example: 100 => 100 руб.
		 * @param float $price
		 * @return string
		 */
		public function format($price);

		/**
		 * Возвращает объект данных валюты
		 * @return \iUmiObject
		 */
		public function getDataObject();
	}
