<?php
	namespace UmiCms\System\Import\UmiDump\Helper\File\Path;

	use UmiCms\System\Import\UmiDump\Helper\Solution\Postfix\iFilter as SolutionPostfixFilter;

	/**
	 * Класс конвертера файловых путей
	 * @package UmiCms\System\Import\UmiDump\Helper\File\Path
	 */
	class Converter implements iConverter {

		/** @var \iConfiguration $configuration экземпляр класса конфигурации */
		private $configuration;

		/** @var SolutionPostfixFilter $solutionPostfixFilter фильтр постфикса решения */
		private $solutionPostfixFilter;

		/** @var null|string $suffix суффикс файлового пути */
		private $suffix;

		/** @inheritdoc */
		public function __construct(\iConfiguration $configuration, SolutionPostfixFilter $solutionPostfixFilter) {
			$this->configuration = $configuration;
			$this->solutionPostfixFilter = $solutionPostfixFilter;
		}

		/** @inheritdoc */
		public function setSuffix($suffix) {
			$this->suffix = (string) $suffix;
			return $this;
		}

		/** @inheritdoc */
		public function convert($path) {
			switch (true) {
				case contains($path, $this->getTemplatesPathPart()) : {
					return $this->replaceTemplateName($path);
				}
				case contains($path, $this->getImagesPathPart()) : {
					return $this->replacePathPart($path, $this->getImagesPathPart());
				}
				case contains($path, $this->getFilesPathPart()) : {
					return $this->replacePathPart($path, $this->getFilesPathPart());
				}
				default : {
					return $path;
				}
			}
		}

		/**
		 * Заменяет путь
		 * @param string $path путь до файла
		 * @param string $pathToReplace часть пути, которую требуется поменять
		 * @return string
		 */
		private function replacePathPart($path, $pathToReplace) {
			$pattern = sprintf('|%s|', $pathToReplace);
			$replacement = sprintf('%s/%s', $pathToReplace, $this->getSuffix());
			return preg_replace($pattern, $replacement, $path);
		}

		/**
		 * Заменяет путь до файла шаблона
		 * @param string $path путь до файла
		 * @return string
		 */
		private function replaceTemplateName($path) {
			$pattern = sprintf('|%s|', $this->getTemplatesPathPart());
			$replacement = $this->getTemplatesPathPart($this->getSuffix());
			return preg_replace($pattern, $replacement, $path);
		}

		/**
		 * Возвращает часть пути до файлов шаблона
		 * @param string|null $solution имя решения (шаблона)
		 * @return string
		 */
		private function getTemplatesPathPart($solution = null) {
			$solution = $solution ?: $this->getSolutionName();
			return sprintf('%s/templates/%s', CURRENT_WORKING_DIR, $solution);
		}

		/**
		 * Возвращает часть пути до пользовательских изображений
		 * @return string
		 */
		private function getImagesPathPart() {
			return $this->getConfiguration()
				->includeParam('user-images-path');
		}

		/**
		 * Возвращает часть пути до пользовательских файлов
		 * @return string
		 */
		private function getFilesPathPart() {
			return $this->getConfiguration()
				->includeParam('user-files-path');
		}

		/**
		 * Возвращает конфигурацию
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает имя решения
		 * @return string
		 */
		private function getSolutionName() {
			return $this->getSolutionPostfixFilter()
				->run($this->getSuffix());
		}

		/**
		 * Возвращает суффикс файлового пути
		 * @return null|string
		 */
		private function getSuffix() {
			return $this->suffix;
		}

		/**
		 * Возвращает фильтр постфикса решения
		 * @return SolutionPostfixFilter
		 */
		private function getSolutionPostfixFilter() {
			return $this->solutionPostfixFilter;
		}
	}