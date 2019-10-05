<?php

	/** Класс события */
	class umiEventPoint implements iUmiEventPoint {

		/** @var string $id идентификатор */
		private $id;

		/** @var string $mode режим вызова */
		private $mode;

		/**
		 * @var array $moduleList список модулей, обработчики которых поддерживаются событием.
		 * Если список пуст - значит событие поддерживает обработчики всех модулей.
		 *
		 * [
		 *      # => 'module name'
		 * ]
		 */
		private $moduleList = [];

		/**
		 * @var array $paramList список параметров
		 *
		 * [
		 *      name => value
		 * ]
		 */
		private $paramList = [];

		/**
		 * @var array $refList список ссылок
		 *
		 * [
		 *      name => &value
		 * ]
		 */
		private $refList = [];

		/** @var array $correctModeList список корректных режимов вызова событий */
		private static $correctModeList = [
			'before',
			'process',
			'after'
		];

		/** @inheritdoc */
		public function __construct($id) {
			$this->setId($id)->setMode();
		}

		/** @inheritdoc */
		public function getId() {
			return $this->id;
		}

		/** @inheritdoc */
		public function setMode($mode = 'process') {
			if (!is_string($mode) || empty($mode)) {
				throw new coreException('Incorrect mode given');
			}

			$mode = mb_strtolower($mode);
			$mode = trim($mode);

			if (!in_array($mode, self::$correctModeList)) {
				throw new coreException("Unknown mode given \"{$mode}\"");
			}

			$this->mode = $mode;
			return $this;
		}

		/** @inheritdoc */
		public function getMode() {
			return $this->mode;
		}

		/** @inheritdoc */
		public function setModules(array $moduleList = []) {
			$this->moduleList = $moduleList;
			return $this;
		}

		/** @inheritdoc */
		public function getModules() {
			return $this->moduleList;
		}

		/** @inheritdoc */
		public function setParam($name, $value = null) {
			if (!is_string($name) || empty($name)) {
				throw new coreException('Incorrect param name given');
			}

			$this->paramList[$name] = $value;
			return $this;
		}

		/** @inheritdoc */
		public function getParam($name) {
			if (!is_string($name) || empty($name)) {
				return null;
			}

			if (array_key_exists($name, $this->paramList)) {
				return $this->paramList[$name];
			}

			return null;
		}

		/** @inheritdoc */
		public function addRef($name, &$value) {
			if (!is_string($name) || empty($name)) {
				throw new coreException('Incorrect ref name given');
			}

			$this->refList[$name] = &$value;
			return $this;
		}

		/** @inheritdoc */
		public function &getRef($name) {
			$reference = null;

			if (!is_string($name) || empty($name)) {
				return $reference;
			}

			if (array_key_exists($name, $this->refList)) {
				$reference = &$this->refList[$name];
			}

			return $reference;
		}

		/**
		 * Устанавливает идентификатор события
		 * @param string $id идентификатор
		 * @return iUmiEventPoint
		 * @throws coreException
		 */
		private function setId($id) {
			if (!is_string($id) || empty($id)) {
				throw new coreException('Incorrect id given');
			}

			$this->id = $id;
			return $this;
		}

		/** @deprecated */
		public function getEventId() {
			return $this->getId();
		}

		/** @deprecated */
		public function call() {
			return umiEventsController::getInstance()->callEvent($this, $this->moduleList);
		}
	}
