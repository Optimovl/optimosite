<?php

	namespace UmiCms\System\Response;

	use UmiCms\System\Response\Buffer\iCollection;
	use UmiCms\System\Response\Buffer\iDetector;
	use UmiCms\System\Response\Buffer\iFactory;
	use UmiCms\System\Response\UpdateTime\iCalculator;

	/**
	 * Класс фасада для работы с буферами вывода
	 * @package UmiCms\System\Response\Buffer
	 */
	class Facade implements iFacade {

		/** @var iFactory $factory фабрика буферов */
		private $factory;

		/** @var iDetector $detector определитель текущего буфера */
		private $detector;

		/** @var iCollection $collection коллекция буферов */
		private $collection;

		/** @var iCalculator $calculator вычислитель времени последнего обновления данных ответа */
		private $calculator;

		/** @inheritdoc */
		public function __construct(
			iFactory $factory,
			iDetector $detector,
			iCollection $collection,
			iCalculator $calculator
		) {
			$this->factory = $factory;
			$this->detector = $detector;
			$this->collection = $collection;
			$this->calculator = $calculator;
		}

		/** @inheritdoc */
		public function getCurrentBuffer() {
			$class = $this->getDetector()
				->detect();

			return $this->getBufferByClass($class);
		}

		/** @inheritdoc */
		public function getBuffer($name) {
			if (!is_string($name) || $name === '') {
				throw new \coreException('Incorrect buffer name given');
			}

			$class = $this->getClass($name);
			return $this->getBufferByClass($class);
		}

		/** @inheritdoc */
		public function getBufferByClass($class) {
			$collection = $this->getCollection();

			if (!$collection->exists($class)) {
				$buffer = $this->getFactory()
					->create($class);
				$collection->set($buffer);
			}

			return $collection->get($class);
		}

		/** @inheritdoc */
		public function getHttpBuffer() {
			return $this->getBuffer(self::HTTP);
		}

		/** @inheritdoc */
		public function getCliBuffer() {
			return $this->getBuffer(self::CLI);
		}

		/** @inheritdoc */
		public function getHttpDocBuffer() {
			return $this->getBuffer(self::HTTP_DOC);
		}

		/** @inheritdoc */
		public function printJson($data, $status = '200 OK') {
			$buffer = $this->getCurrentBuffer();
			$buffer->calltime();
			$buffer->status($status);
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$buffer->option('generation-time', false);
			$buffer->push(json_encode($data));
			$buffer->end();
		}

		/** @inheritdoc */
		public function printXml(\DOMDocument $document, $status = '200 OK') {
			$buffer = $this->getCurrentBuffer();
			$buffer->calltime();
			$buffer->status($status);
			$buffer->contentType('text/xml');
			$buffer->charset('utf-8');
			$buffer->option('generation-time', false);
			$buffer->push($document->saveXML());
			$buffer->end();
		}

		/** @inheritdoc */
		public function download(\iUmiFile $file) {
			$this->validateFile($file);
			$file->download();
		}

		/** @inheritdoc */
		public function downloadAndDelete(\iUmiFile $file) {
			$this->validateFile($file);
			$file->download(true);
		}

		/** @inheritdoc */
		public function getUpdateTime() {
			return $this->getCalculator()
				->calculate();
		}

		/** @inheritdoc */
		public function isCorrect() {
			return $this->getCurrentBuffer()->getStatusCode() == 200;
		}

		/**
		 * Валидирует скачиваемый файл
		 * @param \iUmiFile $file файл
		 * @throws \InvalidArgumentException
		 */
		private function validateFile(\iUmiFile $file) {
			if ($file->getIsBroken()) {
				throw new \InvalidArgumentException(sprintf('Broken file given: "%s"', $file->getFilePath(true)));
			}
		}

		/**
		 * Возвращает имя класса по имени буфера
		 * @param string $name имя буфера
		 * @return string
		 */
		private function getClass($name) {
			return sprintf('%sOutputBuffer', $name);
		}

		/**
		 * Возвращает фабрику буферов
		 * @return iFactory
		 */
		private function getFactory() {
			return $this->factory;
		}

		/**
		 * Возвращает определитель текущего буфера
		 * @return iDetector
		 */
		private function getDetector() {
			return $this->detector;
		}

		/**
		 * Возвращает коллекцию буферов
		 * @return iCollection
		 */
		private function getCollection() {
			return $this->collection;
		}

		/**
		 * Возвращает вычислителя времени обновления данных ответа
		 * @return iCalculator
		 */
		private function getCalculator() {
			return $this->calculator;
		}
	}
