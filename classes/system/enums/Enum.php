<?php

	namespace UmiCms\Classes\System\Enums;

	/**
	 * Класс абстрактного перечисления
	 * @example
	 *
	 *    class ColorCodes extends UmiCms\Classes\System\Enums\Enum {
	 *        const RED = 1;
	 *        const GREEN = 2;
	 *        const BLUE = 3;
	 *
	 *        protected function getDefaultValue() {
	 *            return self::RED;
	 *        }
	 *    }
	 *
	 * echo new ColorCodes(ColorCodes::RED) . PHP_EOL;
	 *
	 * @package UmiCms\Classes\System\Enums
	 */
	abstract class Enum implements iEnum {

		/** @var array $values значения перечисления */
		private $values;

		/** @var mixed $currentValue текущее значение перечисления */
		private $currentValue;

		/** @inheritdoc */
		public function __construct($currentValue = null) {
			if (func_num_args() === 0) {
				$currentValue = $this->getDefaultValue();
			}

			$this->setCurrentValue($currentValue);
		}

		/** @inheritdoc */
		public function __toString() {
			return (string) $this->getCurrentValue();
		}

		/** @inheritdoc */
		public function getAllValues() {
			if ($this->values === null) {
				$this->loadAllValues();
			}

			return $this->values;
		}

		/**
		 * Возвращает текущее значение перечисления по умолчанию
		 * @return mixed
		 */
		abstract protected function getDefaultValue();

		/**
		 * Возвращает текущее значение перечисления
		 * @return mixed
		 */
		protected function getCurrentValue() {
			return $this->currentValue;
		}

		/**
		 * Устанавливает текущее значение перечисления
		 * @param mixed $value текущее значение перечисления
		 * @return iEnum
		 * @throws EnumElementNotExistsException
		 */
		private function setCurrentValue($value) {
			if (!$this->isValidValue($value)) {
				throw new EnumElementNotExistsException('Incorrect enum value given');
			}

			$this->currentValue = $value;
			return $this;
		}

		/**
		 * Валидирует значение перечисления
		 * @param mixed $value проверяемое значение
		 * @return bool результат проверки
		 */
		private function isValidValue($value) {
			$values = self::getAllValues();
			return in_array($value, $values);
		}

		/** Загружает все возможные значение перечисления */
		private function loadAllValues() {
			$classReflection = new \ReflectionClass(get_class($this));
			$this->values = $classReflection->getConstants();
		}
	}
