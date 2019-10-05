<?php

	/** Класс буфера вывода документов, в случае, когда не требуется наложение layout */
	class HTTPDocOutputBuffer extends HTTPOutputBuffer {

		/**
		 * @inheritdoc
		 * Очищает и удаляет все уровни буффера вывода и оставляет только один пустой уровень.
		 */
		public function clear() {
			parent::clear();
			$level = ob_get_level();

			for ($i = 0; $i < $level; $i += 1) {
				ob_end_clean();
			}

			ob_start();
			return $this;
		}

		/** @inheritdoc */
		public function send() {
			$this->sendHeaders();
			echo $this->buffer;
			parent::clear();
		}
	}
