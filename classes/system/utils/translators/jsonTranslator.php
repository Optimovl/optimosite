<?php

	/** Класс json транслятора (сериализатора) */
	class jsonTranslator {

		/** @var string $result результат сериализации */
		protected $result = '';

		/** @var int $level текущий уровень вложенности json дерева */
		protected $level = 1;

		/** @var array $shortKeys соответствия сокращений названий ключей их полным названиям */
		protected static $shortKeys = [
			'@' => 'attribute',
			'#' => 'node',
			'+' => 'nodes',
			'%' => 'xlink',
			'*' => 'comment'
		];

		/** @var string|null имя callback функции */
		private $callback;

		/**
		 * Устанавливает Javascript callback функцию,
		 * которая будет использоваться в качестве обработчика JSON данных
		 * @param string $callback имя callback функции
		 * @example
		 * Пример имен функций: window.callback, someObject.callback, someCallback
		 */
		public function setCallback($callback) {
			$this->callback = trim(htmlspecialchars($callback));
		}

		/**
		 * Преобразует данные в JSON формат и выполняет дополнительную их обработку
		 * @param mixed $data данные, которые требуется преобразовать в JSON формат
		 * @return string результат преобразования
		 */
		public function translateToJson($data) {
			$json = $this->translate($data);
			$objectLiteral = $this->makeObjectLiteral($json);

			if ($this->callback) {
				return $this->makeCallbackNotation($objectLiteral);
			}

			return $objectLiteral;
		}

		/**
		 * Преобразует данные в JSON формат
		 * @param mixed $data данные, которые будут преобразованы
		 * @return string JSON-представление результата преобразований
		 */
		private function translate($data) {
			$this->chooseTranslator($data);
			return $this->result;
		}

		/**
		 * Преобразует представление набора свойств в JSON формате в литерал объекта с этими свойствами
		 * @param string $json JSON-представление свойств объекта
		 * @return string результат преобразований
		 */
		private function makeObjectLiteral($json) {
			return '{' . PHP_EOL . $json . PHP_EOL . '}';
		}

		/**
		 * Возвращает представление вызова callBack-функции
		 * @param string $arg JSON-представление аргумента, который будет передан в callback-функцию
		 * @return string
		 */
		private function makeCallbackNotation($arg) {
			return $this->callback . '(' . $arg . ');';
		}

		/**
		 * Сериализует данные в json
		 * @param mixed $data данные
		 * @param bool $isFull режим полной сериализации данных
		 */
		protected function chooseTranslator($data, $isFull = false) {
			switch (gettype($data)) {
				case 'array': {
					$this->translateArray($data);
					break;
				}

				case 'object': {
					$wrapper = translatorWrapper::get($data);
					$wrapper->setOption('serialize-related-entities', $isFull);

					$this->result .= "{\n";
					$this->level++;
					$this->chooseTranslator($wrapper->translate($data));
					$this->level--;

					$tabs = str_repeat("\t", $this->level);
					$this->result .= "\n" . $tabs . '}';
					break;
				}

				default: {
					$this->translateBasic($data);
				}
			}
		}

		/**
		 * Сериализует массив в json
		 * @param array $data Данные
		 */
		protected function translateArray($data) {
			$length = umiCount($data);
			$i = 0;

			foreach ($data as $key => $value) {
				$subKey = $this->getSubKey($key);
				$realKey = $this->getRealKey($key);

				$q = (++$i < $length) ? ",\n" : '';
				$tabs = str_repeat("\t", $this->level);

				//Patch for value->node:value case
				if (is_array($value) && umiCount($value) == 1) {
					$key = key($value);
					if (mb_substr($key, 0, 5) == 'node:') {
						$value = $value[$key];
					}
				}

				switch ($subKey) {
					case 'void': {
						$c2 = mb_substr($this->result, -2);
						$c3 = mb_substr($this->result, -3);

						if ($i == $length && (($c2 == ",\n" && $c = 2) || ($c3 == ",\n\n" && $c = 3))) {
							$this->result = mb_substr($this->result, 0, mb_strlen($this->result) - $c);
						}
						continue 2;
					}

					case 'list': {
						$this->result .= "{$tabs}\"{$realKey}\": ";

						if (is_array($value)) {
							$value = $this->cleanupArray($value);
						}

						$this->result .= json_encode($value);
						$this->result .= "{$q}\n";
						continue 2;
					}

					case 'xlink': {
						$value = '/' . str_replace('://', '/', $value) . '.json';
					}

					default: {
						if (is_array($value)) {
							if (umiCount($value) == 0) {
								$c2 = mb_substr($this->result, -2);
								$c3 = mb_substr($this->result, -3);
								if ($i == $length && (($c2 == ",\n" && $c = 2) || ($c3 == ",\n\n" && $c = 3))) {
									$this->result = mb_substr($this->result, 0, mb_strlen($this->result) - $c);
								}
								continue 2;
							}

							$this->result .= "{$tabs}\"{$realKey}\": {\n";
							++$this->level;
							$this->chooseTranslator($value);
							$this->result .= "\n{$tabs}}{$q}\n";
							--$this->level;
						} else {
							$this->result .= "{$tabs}\"{$realKey}\": ";
							$this->chooseTranslator($value, $subKey == 'full' || getRequest('viewMode') == 'full');
							$this->result .= "{$q}";
						}
					}
				}
			}
		}

		/**
		 * Преобразует ключи массива
		 * @param array $array
		 * @return array
		 */
		protected function cleanupArray(array $array) {
			$result = [];

			foreach ($array as $key => $value) {
				$result[$this->getRealKey($key)] = is_array($value) ? $this->cleanupArray($value) : $value;
			}

			return $result;
		}

		/**
		 * Сериализует скалярные данные в json
		 * @param mixed $data скалярные данные
		 */
		protected function translateBasic($data) {
			if (!is_string($data) && is_numeric($data)) {
				$this->result .= (float) $data;
			} else {
				if (function_exists('json_encode')) {
					$this->result .= json_encode($data);
				} else {
					$connection = ConnectionPool::getInstance()->getConnection();
					$this->result .= '"' . str_replace("'", "\'", $connection->escape($data)) . '"';
				}
			}
		}

		/**
		 * Возвращает правую часть ключа
		 * @param string $key ключ данных
		 * @return string
		 */
		public function getRealKey($key) {
			$first = mb_substr($key, 0, 1);

			if (isset(self::$shortKeys[$first])) {
				return mb_substr($key, 1);
			}

			$pos = mb_strpos($key, ':');

			if ($pos) {
				++$pos;
			} else {
				$pos = 0;
			}

			return mb_substr($key, $pos);
		}

		/**
		 * Возвращает левую часть ключа
		 * @param string $key ключ данных
		 * @return string
		 */
		public function getSubKey($key) {
			$first = mb_substr($key, 0, 1);

			if (isset(self::$shortKeys[$first])) {
				return self::$shortKeys[$first];
			}

			$pos = mb_strpos($key, ':');

			if ($pos) {
				return mb_substr($key, 0, $pos);
			}

			return false;
		}
	}
