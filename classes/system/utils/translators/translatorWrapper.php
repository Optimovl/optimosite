<?php

	use UmiCms\System\Orm\iEntity;

	/** Абстрактный класс xml транслятора (сериализатора) некоторой сущности системы */
	abstract class translatorWrapper {

		/**
		 * @var bool $showEmptyFields флаг необходимости переводить пустые поля iUmiObject и iUmiHierarchyElement.
		 * @todo: убрать из этого класса, так как класс абстрактный, а поле относится к его потомкам
		 */
		public static $showEmptyFields = false;

		/** @var translatorWrapper[] $wrapperList список загруженных экземпляров классов, отвественных за перевод объектов */
		protected static $wrapperList = [];

		/**
		 * @var array $optionList опции сериализации
		 *
		 * [
		 *      'name' => 'value'
		 * ]
		 */
		private $optionList = [
			'serialize-related-entities' => false // флаг необходимости производить сериализацию связанных сущностей
		];

		/**
		 * Переводит данные объекта в массив для последующей xml сериализации
		 * @param iUmiEntinty|Exception|iEntity $object
		 * @return array
		 */
		abstract public function translate($object);

		/**
		 * Устанавливает опцию сериализации
		 * @param string $name имя
		 * @param mixed $value значение
		 * @return $this;
		 * @throws InvalidArgumentException
		 */
		final public function setOption($name, $value) {
			$this->validateOptionName($name);
			$this->optionList[$name] = $value;
			return $this;
		}

		/**
		 * Возвращает экземпляр класса, ответственного за перевод данных заданного объекта
		 * @param iUmiEntinty|Exception|iEntity $object переводимый объект
		 * @return translatorWrapper
		 * @throws coreException
		 */
		final public static function get($object) {
			if (!is_object($object)) {
				throw new coreException('Object required to apply class translation');
			}

			$className = self::getClassAlias($object);
			$wrapper = self::loadWrapper($className);

			if ($wrapper instanceof translatorWrapper) {
				$wrapper->setOption('serialize-related-entities', false);
				return $wrapper;
			}

			throw new coreException("Can't load translation wrapper for class \"{$className}\"");
		}

		/**
		 * Получает экземпляр класса, ответственного за перевод данных объектов заданного класса
		 * @param string $className класс переводимых объектов
		 * @return translatorWrapper
		 * @throws coreException
		 */
		final protected static function loadWrapper($className) {
			if (isset(self::$wrapperList[$className]) && self::$wrapperList[$className] instanceof translatorWrapper) {
				return self::$wrapperList[$className];
			}

			$wrapperClassName = $className . 'Wrapper';

			if (!class_exists($wrapperClassName)) {
				$filePath = __DIR__ . '/wrappers/' . $className . 'Wrapper.php';

				if (!is_file($filePath)) {
					throw new coreException(
						"Can't load file \"{$filePath}\" to translate object of class \"{$className}\""
					);
				}

				require $filePath;
			}

			if (!class_exists($wrapperClassName)) {
				throw new coreException("Translation wrapper class \"{$wrapperClassName}\" not found");
			}

			$wrapper = new $wrapperClassName();

			if (!$wrapper instanceof translatorWrapper) {
				throw new coreException(
					"Translation wrapper class \"{$wrapperClassName}\" should be instance of translatorWrapper"
				);
			}

			return self::$wrapperList[$className] = $wrapper;
		}

		/**
		 * Возвращает значение опции сериализации
		 * @param string $name имя
		 * @return mixed|null
		 * @throws InvalidArgumentException
		 */
		final protected function getOption($name) {
			$this->validateOptionName($name);

			if (!isset($this->optionList[$name])) {
				return null;
			}

			return $this->optionList[$name];
		}

		/**
		 * Возвращает список опций
		 * @return array
		 */
		final protected function getOptionList() {
			return $this->optionList;
		}

		/**
		 * Возвращает алиас класса объекта, для которого должен быть доступен класс перевода данных
		 * @param iUmiEntinty|Exception $object переводимый объект
		 * @return string
		 */
		protected static function getClassAlias($object) {
			$baseClasses = [
				'baseRestriction',
				'publicException'
			];

			$aliases = [
				'umiObjectProperty' => [
					'umiObjectPropertyPrice',
					'umiObjectPropertyFloat',
					'umiObjectPropertyTags',
					'umiObjectPropertyBoolean',
					'umiObjectPropertyImgFile',
					'umiObjectPropertyRelation',
					'umiObjectPropertyText',
					'umiObjectPropertyDate',
					'umiObjectPropertyInt',
					'umiObjectPropertyString',
					'umiObjectPropertyWYSIWYG',
					'umiObjectPropertyFile',
					'umiObjectPropertyPassword',
					'umiObjectPropertySymlink',
					'umiObjectPropertyCounter',
					'umiObjectPropertyOptioned',
					'umiObjectPropertyColor',
					'umiObjectPropertyLinkToObjectType',
					'umiObjectPropertyMultipleImgFile',
					'UmiCms\System\Data\Object\Property\Value\OfferId',
					'UmiCms\System\Data\Object\Property\Value\DomainId',
					'UmiCms\System\Data\Object\Property\Value\OfferIdList',
					'UmiCms\System\Data\Object\Property\Value\DomainIdList'
				],

				'umiFile' => [
					'umiImageFile'
				],

				'Offer' => [
					'UmiCms\System\Trade\Offer'
				],

				'OfferPrice' => [
					'UmiCms\System\Trade\Offer\Price'
				],

				'OfferPriceCollection' => [
					'UmiCms\System\Trade\Offer\Price\Collection'
				],

				'StockBalance' => [
					'UmiCms\System\Trade\Stock\Balance'
				],

				'StockBalanceCollection' => [
					'UmiCms\System\Trade\Stock\Balance\Collection'
				],

				'OfferCharacteristic' => [
					'UmiCms\System\Trade\Offer\Characteristic'
				],

				'OfferCharacteristicCollection' => [
					'UmiCms\System\Trade\Offer\Characteristic\Collection'
				]
			];

			$className = get_class($object);

			foreach ($aliases as $baseClassName => $alias) {
				if (in_array($className, $alias)) {
					return $baseClassName;
				}
			}

			foreach ($baseClasses as $baseClass) {
				if (in_array($baseClass, class_parents($object))) {
					return $baseClass;
				}
			}

			return $className;
		}

		/**
		 * Валидирует имя опции сериализации
		 * @param $name
		 */
		private function validateOptionName($name) {
			if (!is_string($name) || $name === '') {
				throw new InvalidArgumentException('Incorrect option key given');
			}
		}

		/** @deprecated */
		public $isFull = false;
	}
