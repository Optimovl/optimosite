<?php

	namespace UmiCms\System\Response\Buffer;

	/**
	 * Интерфейс фабрики буферов
	 * @package UmiCms\System\Response\Buffer
	 */
	interface iFactory {

		/**
		 * Создает буфер
		 * @param string $class имя класса реализации буфера
		 * @return \iOutputBuffer
		 * @throws \coreException
		 */
		public function create($class);
	}
