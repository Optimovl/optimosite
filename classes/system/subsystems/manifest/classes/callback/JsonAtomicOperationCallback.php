<?php

	use UmiCms\Service;

	/**
	 * Обработчика хода выполнения атомарного действия,
	 * логгирует процесс буфер в виде списка js функций, которые нужно вызвать
	 */
	class JsonAtomicOperationCallback extends CommonAtomicOperationCallback {

		/** @var iOutputBuffer|null $buffer буффер вывода */
		protected $buffer;

		/** Конструктор */
		public function __construct() {
			$this->buffer = Service::Response()
				->getCurrentBuffer();
		}

		/** @inheritdoc */
		public function getLog() {
			return explode(PHP_EOL, $this->buffer->content());
		}

		/**
		 * Выводит сообщение лога в буффер в виде списка js функций, которые нужно вызвать,
		 * чтобы отобразить результат
		 * @param string $message сообщение лога
		 * @param bool $isError сообщение повествует об ошибке
		 */
		protected function log($message, $isError = false) {
			$from = ["'", "\n"];
			$to = ["\\'", "\\n"];
			$message = str_replace($from, $to, $message);

			$jsFunctionName = $isError ? 'reportJsonError' : 'reportJsonStatus';

			$log = <<<JS
{$jsFunctionName}('{$message}');

JS;
			$this->buffer->push($log);
		}
	}
