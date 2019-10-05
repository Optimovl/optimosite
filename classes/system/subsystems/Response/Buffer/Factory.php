<?php

	namespace UmiCms\System\Response\Buffer;

	/**
	 * Класс фабрики буферов
	 * @package UmiCms\System\Response\Buffer
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create($class) {
			if (!class_exists($class)) {
				throw new \coreException("Output buffer of class \"{$class}\" not found");
			}

			$instance = new $class;

			if (!$instance instanceof \iOutputBuffer) {
				throw new \coreException("Output buffer class \"{$class}\" must implement iOutputBuffer");
			}

			return $instance;
		}
	}
