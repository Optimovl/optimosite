<?php
	namespace UmiCms\System\Import\UmiDump\Helper\File\Path;

	use UmiCms\System\Import\UmiDump\Helper\Solution\Postfix\iFilter as SolutionPostfixFilter;

	/**
	 * Интерфейс конвертера файловых путей
	 * @package UmiCms\System\Import\UmiDump\Helper\File\Path
	 */
	interface iConverter {

		/**
		 * Конструктор
		 * @param \iConfiguration $configuration конфигурация
		 * @param SolutionPostfixFilter $solutionPostfixFilter фильтр постфикса решения
		 */
		public function __construct(\iConfiguration $configuration, SolutionPostfixFilter $solutionPostfixFilter);

		/**
		 * Устанавливает суффикс файлового пути
		 * @param string $suffix суффикс файлового пути
		 * @return $this
		 */
		public function setSuffix($suffix);

		/**
		 * Конвертирует путь до файла
		 * @param string $path путь до файла
		 * @return string
		 */
		public function convert($path);
	}