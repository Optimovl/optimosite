<?php

	/**
	 * Class selectorOption
	 * @method value($value) устанавливает значение свойства
	 */
	class selectorOption {

		protected $name;

		protected $value = [];

		protected $allowedOptionList = [
			'or-mode',
			'root',
			'exclude-nested',
			'return',
			'no-length',
			'no-permissions',
			'load-all-props',
			'search-in-related-object',
			'ignore-children-types',
			'ignore-translate'
		];

		public function __construct($name) {
			if (in_array($name, $this->allowedOptionList)) {
				$this->name = $name;
			} else {
				throw new selectorException("Unknown option \"{$name}\"");
			}
		}

		public function __call($method, $args) {
			$allowedMethods = ['all', 'field', 'fields'];
			$method = mb_strtolower($method);
			if (in_array($method, $allowedMethods)) {
				$value = false;
				if ($method == 'all') {
					$value = true;
				} elseif (umiCount($args)) {
					$value = [];

					foreach ($args as $argValue) {
						$argValueList = (array) $argValue;
						foreach ($argValueList as $argValueListItem) {
							$value[] = $argValueListItem;
						}
					}
				}
				if ($value !== false) {
					$this->value[$method] = $value;
				}
			} elseif ($method == 'value') {
				$argsCount = umiCount($args);

				if ($argsCount) {
					if ($this->name == 'or-mode') {
						$this->value['all'] = true;
					} else {
						if ($argsCount == 1 && (is_array($args[0]) || $args[0] === true || $args[0] === false)) {
							$this->value = $args[0];
						} else {
							$this->value = $args;
						}
					}
				} else {
					$this->value = null;
				}
			} else {
				throw new selectorException("This property doesn't support \"{$method}\" method");
			}
		}

		/**
		 * Возвращает значение свойства
		 * @param string $name название
		 * @return mixed
		 */
		public function __get($name) {
			return $this->$name;
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $name имя свойства
		 * @return bool
		 */
		public function __isset($name) {
			return property_exists(get_class($this), $name);
		}
	}
