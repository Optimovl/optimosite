<?php

	/** Ядро библиотеки преобразований */
	class umiConversion {

		/**
		 * @var umiConversion экземпляр класса для Singleton-а
		 */
		private static $m_oInstance;

		/** @var array содержит уже загруженные процедуры преобазования */
		private $m_aProcedures = [];

		/** Private конструктор, для запрещения создания объекта извне */
		private function __construct() {
			// Nothing to do        
		}

		/** Деструктор для совершения пост-действий (если таковые имеются) */
		public function __destruct() {
			// Nothing to do
		}

		/**
		 * Возвращает экземпляр класса
		 * @return umiConversion
		 */
		public static function getInstance() {
			if (self::$m_oInstance == null) {
				self::$m_oInstance = new umiConversion();
			}
			return self::$m_oInstance;
		}

		/**
		 * Перегруженный оператор вызова: транслирует вызов на соответствующую процедуру преобразования
		 * @param string $_sMethodName Имя преобразования
		 * @param array $_aMethodArguments Аргументы преобразования
		 * @return Mixed Результат преобразования
		 */
		public function __call($_sMethodName, $_aMethodArguments) {
			if (!isset($this->m_aProcedures[$_sMethodName])) {
				$sClassName = $_sMethodName;
				$sIncludePath = dirname(__FILE__) . '/procedures/' . $sClassName . '.conversion.php';
				if (!file_exists($sIncludePath)) {
					return (umiCount($_aMethodArguments) == 1) ? $_aMethodArguments[0] : $_aMethodArguments;
				}
				include $sIncludePath;
				$oConversion = new $sClassName();
				$this->m_aProcedures[$_sMethodName] = $oConversion;
			}
			$oConversion = $this->m_aProcedures[$_sMethodName];
			if ($oConversion instanceof IGenericConversion) {
				return $oConversion->convert($_aMethodArguments);
			} else {
				return (umiCount($_aMethodArguments) == 1) ? $_aMethodArguments[0] : $_aMethodArguments;
			}
		}
	}

