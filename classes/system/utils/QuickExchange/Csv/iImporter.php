<?php

	namespace UmiCms\Classes\System\Utils\QuickExchange\Csv;

	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;
	use UmiCms\Classes\System\Utils\QuickExchange\Source\iDetector as SourceDetector;
	use UmiCms\System\Request\iFacade as iRequest;
	use UmiCms\System\Session\iSession;

	/**
	 * Интерфейс импортера
	 * @package UmiCms\Classes\System\Utils\QuickExchange\Csv
	 */
	interface iImporter {

		/**
		 * Конструктор
		 * @param SourceDetector $sourceDetector определитель источника
		 * @param iRequest $request фасад запроса
		 * @param FileFactory $fileFactory фабрика файлов
		 * @param \iConfiguration $configuration конфигурация
		 * @param iSession $session фасад сессии
		 */
		public function __construct(
			SourceDetector $sourceDetector,
			iRequest $request,
			FileFactory $fileFactory,
			\iConfiguration $configuration,
			iSession $session
		);

		/**
		 * Запускает импорт из csv.
		 * Импорт производится в несколько итераций.
		 * @param \selector $query выборка сущностей, с помощью которой определяется тип импортируемых
		 * сущностей (объекты/страницы) и идентификатор родителя (для страниц).
		 * @param string $encoding кодировка csv файла (windows-1251 / utf-8)
		 * @return bool завершен ли импорт
		 */
		public function import(\selector $query, $encoding);
	}
