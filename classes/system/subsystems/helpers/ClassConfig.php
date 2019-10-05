<?php

	/**
	 * Class ClassConfig
	 * Представляет конфигурацию класса
	 */
	class ClassConfig implements iClassConfig {

		/** @var array конфигурация класса */
		private $config;

		/**
		 * Конструктор
		 * @param string $class имя класса
		 * @param array $config конфигурация класса
		 * @throws Exception
		 */
		public function __construct($class, $config) {
			if (!$class) {
				throw new Exception('Передано некорректное название класса');
			}

			if (!is_array($config)) {
				throw new Exception('Передана некорректная конфигурация класса');
			}

			$this->config = $config;
		}

		/**
		 * Возвращает значение из конфигурации. Может принимать неограниченное число аргументов,
		 * которые разрешают путь к конкретному значению конфигурации.
		 * @example
		 * Конфигурация:
		 *  [
		 *        'some' => [
		 *            'test' => 'value'
		 *        ]
		 *    ];
		 * Код:
		 * $config->getValue('some', 'test'); //вернет строку 'value'
		 * $config->getValue('some'); //вернет массив ['test' => 'value']
		 *
		 * @return mixed
		 */
		public function get() {
			if (func_num_args() === 0) {
				return null;
			}

			$value = $this->config;

			foreach (func_get_args() as $option) {
				$value = $this->getSubConfig($value, $option);

				if ($value === null) {
					return $value;
				}
			}

			return $value;
		}

		/**
		 * Возвращет часть переданной конфигурации
		 * @param array $config конфигурация
		 * @param string $section секция конфигурации
		 * @return null
		 */
		protected function getSubConfig($config, $section) {
			if (is_array($config) && isset($config[$section])) {
				return $config[$section];
			}

			return null;
		}
	}

