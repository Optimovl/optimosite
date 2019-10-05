<?php

	/** @deprecated */
	trait tUmiBufferInjector {

		/** @var iOutputBuffer $buffer буфер */
		private $buffer;

		/**
		 * Устанавливает буфер
		 * @param iOutputBuffer $buffer буфер
		 */
		public function setBuffer(iOutputBuffer $buffer) {
			$this->buffer = $buffer;
		}

		/**
		 * Возвращает буфер
		 * @return iOutputBuffer
		 * @throws Exception
		 */
		public function getBuffer() {
			if (!$this->buffer instanceof iOutputBuffer) {
				throw new RequiredPropertyHasNoValueException('You should set iOutputBuffer first');
			}

			return $this->buffer;
		}
	}

?>
