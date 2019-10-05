<?php

use UmiCms\Service;

/**
	 * Класс для работы с файлами в системе.
	 *
	 * TODO обобщить методы manualUpload, uploadByRawPostBody, uploadByHtmlForm
	 * TODO вынести логику загрузки файлов из класса
	 */
	class umiFile implements iUmiFile {

		protected $filepath,
			$size, $ext, $name, $dirname, $modify_time,
			$is_broken = false;

		public static $mask = 0777;

		protected static $class_name = 'umiFile';

		protected static $forbiddenFileTypes = [
			'php',
			'php3',
			'php4',
			'php5',
			'phtml'
		];

		protected static $allowedFileTypes = [
			'txt',
			'doc',
			'docx',
			'tif',
			'tiff',
			'xls',
			'xlsx',
			'ppt',
			'pptx',
			'pps',
			'ppsx',
			'pcx',
			'odt',
			'odf',
			'vsd',
			'vsdx',
			'sxw',
			'ods',
			'odg',
			'pdf',
			'csv',
			'html',
			'js',
			'tpl',
			'xsl',
			'xml',
			'css',
			'zip',
			'rar',
			'7z',
			'tar',
			'gz',
			'tar.gz',
			'exe',
			'msi',
			'rtf',
			'chm',
			'ico',
			'file',
			'psd',
			'flv',
			'mp4',
			'swf',
			'mp3',
			'wav',
			'wma',
			'ogg',
			'aac',
			'gpx',
			'avi',
			'mkv',
			'wmv',
			'mov'
		];

		protected static $allowedImageTypes = ['jpg', 'jpeg', 'gif', 'bmp', 'png', 'svg', 'ico'];

		protected static $allowedUserFileTypes;

		protected static $addWaterMark = false;

		/* @var int|null $order порядковый номер */
		protected $order;

		/* @var int|null $id идентификатор */
		protected $id;

		/** @var bool $ignoreSecurity игнорировать безопасность файла */
		private $ignoreSecurity = false;

		/** @var bool $isReadable доступен ли файл для чтения */
		private $isReadable = false;

		/** @var bool $isWritable доступен ли файл для записи */
		private $isWritable = false;

		/**
		 * Конструктор, выполняющий нормализацию файлового пути и проверку его корректности.
		 *
		 * Каноничным является указание пути через ./ относительно CURRENT_WORKING_DIR, где
		 * CURRENT_WORKING_DIR в общем случае - корневая папка проекта. DOCUMENT_ROOT.
		 * Исторически была неявная константа-модификатор, которая давала возможность указывать
		 * для скриптов необхомость преобразовывать такие пути относительно CURRENT_WORKING_DIR
		 * или же использовать их относительно текущей директории, что добавило неоднозначности.
		 * Поэтому мы проверяем наличие двух файлов по двум разным путям.
		 *
		 * В случае если CURRENT_WORKING_DIR не совпадает с текущей директорией,
		 * а это почти все консольные вызовы, которые не(верно) устанавлиявают директорию запуска,
		 * Сначала проверяем наличие файла по относительному пути
		 * и если файл найден, то генерируем предупреждение и используем этот файл
		 * для дальнейшей работы.
		 * И только если файла по относительному пути не обнаружено, пытаемся использовать
		 * абсолютный путь к файлу, построенный с участием CURRENT_WORKING_DIR.
		 * Эта очередность имеет важное значение в поддержке совместимости.
		 *
		 * Стоит заметить, что можно указывать как абсолютные пути вида /full/path/to/file.ext,
		 * так и относительные - to/file.ext. Движок не накладывает на них органичений. Все вышесказанное
		 * относится только к путям, начинающихся с ./
		 *
		 * @param string $filePath путь до файла
		 */
		public function __construct($filePath) {
			$this->setFilePath($filePath);
		}

		/** Удалить файл из файловой системы */
		public function delete() {
			if (is_writable($this->filepath)) {
				return unlink($this->filepath);
			}

			return false;
		}

		/**
		 * Послать HTTP заголовки для того, чтобы браузер начал скачивать файл
		 * @param bool $deleteAfterDownload = false удалить файл после скачивания
		 * @todo: вынести функционал скачивания в отдельный класс, применить его в UmiCms\System\Response
		 */
		public function download($deleteAfterDownload = false) {
			$downloadMode = $this->getDownloadMode();

			switch ($downloadMode) {
				case 'nginx': {
					$this->downloadByNginx($deleteAfterDownload);
					break;
				}
				default: {
					$this->downloadByApache($deleteAfterDownload);
				}
			}
		}

		/**
		 * Возвращает режим скачивания файла
		 * @return string
		 */
		public function getDownloadMode() {
			$umiConfig = mainConfiguration::getInstance();
			$downloadMode = (string) $umiConfig->get('kernel', 'umi-file-download-mode');

			switch ($downloadMode) {
				case 'nginx': {
					return $downloadMode;
				}
				default: {
					return 'apache';
				}
			}
		}

		/** @inheritdoc */
		public function copy($target) {
			$success = copy($this->getFilePath(), $target);

			if ($success) {
				$this->setFilePath($target);
			}

			return $this;
		}

		/** @inheritdoc */
		public function isExists() {
			return !$this->getIsBroken();
		}

		/** @inheritdoc */
		public function isReadable() {
			return $this->isReadable;
		}

		/** @inheritdoc */
		public function isWritable() {
			return $this->isWritable;
		}

		/** @inheritdoc */
		public function setFilePath($path) {
			$path = $this->prepareFilePath($path);
			$path = str_replace("\\", '/', $path);

			if ($path !== $this->filepath) {
				$this->filepath = $path;
				$this->loadInfo();
			}

			return $this;
		}

		/**
		 * Подгатавливает путь до файла
		 * @param string $filePath путь до файла
		 * @return string
		 */
		private function prepareFilePath($filePath) {
			$filePath = str_replace('//', '/', $filePath);
			$filePath = str_replace("\\\\", "\\", $filePath);

			if (mb_substr($filePath, 0, 2) == './') {
				$cwd = str_replace('/\\/', '/', getcwd());

				if ($cwd != CURRENT_WORKING_DIR) {
					$relativeFilepath = $filePath;
					$currentDirFilepath = preg_replace("/^\.\//", CURRENT_WORKING_DIR . '/', $filePath);

					if (file_exists($relativeFilepath)) {
						if (!defined('UMICMS_CLI_MODE')) { // проверка, чтобы не было мусора при работе установщика
							trigger_error(
								"Files started with './' should be placed relative to CURRENT_WORKING_DIR '" .
								CURRENT_WORKING_DIR . "'. " . "File '$relativeFilepath' loaded from '" . $cwd . "' ",
								E_USER_DEPRECATED
							);
						}
						$filePath = $relativeFilepath;
					} else {
						$filePath = $currentDirFilepath;
					}
				}
			}

			return $filePath;
		}

		/**
		 * Проверяет, является ли переданное расширение файла допустимым для загрузки на сервер
		 * @param string $extension расширение файла
		 * @return boolean true, если расширение является допустимым.
		 */
		public static function isAllowedFileType($extension) {
			$extension = mb_strtolower($extension);
			if (in_array($extension, self::$forbiddenFileTypes)) {
				return false;
			}

			if (in_array($extension, self::$allowedFileTypes) || in_array($extension, self::$allowedImageTypes)) {
				return true;
			}

			if (self::$allowedUserFileTypes === null) {
				$auth = Service::Auth();
				$userId = $auth->getUserId();
				$appendedFileExtensions = umiObjectsCollection::getInstance()
					->getObject($userId)
					->getValue('appended_file_extensions');
				self::$allowedUserFileTypes = [];
				foreach (explode(',', $appendedFileExtensions) as $appendedExtension) {
					$appendedExtension = mb_strtolower(trim($appendedExtension));
					if ($appendedExtension !== '') {
						self::$allowedUserFileTypes[] = $appendedExtension;
					}
				}
			}

			if (in_array($extension, self::$allowedUserFileTypes)) {
				return true;
			}

			return false;
		}

		/**
		 * Возвращает расширение файла по его имени в нижнем регистре
		 * @param string $fileNameOrPath имя файла или путь до него
		 * @return string
		 */
		public static function getFileExtension($fileNameOrPath) {
			$fileNamePartList = explode('.', $fileNameOrPath);
			$extension = (string) array_pop($fileNamePartList);
			return mb_convert_case($extension, MB_CASE_LOWER);
		}

		/**
		 * @todo: вынести функционал загрузки в отдельный классы или классы
		 * @param $name
		 * @param $temp_path
		 * @param $size
		 * @param $target_folder
		 * @return int
		 */
		public static function manualUpload($name, $temp_path, $size, $target_folder) {
			if (!$size || !$name || !self::isLegalUploadedFileName($name) || !is_uploaded_file($temp_path)) {
				return 1;
			}

			$extension = self::getFileExtension($name);

			if (!self::isAllowedFileType($extension)) {
				return 2;
			}

			$name = mb_substr($name, 0, mb_strlen($name) - mb_strlen($extension) - 1);

			if (self::isTransliterateUploadedFiles()) {
				$name = translit::convert($name);
			}
			$name .= '.' . $extension;

			$new_path = $target_folder . '/' . $name;

			if (!self::isLegalUploadedFileName($name)) {
				return 3;
			}

			if (is_uploaded_file($temp_path)) {
				$new_path = umiFile::getUnconflictPath($new_path);

				if (move_uploaded_file($temp_path, $new_path)) {
					chmod($new_path, self::$mask);

					$new_path = self::getRelPath($new_path);
					return new self::$class_name($new_path);
				}

				return 5;
			}

			return 6;
		}

		/**
		 * Возвращает, является ли название загруженного файла валидным
		 * @param string $name название файла
		 * @return bool
		 */
		private static function isLegalUploadedFileName($name) {
			return $name !== '.htaccess';
		}

		/**
		 * Загружает файл, переданный в POST-запросе.
		 * Работает в двух режимах:
		 *
		 * 1) Файл был передан как raw-тело POST-запроса
		 * 2) Файл был передан как значение поля html-формы
		 *
		 * У названия поля есть два формата:
		 *
		 * 1) data[photo]
		 * 2) data[1774][photo]
		 *
		 * Значения параметров в формате 1:
		 *
		 * $group_name == 'data'
		 * $var_name == 'photo'
		 * $id == '1774'
		 *
		 * Значения параметров в формате 2:
		 *
		 * $group_name == 'data'
		 * $var_name == 'photo'
		 * $id == false
		 *
		 * @param string|bool $groupName название "группы" поля html-формы
		 * @param string|bool $varName название значения поля html-формы
		 * @param string $targetDirectory директория, в которую нужно сохранить загруженный файл
		 * @param string|bool $id ID редактируемого объекта, @see DataForms::saveEditedObject()
		 * @return iUmiFile|bool новый файл или false в случае ошибки
		 */
		public static function upload($groupName, $varName, $targetDirectory, $id = false) {
			$directory = new umiDirectory(realpath($targetDirectory));
			if ($directory->getIsBroken() || !$directory->isWritable()) {
				return false;
			}

			if ($groupName === false && $varName === false) {
				return self::uploadByRawPostBody($directory->getPath());
			}

			return self::uploadByHtmlForm($groupName, $varName, $directory->getPath(), $id);
		}

		/**
		 * Создает заданный файл при условии его отсутствия
		 * @param string $path путь до файла
		 * @return bool
		 */
		public static function requireFile($path) {
			$file = new umiFile($path);

			if ($file->isExists()) {
				return true;
			}

			$directory = new umiDirectory(dirname($path));

			if ($directory->getIsBroken()) {
				umiDirectory::requireFolder($directory->getPath());
			}

			touch($path);
			chmod($path, 0777);

			return $file->refresh()
				->isExists();
		}

		/**
		 * Загружает файл как raw-тело POST-запроса
		 * @param string $target_folder директория, в которую нужно сохранить загруженный файл
		 * @see umiFile::upload()
		 * @return iUmiFile|bool новый файл или false в случае ошибки
		 */
		private static function uploadByRawPostBody($target_folder) {
			$name = $_REQUEST['filename'];
			$content = Service::Request()->getRawBody();
			$extension = self::getFileExtension($name);

			if (!self::isAllowedFileType($extension)) {
				return false;
			}

			$name = mb_substr($name, 0, mb_strlen($name) - mb_strlen($extension) - 1);

			if (self::isTransliterateUploadedFiles()) {
				$name = translit::convert($name);
			}

			$name .= '.' . $extension;

			if (!self::isLegalUploadedFileName($name)) {
				return false;
			}

			$new_path = $target_folder . '/' . $name;

			if (file_put_contents($new_path, $content) == 0) {
				return false;
			}

			chmod($new_path, self::$mask);
			$new_path = self::getRelPath($new_path);
			return new self::$class_name($new_path);
		}

		/**
		 * Загружает файл, переданный как значение поля html-формы.
		 * Рассчитан на загрузку файлов во множественном режиме,
		 * @link http://php.net/manual/en/features.file-upload.multiple.php
		 * @see umiFile::upload()
		 *
		 * @param string $group_name название "группы" поля html-формы
		 * @param string $var_name название значения поля html-формы
		 * @param string $target_folder директория, в которую нужно сохранить загруженный файл
		 * @param string|bool $id ID редактируемого объекта, @see DataForms::saveEditedObject()
		 * @return umiFile|bool новый файл или false в случае ошибки
		 */
		private static function uploadByHtmlForm($group_name, $var_name, $target_folder, $id) {
			$files = Service::Request()
				->Files();
			$files_array = $files->getArrayCopy();

			if (!is_array($files_array)) {
				return false;
			}

			if (!isset($files_array[$group_name]) && isset($files_array['pics'])) {
				$files_array[$group_name] = $files_array['pics'];
				$group_name = 'pics';
			}

			if (!array_key_exists($group_name, $files_array)) {
				return false;
			}

			$file_info = $files_array[$group_name];

			if (isset($file_info['size'][$var_name])) {
				$id = false;
			}

			if ($id === false) {
				$size = (isset($file_info['size'][$var_name]) ? $file_info['size'][$var_name] : 0);
			} else {
				$size = (isset($file_info['size'][$id][$var_name]) ? $file_info['size'][$id][$var_name] : 0);
			}

			if ($size == 0) {
				return false;
			}

			$name = ($id === false) ? $file_info['name'][$var_name] : $file_info['name'][$id][$var_name];
			$extension = self::getFileExtension($name);

			if (!self::isAllowedFileType($extension)) {
				return false;
			}

			$name = mb_substr($name, 0, mb_strlen($name) - mb_strlen($extension) - 1);

			if (self::isTransliterateUploadedFiles()) {
				$name = translit::convert($name);
			}
			$name .= '.' . $extension;

			if (!self::isLegalUploadedFileName($name)) {
				return false;
			}

			$temp_path = ($id === false) ? $file_info['tmp_name'][$var_name] : $file_info['tmp_name'][$id][$var_name];
			if (!is_uploaded_file($temp_path)) {
				return false;
			}

			$new_path = umiFile::getUnconflictPath($target_folder . "/{$name}");

			if (!move_uploaded_file($temp_path, $new_path)) {
				return false;
			}

			chmod($new_path, self::$mask);
			$new_path = self::getRelPath($new_path);

			return new self::$class_name($new_path);
		}

		// Ф-я распаковки zip-архива
		public static function upload_zip($var_name, $file = '', $folder = '__default__', $addWaterMark = false) {
			if ($file === '__default__') {
				$file = USER_IMAGES_PATH . '/cms/data/';
			}
			if ($file == '') {
				$temp_path = $var_name['tmp_name'];
				$name = $var_name['name'];

				list($umi_temp1, $umi_temp2, $extension) = array_values(getPathInfo($name));
				$name = mb_substr($name, 0, mb_strlen($name) - mb_strlen($extension));
				if (self::isTransliterateUploadedFiles()) {
					$name = translit::convert($name);
				}
				$name .= '.' . $extension;

				$new_path = $folder . $name;
				$upload_path = SYS_TEMP_PATH . '/uploads';
				if (!is_dir($upload_path)) {
					mkdir($upload_path);
				}
				$new_zip_path = $upload_path . '/' . $name;

				if ($var_name['size'] == 0) {
					return false;
				}

				if (is_uploaded_file($temp_path)) {

					$new_path = umiFile::getUnconflictPath($new_path);
					if (move_uploaded_file($temp_path, $new_zip_path)) {
						chmod($new_zip_path, self::$mask);
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {

				$file = CURRENT_WORKING_DIR . '/' . $file;

				if (!file_exists($file) || !is_writable($file)) {
					return 'File does not exist!';
				}

				$path_parts = getPathInfo($file);

				if ($path_parts['extension'] != 'zip') {
					return "It's not zip-file!";
				}

				$new_path = $file;
				$new_zip_path = $file;
			}

			$oldAddWaterMark = self::$addWaterMark;
			self::$addWaterMark = $addWaterMark;

			$archive = new UmiZipArchive($new_zip_path);

			// Проверяем, что каждый файл не превышает заданного максимального размера для изображений
			$list = $archive->listContent();
			if (umiCount($list) < 1) {
				throw new publicAdminException(getLabel('zip-file-empty'));
			}

			$upload_max_filesize = cmsController::getInstance()->getModule('data')->getAllowedMaxFileSize();
			$max_img_filesize = Service::Registry()->get('//settings/max_img_filesize');

			if (!$max_img_filesize) {
				$max_img_filesize = $upload_max_filesize;
			}
			// Значение указывается в мегабайтах, нам нужны байты
			$max_img_filesize = $max_img_filesize * 1024 * 1024;

			$summary = 0;
			foreach ($list as $key => $oneFile) {
				$extension = self::getFileExtension($oneFile['filename']);
				// Пропускаем файлы, которые не будут распаковываться
				if (!umiFile::isAllowedImageType($extension)) {
					unset($list[$key]);
					continue;
				}
				// Проверяем размер файла, не должен превышать разрешенный для изображений
				if ($oneFile['size'] > $max_img_filesize) {
					throw new publicAdminException(getLabel('zip-file-image-max-size') . "{$oneFile['filename']}");
				}

				$summary += $oneFile['size'];
			}

			// Повторная проверка, что у нас есть файлы для обработки
			if (umiCount($list) < 1) {
				throw new publicAdminException(getLabel('zip-file-images-absent'));
			}

			// Проверяем, что у нас есть место для распаковки изображений
			if (!checkAllowedDiskSize($summary)) {
				throw new publicAdminException(getLabel('zip-file-images-no-free-size'));
			}

			$list = $archive->extract($folder, true, 'callbackPreExtract', 'callbackPostExtract');

			self::$addWaterMark = $oldAddWaterMark;

			if (!is_array($list)) {
				throw new coreException ('Zip extracting error: ' . $archive->errorInfo());
			}

			// unlink zip
			if (is_writable($new_zip_path)) {
				unlink($new_zip_path);
			}

			return $list;
		}

		/**
		 * Получить название файла
		 * @return string название файла
		 */
		public function getFileName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function getDirName($webMode = false) {
			$path = $this->dirname;

			if ($webMode) {
				return $this->convertAbsoluteToLocal($path);
			}

			return $path;
		}

		/**
		 * Получить время последней модификации файла
		 * @return int время последней модификации файла в UNIX TIMESTAMP
		 */
		public function getModifyTime() {
			return $this->modify_time;
		}

		/** @inheritdoc */
		public function getContent() {
			return file_get_contents($this->getFilePath());
		}

		/** @inheritdoc */
		public function putContent($content) {
			return file_put_contents($this->getFilePath(), $content);
		}

		/** @inheritdoc */
		public function getHash() {
			return md5_file($this->getFilePath());
		}

		/** @inheritdoc */
		public function getExt($caseSensitive = false) {
			$extension = $this->ext;

			if ($caseSensitive) {
				return $extension;
			}

			return mb_strtolower($extension);
		}

		/**
		 * Получить размер файла
		 * @return int размер файла в байтах
		 */
		public function getSize() {
			return $this->size;
		}

		/**
		 * Получить путь до файла в файловой системе
		 * @param bool $web_mode если true, то путь будет указан относительно DOCUMENT_ROOT'а
		 * @return string путь до файла
		 */
		public function getFilePath($web_mode = false) {
			$path = $this->filepath;

			if ($web_mode) {
				return $this->convertAbsoluteToLocal($path);
			}

			return $path;
		}

		/**
		 * Возвращает порядок вывода
		 * @return int|null
		 */
		public function getOrder() {
			return $this->order;
		}

		/**
		 * Устанавливает порядок вывода
		 * @param int $order порядок вывода
		 */
		public function setOrder($order) {
			$this->order = (int) $order;
		}

		/**
		 * Возвращает идентификатор
		 * @return int|null
		 */
		public function getId() {
			return $this->id;
		}

		/**
		 * Устанавливает идентификатор
		 * @param int $id идентификатор
		 */
		public function setId($id) {
			$this->id = (int) $id;
		}

		/** @inheritdoc */
		public function refresh() {
			$this->loadInfo();
			return $this;
		}

		/**
		 * Конвертирует абсолютный путь в локальный
		 * @param string $path абсолютный путь
		 * @return string
		 */
		private function convertAbsoluteToLocal($path) {
			$sIncludePath = ini_get('include_path');

			if ($sIncludePath != '.' && mb_substr($path, 0, mb_strlen($sIncludePath)) === $sIncludePath) {
				return '/' . mb_substr($path, mb_strlen($sIncludePath));
			}

			$sIncludePath = CURRENT_WORKING_DIR;

			if (mb_substr($path, 0, mb_strlen($sIncludePath)) === $sIncludePath) {
				return mb_substr($path, mb_strlen($sIncludePath));
			}

			return (mb_substr($path, 0, 2) == './') ? ('/' . mb_substr($path, 2, mb_strlen($path) - 2)) : $path;
		}

		private function loadInfo() {
			$pathInfo = pathinfo($this->filepath);
			$this->dirname = isset($pathInfo['dirname']) ? $pathInfo['dirname'] : '';
			$this->name = isset($pathInfo['basename']) ? $pathInfo['basename'] : '';
			$this->ext = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';
			$this->setReadable(is_readable($this->filepath));
			$this->setWritable(is_writable($this->filepath));

			if (!is_file($this->filepath)) {
				$this->is_broken = true;
				return false;
			}

			$this->is_broken = false;
			$this->modify_time = filemtime($this->filepath);
			$this->size = filesize($this->filepath);

			if ($this->isSecurityIgnored()) {
				return true;
			}

			if ($this->getExt() == 'php' || $this->getExt() == 'php5' || $this->getExt() == 'phtml') {
				$this->is_broken = true;
			}

			if ($this->name == '.htaccess') {
				$this->is_broken = true;
			}
		}

		/** @inheritdoc */
		public function setIgnoreSecurity($flag = true) {
			$this->ignoreSecurity = (bool) $flag;
			return $this;
		}

		/**
		 * Возвращает путь до файла
		 * @return string
		 */
		public function __toString() {
			$filepath = $this->getFilePath(true);
			return $filepath === null ? '' : $filepath;
		}

		/**
		 * Узнать, все ли в порядке с файлом, на который ссылается объект umiFile
		 * @return bool true, если нет ошибок
		 */
		public function getIsBroken() {
			return (bool) $this->is_broken;
		}

		/**
		 * Возвращает неконфликтный с существующими вариант имени файла с индексом
		 * @param string $newPath - предлагаемое имя
		 * @return string
		 * @throws coreException
		*/
		public static function getUnconflictPath($newPath) {
			if (!file_exists($newPath)) {
				return $newPath;
			}

			$info = getPathInfo($newPath);
			$dirname = $info['dirname'];
			$filename = $info['filename'];
			$ext = $info['extension'];

			for ($i = 1; $i < 512; $i++) {
				$resultPath = $dirname . '/' . $filename . $i . '.' . $ext;
				if (!file_exists($resultPath)) {
					return $resultPath;
				}
			}
			throw new coreException('This is really hard to happen');
		}

		public static function getAddWaterMark() {
			return self::$addWaterMark;
		}

		public static function isAllowedImageType($extension) {
			return in_array($extension, self::$allowedImageTypes);
		}

		protected static function getRelPath($path) {
			$cwd = realpath(getcwd());
			return '.' . mb_substr(realpath($path), mb_strlen($cwd));
		}

		/**
		 * Производить ли транслитерацию названий загружаемых файлов
		 * @return bool
		 */
		protected static function isTransliterateUploadedFiles() {
			$config = mainConfiguration::getInstance();
			$transliterateFileNames = $config->get('system', 'transliterate-uploaded-files');
			return ($transliterateFileNames === null || $transliterateFileNames == 1);
		}

		/**
		 * Реализация umiFile::download() для Apache
		 * @param bool $deleteAfterDownload удалить файл после скачивания
		 */
		protected function downloadByApache($deleteAfterDownload) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->reset();
			$buffer->setHeader('Cache-Control', 'public, must-revalidate');
			$buffer->setHeader('Pragma', 'no-cache');
			$buffer->contentType('application/force-download');
			$buffer->setHeader('Accept-Ranges', 'bytes');
			$buffer->setHeader('Content-Encoding', 'None');
			$buffer->setHeader('Content-Transfer-Encoding', 'Binary');
			$buffer->setHeader('Content-Disposition', 'attachment; filename=' . $this->getFileName());

			$filePath = realpath($this->getFilePath());
			$buffer->push(file_get_contents($filePath));

			if ($deleteAfterDownload) {
				$this->delete();
			}

			$buffer->end();
		}

		/**
		 * Реализация umiFile::download() для Nginx
		 * @param bool $deleteAfterDownload удалить файл после скачивания
		 */
		protected function downloadByNginx($deleteAfterDownload) {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->clear();
			$buffer->reset();
			$buffer->contentType('application/force-download');
			$buffer->setHeader('X-Accel-Redirect', '/' . trim($this->getFilePath(), ".\\/"));
			$buffer->contentType('application/force-download');
			$buffer->setHeader('Accept-Ranges', 'bytes');
			$buffer->setHeader('Content-Encoding', 'None');
			$buffer->setHeader('Content-Transfer-Encoding', 'Binary');
			$buffer->setHeader('Content-Disposition', 'attachment; filename=' . $this->getFileName());
			$buffer->setHeader('Expires', '0');
			$buffer->setHeader('Cache-Control', 'public, must-revalidate, post-check=0, pre-check=0');
			$buffer->setHeader('Pragma', 'no-cache');

			$buffer->push(' ');

			if ($deleteAfterDownload) {
				$this->delete();
			}

			$buffer->end();
		}

		/**
		 * Устанавливает флаг доступности файла на чтение
		 * @param bool $flag флаг доступности чтения
		 * @return $this
		 */
		protected function setReadable($flag = true) {
			$this->isReadable = (bool) $flag;
			return $this;
		}

		/**
		 * Устанавливает флаг доступности файла на запись
		 * @param bool $flag флаг доступности записи
		 * @return $this
		 */
		protected function setWritable($flag = true) {
			$this->isWritable = (bool) $flag;
			return $this;
		}

		/**
		 * Определяет игнорируется ли безопасность файла
		 * @return bool
		 */
		private function isSecurityIgnored() {
			return $this->ignoreSecurity;
		}
	}

	// Контроль извлекаемых из zip-архива файлов
	function callbackPreExtract($p_event, &$p_header) {
		$info = getPathInfo($p_header['filename']);

		$extension = mb_strtolower($info['extension']);
		if (!umiFile::isAllowedImageType($extension)) {
			return 0;
		}

		$basename = mb_substr($info['basename'], 0, (mb_strlen($info['basename']) - mb_strlen($info['extension'])) - 1);
		$basename = translit::convert($basename);
		$p_header['filename'] = $info['dirname'] . '/' . $basename . '.' . $info['extension'];

		$p_header['filename'] = umiFile::getUnconflictPath($p_header['filename']);

		return 1;
	}

	function callbackPostExtract($p_event, &$p_header) {
		$info = getPathInfo($p_header['stored_filename']);
		$extension = isset($info['extension']) ? mb_strtolower($info['extension']) : '';
		$filename = $p_header['filename'];

		if (umiFile::isAllowedImageType($extension)) {
			$imgSize = @getimagesize($filename);
			if (!is_array($imgSize)) {
				@unlink($filename);
			}

			if (umiFile::getAddWaterMark()) {
				if (umiImageFile::addWatermark($filename) !== false) {
					return 1;
				}
			}

			$jpgThroughGD = (bool) mainConfiguration::getInstance()->get('kernel', 'jpg-through-gd');
			if ($jpgThroughGD) {
				if ($extension == 'jpg' || $extension == 'jpeg') {
					$res = imagecreatefromjpeg($filename);
					if ($res) {
						imagejpeg($res, $filename, 100);
						imagedestroy($res);
					}
				}
			}
		} else {
			unlink($filename);
		}

		return 1;
	}
