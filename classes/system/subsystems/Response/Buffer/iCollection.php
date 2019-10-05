<?php

	namespace UmiCms\System\Response\Buffer;

	/**
	 * Интерфейс коллекции буферов
	 * @package UmiCms\System\Response\Buffer
	 */
	interface iCollection {

		/**
		 * Возвращает буфер
		 * @param string $class имя класса
		 * @return \iOutputBuffer
		 * @throws \coreException
		 */
		public function get($class);

		/**
		 * Определяет были создан буфер
		 * @param string $class класс буфера
		 * @return bool
		 */
		public function exists($class);

		/**
		 * Устанавливает созданный буфер
		 * @param \iOutputBuffer $buffer
		 * @return $this
		 */
		public function set(\iOutputBuffer $buffer);
	}
