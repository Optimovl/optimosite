<?php

	namespace UmiCms\System\Response;

	use UmiCms\System\Response\Buffer\iCollection;
	use UmiCms\System\Response\Buffer\iDetector;
	use UmiCms\System\Response\Buffer\iFactory;
	use UmiCms\System\Response\UpdateTime\iCalculator;

	/**
	 * Интерфейс фасада для работы с буферами вывода
	 * @package UmiCms\System\Response\Buffer
	 */
	interface iFacade {

		/** @const string HTTP имя http буфера */
		const HTTP = 'HTTP';

		/** @const string CLI имя cli буфера */
		const CLI = 'CLI';

		/** @const string HTTP_DOC имя буфера для документов */
		const HTTP_DOC = 'HTTPDoc';

		/**
		 * Конструктор
		 * @param iFactory $factory фабрика буферов
		 * @param iDetector $detector определитель текущего буфера
		 * @param iCollection $collection коллекция буферов
		 * @param iCalculator $calculator вычислитель времени последнего обновления данных ответа
		 */
		public function __construct(
			iFactory $factory,
			iDetector $detector,
			iCollection $collection,
			iCalculator $calculator
		);

		/**
		 * Возвращает текущий буфер
		 * @return \iOutputBuffer
		 */
		public function getCurrentBuffer();

		/**
		 * Возвращает буфер по имени
		 * @param string $name имя буфера
		 * @return \iOutputBuffer
		 * @throws \coreException
		 */
		public function getBuffer($name);

		/**
		 * Возвращает буфер по имени его класса
		 * @param string $class класс буфера
		 * @return \iOutputBuffer
		 */
		public function getBufferByClass($class);

		/**
		 * Возвращает буфер вывода для ответа на http запрос
		 * @return \HTTPOutputBuffer
		 */
		public function getHttpBuffer();

		/**
		 * Возвращает буфер вывода для командной строки
		 * @return \CLIOutputBuffer
		 */
		public function getCliBuffer();

		/**
		 * Возвращает буфер вывода документов, в случае, когда не требуется наложение layout
		 * @return \HTTPDocOutputBuffer
		 */
		public function getHttpDocBuffer();

		/**
		 * Выводит в буффер данные в json формате
		 * @param mixed $data данные
		 * @param string $status статус ответа
		 */
		public function printJson($data, $status = '200 OK');

		/**
		 * Выводит в буффер данные в xml формате
		 * @param \DOMDocument $document xml документ
		 * @param string $status статус ответа
		 */
		public function printXml(\DOMDocument $document, $status = '200 OK');

		/**
		 * Инициирует скачивание файла
		 * @param \iUmiFile $file файл
		 */
		public function download(\iUmiFile $file);

		/**
		 * Инициирует скачивание файла и удаляет его
		 * @param \iUmiFile $file файл
		 */
		public function downloadAndDelete(\iUmiFile $file);

		/**
		 * Возвращает время последнего обновления данных ответа
		 * @return int
		 */
		public function getUpdateTime();

		/**
		 * Определяет корректен ли ответ
		 * @return bool
		 */
		public function isCorrect();
	}
