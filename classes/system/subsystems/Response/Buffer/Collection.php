<?php

	namespace UmiCms\System\Response\Buffer;

	/**
	 * Класс коллекции буферов
	 * @package UmiCms\System\Response\Buffer
	 */
	class Collection implements iCollection {

		/** @var \iOutputBuffer[] $bufferList список созданных буферов */
		private $bufferList;

		/** @inheritdoc */
		public function get($class) {
			if (!$this->exists($class)) {
				throw new \coreException("Output buffer of class \"{$class}\" not loaded");
			}

			return $this->bufferList[$class];
		}

		/**
		 * Определяет были создан буфер
		 * @param string $class класс буфера
		 * @return bool
		 */
		public function exists($class) {
			if (!is_string($class) || $class === '') {
				return false;
			}

			return isset($this->bufferList[$class]);
		}

		/**
		 * Устанавливает созданный буфер
		 * @param \iOutputBuffer $buffer
		 * @return $this
		 */
		public function set(\iOutputBuffer $buffer) {
			$this->bufferList[get_class($buffer)] = $buffer;
			return $this;
		}
	}
