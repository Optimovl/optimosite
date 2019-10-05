<?php

	namespace UmiCms\System\Request\Path;

	use UmiCms\System\Request\Http\iGet;

	/**
	 * Класс распознавателя обрабатываемого пути
	 * @package UmiCms\System\Request\Path
	 */
	class Resolver implements iResolver {

		/** @var iGet $getContainer контейнер GET параметров */
		private $getContainer;

		/** @var \iConfiguration $configuration конфигурация */
		private $configuration;

		/** @inheritdoc */
		public function __construct(iGet $getContainer, \iConfiguration $configuration) {
			$this->getContainer = $getContainer;
			$this->configuration = $configuration;
		}

		/** @inheritdoc */
		public function get() {
			$path = (string) $this->getRaw();
			$path = trim($path, '/');
			return $this->filterUrlPostfix($path);
		}

		/** @inheritdoc */
		public function getParts() {
			return explode('/', $this->get());
		}

		/**
		 * Возвращает необработанный путь
		 * @return mixed
		 */
		private function getRaw() {
			return $this->getGetContainer()
				->get('path');
		}

		/**
		 * Удаляет из пути постфикс запроса
		 * @param string $path путь
		 * @return string
		 */
		private function filterUrlPostfix($path) {
			$postfix = $this->getConfiguration()
				->get('seo', 'url-suffix');

			if (endsWith($path, $postfix)) {
				$postfixPosition = mb_strrpos($path, $postfix);
				$path = mb_substr($path, 0, $postfixPosition);
			}

			return $path;
		}

		/**
		 * Возвращает контейнер GET параметров
		 * @return iGet
		 */
		private function getGetContainer() {
			return $this->getContainer;
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}
	}
