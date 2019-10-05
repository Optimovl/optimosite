<?php

	/** Класс xml транслятора (сериализатора) публичных исключений */
	class publicExceptionWrapper extends translatorWrapper {

		/**
		 * @inheritdoc
		 * @param publicException $object
		 */
		public function translate($object) {
			return $this->translateData($object);
		}

		/**
		 * Преобразует публичной исключение в массив с разметкой для последующей сериализации в xml
		 * @param publicException $exception публичное исключение
		 * @return array
		 */
		protected function translateData(publicException $exception) {
			return [
				'error' => [
					'node:msg' => def_module::parseTPLMacroses($exception->getMessage())
				]
			];
		}
	}
