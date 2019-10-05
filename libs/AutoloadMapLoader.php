<?php

	namespace UmiCms\Libs;

	/**
	 * Загрузчик соответствий классов к их файлам
	 * Для подключения класса может потребоваться подключение одного или нескольких файлов
	 * Все вызовы методов загрузки добавляют соответствия к списку уже существующих
	 * При совпадении имен классов новое соответствие перезаписывает существующее
	 *
	 * Class AutoloadMapLoader
	 * @package UmiCms\Libs
	 */
	class AutoloadMapLoader {

		/** @var array соответствия классов к файлам */
		private $map = [];

		/**
		 * Возвращает соответствия классов к их файлам
		 * @return array
		 */
		public function getMap() {
			return $this->map;
		}

		/**
		 * Загружает соответствия из файла
		 * Для успешной загрузки соответствий в файле должна быть определена переменная $classes со значением вида:
		 * [
		 *    'class1' => [path1, path2...]
		 *    'class2' => [path1, path2...]
		 *    ...
		 * ]
		 * @param string $filePath путь до файла
		 * @return $this
		 */
		public function fromFile($filePath) {
			if (!file_exists($filePath)) {
				return $this;
			}

			$classes = [];
			require $filePath;

			$this->map = array_merge($this->map, $classes);
			return $this;
		}

		/**
		 * Загружает соответствия из конфигурации
		 * @example
		 * Конфигурация:
		 * [autoload]
		 * class1[] = "path/to/class1"
		 * class2[] = "path/to/class2"
		 *
		 * Результат fromConfig($config, 'autoload')
		 * [
		 *    'class1' => ['path/to/class1'],
		 *    'class2' => ['path/to/class2'],
		 * ]
		 *
		 * @param \iConfiguration $config конфигурация
		 * @param string $section секция конфигурации, в которой описаны соответствия
		 * @return $this
		 */
		public function fromConfig(\iConfiguration $config, $section = 'autoload') {
			$classesNames = $config->getList($section);

			if (!is_array($classesNames) || count($classesNames) === 0) {
				return $this;
			}

			$result = [];
			foreach ($classesNames as $class) {
				$pathList = $config->get($section, $class);

				if (is_array($pathList) && count($pathList) > 0) {
					$pathList = array_map([$this, 'getAbsolutePath'], $pathList);
					$result[$class] = $pathList;
				}
			}

			$this->map = array_merge($this->map, $result);
			return $this;
		}

		/**
		 * Возвращает абсолютный путь, по относительному пути, в котором символ ~ обозначает корневую директорию системы
		 * @param string $filePath относительный путь
		 * @return string
		 */
		private function getAbsolutePath($filePath) {
			return preg_replace('/^~/', CURRENT_WORKING_DIR, $filePath);
		}

	}
