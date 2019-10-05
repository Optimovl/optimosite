<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\Csv;

	use UmiCms\Classes\System\Utils\QuickExchange\Source\iDetector as SourceDetector;
	use UmiCms\System\Request\iFacade as iRequest;

	/**
	 * Интерфейс экспортера
	 * @package UmiCms\Classes\System\Utils\QuickExchange\Csv
	 */
	interface iExporter {

		/**
		 * Конструктор
		 * @param SourceDetector $sourceDetector определитель источника
		 * @param iRequest $request фасад запроса
		 */
		public function __construct(SourceDetector $sourceDetector, iRequest $request);

		/**
		 * Запускает экспорт в csv.
		 * Экспорт производится в несколько итераций.
		 * @param \selector $query выборка сущностей, которые будут экспортированы
		 * @param string $encoding кодировка csv файла (windows-1251 / utf-8)
		 * @return bool завершен ли экспорт
		 */
		public function export(\selector $query, $encoding);
	}
