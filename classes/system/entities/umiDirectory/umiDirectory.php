<?php

	/**
	 * Предоставляет интерфейс для работы с директориями
	 * Использует итератор umiDirectoryIterator
	 */
	class umiDirectory implements iUmiDirectory, IteratorAggregate {

		/** @var string путь до директории */
		protected $path = '';

		protected $is_broken = false;

		protected $arr_files = [];

		protected $arr_dirs = [];

		protected $arr_objs = [];

		protected $is_readed = false;

		/** @var bool доступна ли директория для чтения */
		protected $isReadable = false;

		/** @var bool доступна ли директория для записи */
		protected $isWritable = false;

		/** @var array $cache Кеш */
		private static $cache = [];

		/** @var string $parentPath путь до родительской директории */
		private $parentPath;

		/** @inheritdoc */
		public function __construct($path) {
			try {
				$this->setPath($path);
			} catch (Exception $exception) {
				umiExceptionHandler::report($exception);
			}

			$this->refresh();
		}

		/** @inheritdoc */
		public function refresh() {
			$path = $this->getPath();

			if (is_dir($path)) {
				$this->setReadable(
					is_readable($path)
				);
				$this->setWritable(
					is_writable($path)
				);
				$this->is_broken = false;
				return $this;
			}

			$this->setReadable(false);
			$this->setWritable(false);
			$this->is_broken = true;
			return $this;
		}

		/** @inheritdoc */
		public function getPath() {
			return $this->path;
		}

		/**
		 * Возвращает имя директории
		 * @return string имя директории
		 */
		public function getName() {
			$arrDirPath = explode('/', $this->path);
			return array_pop($arrDirPath);
		}

		public function getIterator() {
			$this->read();
			return new umiDirectoryIterator($this->arr_objs);
		}

		private function read() {
			if ($this->is_readed) {
				return false;
			}

			$this->is_readed = true;
			$this->arr_files = [];
			$this->arr_dirs = [];

			$cache = self::cache($this->path);
			if ($cache) {
				list($this->arr_files, $this->arr_dirs, $this->arr_objs) = $cache;
				return;
			}

			if (is_dir($this->path) && is_readable($this->path)) {
				$rs_dir = opendir($this->path);

				if ($rs_dir) {
					while (($s_next_obj = readdir($rs_dir)) !== false) {
						if (isDemoMode()) {
							if ($s_next_obj == 'demo') {
								continue;
							}
						}

						$s_obj_path = $this->path . '/' . $s_next_obj;

						if (is_file($s_obj_path)) {
							$this->arr_files[$s_next_obj] = $s_obj_path;
							$this->arr_objs[] = $s_obj_path;
						} elseif (is_dir($s_obj_path) && $s_next_obj != '..' && $s_next_obj != '.') {
							$this->arr_dirs[$s_next_obj] = $s_obj_path;
							$this->arr_objs[] = $s_obj_path;
						}
					}

					closedir($rs_dir);
				}
			}

			self::cache($this->path, [$this->arr_files, $this->arr_dirs, $this->arr_objs]);
		}

		/**
		 * Проверяет существует ли директория
		 * @return bool true, если директория не существует
		 */
		public function getIsBroken() {
			return (bool) $this->is_broken;
		}

		/** @inheritdoc */
		public function isExists() {
			return !$this->getIsBroken();
		}

		/** @inheritdoc */
		public function isReadable() {
			return (bool) $this->isReadable;
		}

		/** @inheritdoc */
		public function isWritable() {
			return (bool) $this->isWritable;
		}

		/** @inheritdoc */
		public function getList($pattern) {
			$pattern = sprintf('%s/%s', $this->getPath(), ltrim($pattern, '/'));
			$list = glob($pattern);
			return is_array($list) ? $list : [];
		}

		/**
		 * Читает директорию и возвращает массив объектов файловой системы
		 * @param int $i_obj_type тип, который хотим получить: 1 - real files, 2 - directories, 0 - files & directories
		 * @param string $s_mask ="" маска по которой выбирать объекты
		 * @param bool $b_only_readable =false сделать проверку на чтение и вернуть только объекты, доступные на чтение
		 * @return array массив объектов файловой системы. Ключ массива - имя объекта, значение - полный путь к нему
		 */
		public function getFSObjects($i_obj_type = 0, $s_mask = '', $b_only_readable = false) {
			$this->read();
			$arr_result = [];

			switch ($i_obj_type) {
				case 1:                                    //1: real files
					$arr_objs = $this->arr_files;
					break;
				case 2:                                    //2: directories
					$arr_objs = $this->arr_dirs;
					break;
				default:
					$arr_objs = array_merge($this->arr_dirs, $this->arr_files);
			}

			$s_mask = (string) $s_mask;
			foreach ($arr_objs as $s_obj_name => $s_obj_path) {
				if ((!$b_only_readable || is_readable($s_obj_path)) && ($s_mask === '') ||
					preg_match('/' . $s_mask . '/i', $s_obj_name)) {
					$arr_result[$s_obj_name] = $s_obj_path;
				}
			}

			ksort($arr_result);
			return $arr_result;
		}

		/**
		 * Читает директорию и возвращает массив файлов в директории
		 * @param string $s_mask ="" маска по которой выбирать файлы
		 * @param bool $b_only_readable =false сделать проверку на чтение и вернуть только файлы, доступные на чтение
		 * @return array массив файлов. Ключ массива - имя файла, значение - полный путь к нему
		 */
		public function getFiles($s_mask = '', $b_only_readable = false) {
			return $this->getFSObjects(1, $s_mask, $b_only_readable);
		}

		/**
		 * Читает директорию и возвращает массив поддиректорий
		 * @param string $s_mask ="" маска по которой выбирать директории
		 * @param bool $b_only_readable =false сделать проверку на чтение и вернуть только файлы, доступные на чтение
		 * @return array массив директорий. Ключ массива - имя директории, значение - полный путь к ней
		 */
		public function getDirectories($s_mask = '', $b_only_readable = false) {
			return $this->getFSObjects(2, $s_mask, $b_only_readable);
		}

		/**
		 * Читает директорию и возвращает массив всех вложенных файлов и директорий на всю глубину
		 * @param int $i_obj_type тип, который хотим получить: 1 - real files, 2 - directories, 0 - files & directories
		 * @param string $s_mask ="" маска по которой выбирать объекты
		 * @param bool $b_only_readable =false сделать проверку на чтение и вернуть только объекты, доступные на чтение
		 * @return array массив объектов файловой системы. Ключ массива - полный путь к нему объекта, значение - имя
		 */
		public function getAllFiles($i_obj_type = 0, $s_mask = '', $b_only_readable = false) {
			$files = $this->getFSObjects($i_obj_type, $s_mask, $b_only_readable);
			$result = array_flip($files);
			$dirs = $this->getFSObjects(2, $s_mask, $b_only_readable);

			foreach ($dirs as $dir) {
				$dir = new umiDirectory($dir);
				$dirFiles = $dir->getAllFiles($i_obj_type, $s_mask = '', $b_only_readable);

				foreach ($dirFiles as $path => $name) {
					$result[$path] = $name;
				}
			}

			return $result;
		}

		/** @inheritdoc */
		public function deleteRecursively() {
			return $this->delete(true);
		}

		/** @inheritdoc */
		public function delete($recursively = false) {
			if (!is_writable($this->path)) {
				return false;
			}

			$contentDeleted = true;

			if ($recursively) {
				$contentDeleted = $this->deleteContent();
			}

			return $contentDeleted && @rmdir($this->path);
		}

		/** @inheritdoc */
		public function deleteContent() {
			self::deleteCache();

			foreach ($this as $item) {

				$isDeleted = false;

				if ($item instanceof iUmiDirectory) {
					$isDeleted = $item->deleteRecursively();
				} elseif ($item instanceof iUmiFile) {
					$isDeleted = $item->delete();
				}

				if (!$isDeleted) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Рекурсивно удаляет файлы в директории по маске
		 * @param string $pattern маска
		 */
		public function deleteFilesByPattern($pattern = '/(.*temp$)/') {
			if ($this->getIsBroken()) {
				return;
			}

			foreach ($this as $item) {
				if ($item instanceof self) {
					$item->deleteFilesByPattern($pattern);
				}

				if ($item instanceof umiFile && preg_match($pattern, $item->getFilePath())) {
					$item->delete();
				}
			}
		}

		/**
		 * Удаляет пустую директорию и возвращает результат операции
		 * @return bool
		 */
		public function deleteEmptyDirectory() {
			$recursiveDeletion = false;
			return $this->delete($recursiveDeletion);
		}

		/** @inheritdoc */
		public function getParentPath() {
			return $this->parentPath;
		}

		/**
		 * Убедиться, что директория $folder существует, если нет, то создать ее
		 * @param string $folder проверяемая директория
		 * @param string $basedir = "" родительский каталог, который должен содержать проверяемую директорию
		 * @return bool true, если директория существует, либо успешно создана
		 */
		public static function requireFolder($folder, $basedir = '') {
			if (!$folder) {
				return false;
			}

			if (!is_dir($folder)) {
				mkdir($folder, 0777, true);
			}

			$realpath = realpath($folder);
			$basedir = realpath($basedir);
			return (mb_substr($realpath, 0, mb_strlen($basedir)) == $basedir);
		}

		/** @inheritdoc */
		public static function getDirectorySize($directoryPath) {
			return getDirSize($directoryPath);
		}

		public function __toString() {
			return "umiDirectory::{$this->path}";
		}

		/**
		 * Устанавливает путь до директории
		 * @param string $path путь до директории
		 * @return $this
		 * @throws Exception
		 */
		protected function setPath($path) {
			if (!is_string($path) || empty($path)) {
				throw new Exception('Wrong path given');
			}

			$path = rtrim($path, '/');

			if (empty($path)) {
				throw new Exception('Wrong path given');
			}

			$this->path = $path;
			return $this->loadParentPath();
		}

		/**
		 * Устанавливает данные в кеш или возвращает их
		 * @param string $key ключ
		 * @param mixed|null $value данные (если null - данные возвращаются, иначе устанавливаются)
		 * @return mixed|null
		 */
		protected static function cache($key, $value = null) {
			if ($value) {
				return self::$cache[$key] = $value;
			}

			if (isset(self::$cache[$key])) {
				return self::$cache[$key];
			}

			return null;
		}

		/** Удаляет кеш */
		protected static function deleteCache() {
			self::$cache = [];
		}

		/**
		 * Устанавливает флаг доступности директории на чтение
		 * @param bool $flag флаг доступности чтения
		 * @return $this
		 */
		protected function setReadable($flag = true) {
			$this->isReadable = (bool) $flag;
			return $this;
		}

		/**
		 * Устанавливает флаг доступности директории на запись
		 * @param bool $flag флаг доступности записи
		 * @return $this
		 */
		protected function setWritable($flag = true) {
			$this->isWritable = (bool) $flag;
			return $this;
		}

		/**
		 * Загружает путь до родительской директории
		 * @return $this
		 */
		private function loadParentPath() {
			$pathInfo = pathinfo($this->getPath());
			$parentPath = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
			return $this->setParentPath($parentPath);
		}

		/**
		 * Устанавливает путь до родительской директории
		 * @param string $path путь до родительской директории
		 * @return $this
		 */
		private function setParentPath($path) {
			$this->parentPath = $path;
			return $this;
		}
	}
