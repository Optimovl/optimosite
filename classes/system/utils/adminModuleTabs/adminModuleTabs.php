<?php
	/**
	 * Класс для управления табами в админке модулей.
	 * Необходим для динамического изменния количества табов в модулях.
	 * Должен быть доступен для подключаемых библиотек модуля при инициализации
	 * При инициализации класса создается 2 экземпляра: 'common' и 'config', которые
	 * содержат модифицируемый список табов для админки модуля и для конфигурации модуля.
	 * После инициализации из шаблона вызывается макрос, который в зависимости от текущей страницы выбирает
	 * необходимый экземпляр класса и выводит список табов для отрисовки.
	 */
	class adminModuleTabs implements iAdminModuleTabs {

		/** @var array $tabs список табов */
		private $tabs = [];

		/** @inheritdoc */
		public function add($methodName, $aliases = []) {
			$this->tabs[$methodName] = $aliases;
		}

		/** @inheritdoc */
		public function get($methodName) {
			if (isset($this->tabs[$methodName])) {
				return $this->tabs[$methodName];
			}
			return false;
		}

		/** @inheritdoc */
		public function getTabNameByAlias($method) {
			if (isset($this->tabs[$method])) {
				return $method;
			}

			foreach ($this->tabs as $tabMethod => $aliases) {
				if (in_array($method, (array) $aliases)) {
					return $tabMethod;
				}
			}

			return false;
		}

		/** @inheritdoc */
		public function remove($methodName) {
			if (isset($this->tabs[$methodName])) {
				unset($this->tabs[$methodName]);
				return true;
			}
			return false;
		}

		/** @inheritdoc */
		public function getAll() {
			return (umiCount($this->tabs) > 1) ? $this->tabs : [];
		}

		/** @inheritdoc */
		public function getRealAll() {
			return $this->tabs;
		}
	}

