<?php

	/** @deprecated */
	interface iUmiBufferInjector {

		/**
		 * Устанавливает буфер
		 * @param iOutputBuffer $buffer буфер
		 */
		public function setBuffer(iOutputBuffer $buffer);

		/**
		 * Возвращает буфер
		 * @return iOutputBuffer
		 */
		public function getBuffer();
	}

?>
