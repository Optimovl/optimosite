<?php

	namespace UmiCms\Classes\System\Translators;

	/**
	 * Транслятор массива c обозначением XML-сущностей в массив,
	 * в котором отсутствует излишняя информация, описывающая XML
	 * Термины:
	 * Key - ключ исходного массива (обозначение XML-сущности включая ее имя)
	 * Symbol - обозначение XML-сущности, то есть тип сущности
	 * Branch - отдельная ветка исходного массива
	 * Class PhpTranslator
	 * @package UmiCms\Classes\System\Translators
	 */
	class PhpTranslator {

		/** @const символ, разделяющий обозначение XML-сущности и ее имя */
		const SYMBOL_DELIMITER = ':';

		/** @var array список коротких обозначений XML-сущностей */
		private static $shortSymbolList = [
			'@',
			'#',
			'+',
			'%',
			'*'
		];

		/** @var array список полных обозначений XML-сущностей */
		private static $fullSymbolList = [
			'attr',
			'attribute',
			'list',
			'nodes',
			'node',
			'void',
			'xml',
			'full',
			'xlink',
			'@xlink',
			'comment',
			'subnodes'
		];

		/** @var array список XML-сущностей, которые не будут транслированы */
		private static $redundantSymbolList = [
			'%',
			'xlink',
			'@xlink',
			'xml'
		];

		/** @var string $autoSubNodeSymbol специальное обозначение блока дочерних узлов */
		private static $autoSubNodeSymbol = 'items';

		/** @var bool $allKeysAreUseless любые ключи массива являются бесполезными для передачи структуры */
		private $allKeysAreUseless = true;

		/**
		 * Выполняет непосредственную трансляцию данных в массив,
		 * в котором отсутствует излишняя информация, описывающая XML
		 * @param mixed $data транслируемые данные
		 * @return array
		 */
		public function translate($data) {
			if (!$this->isTranslatable($data)) {
				return $data;
			}

			$result = [];

			foreach ($data as $key => $branch) {
				if ($this->isRedundant($key)) {
					continue;
				}

				$branch = $this->collapse($branch);

				$newKey = $this->cleanKey($key);
				$result[$newKey] = $this->translateBranch($branch);
			}

			return $result;
		}

		/**
		 * Устанавливает, что любые ключи массива являются бесполезными для передачи структуры
		 * @param bool $flag значение
		 * @return $this
		 */
		public function setAllKeysAreUseless($flag = true) {
			$this->allKeysAreUseless = (bool) $flag;
			return $this;
		}

		/**
		 * Транслирует данные ветки массива
		 * @param mixed $data транслируемые данные
		 * @return array результат трансляции
		 */
		private function translateBranch($data) {
			if (is_array($data)) {
				return $this->translate($data);
			}

			return $data;
		}

		/**
		 * Производит удаление излишних данных.
		 * Если передан массив, то он будет обработан таким образом, что из него будут удалены все ключи,
		 * которые не предоставляют полезной информации.
		 * @example
		 *
		 * С включенной опцией phpTranslator::$allKeysAreUseless:
		 *
		 * До
		 *
		 *  [
		 *      'foo' => [
		 *          'subnodes:items' => [
		 *              'bar' => 'baz'
		 *          ]
		 *      ]
		 *  ]
		 *
		 * После
		 *
		 *  [
		 *      'foo' => 'baz'
		 *  ]
		 *
		 * с выключенной опцией phpTranslator::$allKeysAreUseless:
		 *
		 * До
		 *
		 *  [
		 *      'foo' => [
		 *          'subnodes:items' => [   // бесполезный ключ, @see phpTranslator::isKeyUseless()
		 *              'baz' => 'baz'
		 *          ]
		 *      ]
		 *  ]
		 *
		 * После
		 *
		 *  [
		 *      'foo' => [
		 *          'baz' => 'baz'
		 *      ]
		 *  ]
		 *
		 * @param mixed $data обрабатываемые данные
		 * @return mixed нормализованные данные
		 */
		private function collapse($data) {
			if (!$this->isCollapsible($data)) {
				return $data;
			}

			$singleKey = $this->getFirstKey($data);
			$nextData = $data[$singleKey];

			if ($this->isKeyUseless($singleKey)) {
				return $this->collapse($nextData);
			}

			if ($this->isCollapsible($nextData)) {
				$nexSingleKey = $this->getFirstKey($nextData);
				$data[$singleKey] = $this->collapse($nextData[$nexSingleKey]);
			}

			return $data;
		}

		/**
		 * Проверяет что ключ являет бесполезным для структуры массива.
		 * Бесполезными являются ключи, которые содержат избыточную информацию для xmlTranslator,
		 * @see phpTranslator::cleanKey().
		 * @param string $key ключ
		 * @return bool
		 */
		private function isKeyUseless($key) {
			if ($this->allKeyAreUseless()) {
				return true;
			}

			$cleanedKey = $this->cleanKey($key);

			return ($cleanedKey !== $key || $key === self::$autoSubNodeSymbol);
		}

		/**
		 * Определяет, что любые ключи массива являются бесполезными для передачи структуры
		 * @return bool
		 */
		private function allKeyAreUseless() {
			return $this->allKeysAreUseless;
		}

		/**
		 * Возвращает первый ключ массива
		 * @param array $data
		 * @return mixed
		 */
		private function getFirstKey(array $data) {
			$arrayKeyList = array_keys($data);
			return array_shift($arrayKeyList);
		}

		/**
		 * Проверяет являются ли данные массивом, который можно "схлопнуть"
		 * @param mixed $data проверяемые данные
		 * @return bool
		 */
		private function isCollapsible($data) {
			if (!is_array($data)) {
				return false;
			}

			if (umiCount(array_keys($data)) !== 1) {
				return false;
			}

			$singleKey = $this->getFirstKey($data);

			if (!is_string($singleKey)) {
				return false;
			}

			return true;
		}

		/**
		 * Производит чистку ключа от излишней информации путем удаление его частей
		 * @param string $key ключ исходного массива
		 * @return string очищенный ключ
		 */
		private function cleanKey($key) {
			$short = $this->getShortSymbol($key);

			if ($short) {
				return mb_substr($key, 1);
			}

			$full = $this->getFullSymbol($key);

			if ($full) {
				return mb_substr($key, mb_strlen($full) + mb_strlen(self::SYMBOL_DELIMITER));
			}

			return $key;
		}

		/**
		 * Возвращает является ли ключ лишним, то есть будет ли он и его ветка включены в результирующий массив
		 * @param string $key ключ исходного массива
		 * @return bool
		 */
		private function isRedundant($key) {
			$symbol = $this->getSymbolFromKey($key);
			return in_array($symbol, self::$redundantSymbolList);
		}

		/**
		 * Возвращает обозначения XML-сущности ключа
		 * @param string $key ключ исходного массива
		 * @return null|string если обозначение было найдено, то строка с именем обозначения или null в ином случае
		 */
		private function getSymbolFromKey($key) {
			$full = $this->getFullSymbol($key);

			if ($full) {
				return $full;
			}

			return $this->getShortSymbol($key);
		}

		/**
		 * Возвращает короткое обозначение XML-сущности, которое содержится в переданном ключе
		 * @param string $key ключ исходного массива
		 * @return string|null если обозначение было найдено, то строка c именем обозначения или null в ином случае
		 */
		private function getShortSymbol($key) {
			$firstSymbol = mb_substr($key, 0, 1);
			$foundKey = array_search($firstSymbol, self::$shortSymbolList);

			if ($foundKey === false) {
				return null;
			}

			return self::$shortSymbolList[$foundKey];
		}

		/**
		 * Возвращает полное обозначение XML-сущности, которое содержится в переданном ключе
		 * @param string $key ключ исходного массива
		 * @return string|null если обозначение было найдено, то строка c именем обозначения или null в ином случае
		 */
		private function getFullSymbol($key) {
			$delimiterPosition = mb_strpos($key, self::SYMBOL_DELIMITER);

			if ($delimiterPosition === false) {
				return null;
			}

			$symbolCandidate = mb_substr($key, 0, $delimiterPosition);

			if (!in_array($symbolCandidate, self::$fullSymbolList)) {
				return null;
			}

			return $symbolCandidate;
		}

		/**
		 * Проверяет могут ли данные быть транслированы
		 * @param mixed $data проверяемые данные
		 * @return bool true, если данные находятся в подходящем для трансляции формате, false в ином случае
		 */
		private function isTranslatable($data) {
			return is_array($data);
		}
	}
