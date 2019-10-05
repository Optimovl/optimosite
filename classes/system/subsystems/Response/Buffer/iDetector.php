<?php

	namespace UmiCms\System\Response\Buffer;

	use UmiCms\System\Request\Mode\iDetector as ModeDetector;

	/**
	 * Интерфейс определителя текущего буфера
	 * @package UmiCms\System\Response\Buffer
	 */
	interface iDetector {

		/** @const string DEFAULT_CLI_BUFFER класс реализации буфера вывода для командной строки по умолчанию */
		const DEFAULT_CLI_BUFFER = 'CLIOutputBuffer';

		/** @const string DEFAULT_HTTP_BUFFER класс реализации буфера вывода для http запроса по умолчанию */
		const DEFAULT_HTTP_BUFFER = 'HTTPOutputBuffer';

		/**
		 * Конструктор
		 * @param ModeDetector $modeDetector определитель режима работы системы
		 */
		public function __construct(ModeDetector $modeDetector);

		/**
		 * Определяет класс реализации текущего буфера вывода
		 * @return string
		 */
		public function detect();
	}
