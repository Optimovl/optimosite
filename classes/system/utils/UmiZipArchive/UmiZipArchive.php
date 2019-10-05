<?php

	/**
	 * Class UmiZipArchive
	 * Файловый архив, сжатый Zip.
	 * При добавлении файла в архив переданный до него путь будет полностью соответствовать пути
	 * до файла в архиве, то есть будут созданы все директории, указанные в пути. Например:
	 * $zip->add('/path/to/file.txt');
	 * В результате выполнения кода в архив будет добавлен файл с путем /path/to/file.txt
	 * В методах добавления файлов и директорий в архив присутствуют два параметра $pathToRemove и
	 * $pathToAdd
	 * $pathToRemove - строка, представляющая часть пути, которая будет вырезана из пути
	 * до файла или директории в архиве. Например:
	 * $zip->add('files/image.png', 'files');
	 * В результате выполнения кода в корень архив будет добавлен файл image.png
	 * $pathToAdd - строка, представляющая часть пути, который будет добавлен к пути
	 * до файла или директории. Например:
	 * $zip->add('files/image.png', null, 'base');
	 * В результате выполнения кода в архив будет добавлен файл /base/files/image.png
	 * Сначала производится урезка пути, а потом его приращение.
	 */
	class UmiZipArchive implements IUmiZipArchive {

		/** @const используемый по умолчанию класс для работы с Zip архивами */
		const ARCHIVE_CLASS_DEFAULT = 'ZipArchive';

		/** @var string путь до архива */
		private $name;

		/** @var mixed объект, через который выполняются непосредственные действия с архивом */
		private $archive;

		/** @var string префикс сообщений об ошибках */
		private $errorPrefix = '';

		/** @var string текст последней возникшей ошибки */
		private $lastError = '';

		/** @var string имя класса, предоставляющего методы для работы с Zip архивами */
		protected $archiveClass;

		/** @var array список кодов класса, работающего с Zip архивами */
		protected $codes;

		/**
		 * Конструктор
		 * Выполняет начальную инициализацию объекта. Реальные действия по созданию архива не происходят.
		 * @param string $archiveName путь до архива
		 * @param string $zipArchiveClass имя класса, предоставляющего методы для работы с Zip архивами
		 * @throws Exception
		 */
		public function __construct($archiveName, $zipArchiveClass = self::ARCHIVE_CLASS_DEFAULT) {
			if (!class_exists($zipArchiveClass)) {
				throw new Exception($this->getFullErrorMessage('Class ' . $zipArchiveClass . ' not found'));
			}

			$this->name = $archiveName;
			$this->archiveClass = $zipArchiveClass;
			$this->archive = new $this->archiveClass;

			$this->initCodes();
		}

		/**
		 * Создает Zip архив с файлами и директориями, перечисленными в $files.
		 * @param $files
		 * @param string|null $pathToRemove вырезаемый путь из пути до файлов или директорий в архиве
		 * @param string|null $pathToAdd добавляемый путь к пути до файлов или директорий в архиве
		 * @throws Exception, если архив уже существует.
		 * @example
		 * $zip->create('/path/to/file.txt', 'to', 'root');
		 * После выполнение кода в архиве будет создан файл с путем /root/path/file.txt
		 * @return array содержимое архива
		 */
		public function create($files, $pathToRemove = null, $pathToAdd = null) {
			$this->setErrorPrefix(getLabel('error-create-zip-archive'));

			if (file_exists($this->name)) {
				throw new Exception($this->getFullErrorMessage($this->codes['error_exists']));
			}

			$this->openAndAdd($files, $pathToRemove, $pathToAdd, $this->codes['create']);
			return $this->listContent();
		}

		/**
		 * Добавляет файлы или директории в архив. Если архив не существует, то он будет создан.
		 * @param array|string $files пути до директорий или файлов
		 * @param string|null $pathToRemove вырезаемый путь из пути до файлов или директорий в архиве
		 * @param string|null $pathToAdd добавляемый путь к пути до файлов или директорий в архиве
		 */
		public function add($files, $pathToRemove = null, $pathToAdd = null) {
			$this->setErrorPrefix(getLabel('error-put-files-to-zip-archive'));
			$this->openAndAdd($files, $pathToRemove, $pathToAdd, $this->codes['create']);
		}

		/**
		 * Выполняет распаковку архива
		 * @param string $path путь до директории, в которую будет распаковано содержимое архива
		 * @param bool $ignoreFolders если true, то все файлы в архиве, независимо от их
		 * расположения в архиве, будут распакованы в директорию $path (не сохраняя вложенность)
		 * @param callable|null $beforeCallback будет вызвана перед выполнением распаковки
		 * @param callable|null $afterCallback будет вызвана после выполнения распаковки
		 * @return array|bool содержимое архива
		 * @throws Exception
		 */
		public function extract($path = '.', $ignoreFolders = false, $beforeCallback = null, $afterCallback = null) {
			$this->open();

			if (!is_dir($path)) {
				mkdir($path, 0777, true);
			}

			$extractingStatus = $this->archive->extractTo($path);

			if (!$extractingStatus) {
				return $extractingStatus;
			}

			if ($ignoreFolders) {
				for ($i = 0; $i < $this->archive->numFiles; $i++) {
					$fileData = $this->archive->statIndex($i);
					$filePath = $fileData['name'];
					$extractedPath = $path . DIRECTORY_SEPARATOR . $filePath;

					if (is_file($extractedPath)) {
						$newPath = DIRECTORY_SEPARATOR . $this->getBaseName($extractedPath);
						if (!is_file($newPath)) {
							rename($extractedPath, $path . DIRECTORY_SEPARATOR . $this->getBaseName($extractedPath));
						}
					}
				}

				for ($i = 0; $i < $this->archive->numFiles; $i++) {
					$fileData = $this->archive->statIndex($i);
					$filePath = $fileData['name'];
					$extractedPath = $path . DIRECTORY_SEPARATOR . $filePath;

					if ($this->isFolderPath($filePath) && $this->isDirEmpty($extractedPath)) {
						rmdir($extractedPath);
					}
				}
			}

			$this->convertFolderNames($path);
			$this->close();

			foreach ($this->listContent() as $extractedFile) {
				$extractedPath = $path . $extractedFile['filename'];

				if (is_file($extractedPath)) {
					$this->executeCallback($afterCallback, $extractedPath);
				}
			}

			return $this->listContent();
		}

		/**
		 * Возвращае информацию о содержимом архива
		 * @return array
		 * @throws Exception
		 */
		public function listContent() {
			$this->open();
			$list = [];

			for ($i = 0; $i < $this->archive->numFiles; $i++) {
				$item = [];
				$fileData = $this->archive->statIndex($i);
				$filePath = $this->cp437ToUtf8($fileData['name']);
				$fileName = $this->getBaseName($filePath);

				$item['filename'] = $item['stored_filename'] = $fileName;
				$item['is_folder'] = $this->isFolderPath($filePath);
				$item['size'] = $fileData['size'];

				$list[] = $item;
			}

			$this->close();

			return $list;
		}

		/**
		 * Возвращает текст, возникшей ошибки
		 * @return string
		 */
		public function errorInfo() {
			return $this->lastError;
		}

		/**
		 * Возвращает имя архива
		 * @return string имя архива
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * Открывает(создает) архив и добавляет в него файлы
		 * @param array|string $files пути до файлов или директорий
		 * @param string|null $pathToRemove вырезаемый путь из пути до файлов или директорий в архиве
		 * @param string|null $pathToAdd добавляемый путь к пути до файлов или директорий в архиве
		 * @param int $flagsOpen флаги открытия архива
		 * @throws Exception
		 */
		protected function openAndAdd($files, $pathToRemove = null, $pathToAdd = null, $flagsOpen) {
			$this->open($flagsOpen);
			$this->addFilesList($files, $pathToRemove, $pathToAdd);
			$this->close();
		}

		/**
		 * Открывает архив
		 * @param int|null $flags флаги открытия архива
		 * @return bool
		 * @throws Exception
		 */
		protected function open($flags = null) {
			$openingStatus = $this->archive->open($this->name, $flags);

			if ($openingStatus !== true) {
				throw new Exception($this->getFullErrorMessage($openingStatus));
			}

			return true;
		}

		/**
		 * Добавляет список файлов или директорий в архив
		 * @param array|string $files пути до директорий или файлов
		 * @param string|null $pathToRemove вырезаемый путь из пути до файлов или директорий в архиве
		 * @param string|null $pathToAdd добавляемый путь к пути до файлов или директорий в архиве
		 */
		protected function addFilesList($files, $pathToRemove = null, $pathToAdd = null) {
			$filesList = $this->getFilesList($files);

			foreach ($filesList as $filePath) {
				$this->addFileOrDirectory($filePath, $pathToRemove, $pathToAdd);
			}
		}

		/**
		 * Добавляет файл в архив
		 * @param string $filePath путь до добавляемого файла
		 * @param string|null $pathToRemove вырезаемый путь из пути до файла в архиве
		 * @param string|null $pathToAdd добавляемый путь к пути до файла архиве
		 */
		protected function addFile($filePath, $pathToRemove, $pathToAdd) {
			$this->archive->addFile($filePath, $this->getFilePathInArchive($filePath, $pathToRemove, $pathToAdd));
		}

		/**
		 * Добавляет директорию и все ее содержимое в архив
		 * @param string $path путь до директории
		 * @param string|null $pathToRemove вырезаемый путь из пути до файлов и директорий,
		 * дочерних к указанной директории, включая саму директорию
		 * @param string|null $pathToAdd добавляемый путь к пути до файлов и директорий,
		 * дочерних к указанной директории, включая саму директорию
		 */
		protected function addDirectory($path, $pathToRemove = null, $pathToAdd = null) {
			$this->addEmptyDirectories($path, $pathToRemove, $pathToAdd);

			foreach ($this->getDirectoryIterator($path, FilesystemIterator::SKIP_DOTS) as $fileInfo) {
				$filePath = $fileInfo->getPathName();

				if (is_file($filePath)) {
					$this->addFile($filePath, $pathToRemove, $pathToAdd);
				}
			}
		}

		/**
		 * Добавляет пустые директории в архив, которые содержатся в указанной директории,
		 * включая саму директорию
		 * @param string $path путь до директории
		 * @param string|null $pathToRemove вырезаемый путь из пути до директорий,
		 * дочерних к указанной директории, включая саму директорию
		 * @param string|null $pathToAdd добавляемый путь к пути до директорий,
		 * дочерних к указанной директории, включая саму директорию
		 * @return bool
		 */
		protected function addEmptyDirectories($path, $pathToRemove, $pathToAdd) {
			if (!is_dir($path)) {
				return false;
			}

			foreach ($this->getDirectoryIterator($path) as $fileInfo) {
				if ($fileInfo->getFileName() == '.') {
					$this->archive->addEmptyDir($this->getFilePathInArchive($fileInfo->getPath(), $pathToRemove, $pathToAdd));
				}
			}

			return true;
		}

		/**
		 * Возвращает итератор для рекурсивного перебора всех вложенныъ файлов в директории
		 * @param string $path путь до директории
		 * @param null|int $options опции конструктора RecursiveDirectoryIterator
		 * @return RecursiveIteratorIterator
		 */
		protected function getDirectoryIterator($path, $options = null) {
			$directoryIterator = new RecursiveDirectoryIterator($path, $options);
			return new RecursiveIteratorIterator($directoryIterator);
		}

		/**
		 * Добавляет файл или директорию в архив
		 * @param string $path путь до файла или директории
		 * @param string|null $pathToRemove вырезаемый путь из пути до файлов и директорий
		 * @param string|null $pathToAdd добавляемый путь к пути до файлов и директорий
		 */
		protected function addFileOrDirectory($path, $pathToRemove, $pathToAdd) {
			if (is_file($path)) {
				$this->addFile($path, $pathToRemove, $pathToAdd);
			} elseif (is_dir($path)) {
				$this->addDirectory($path, $pathToRemove, $pathToAdd);
			}
		}

		/**
		 * Возвращает сообщение об ошибке на основе кода ошибки
		 * @param string|int $error код или собщение об ошибке
		 * @return string
		 */
		protected function getErrorMessage($error) {
			if (is_string($error)) {
				return $error;
			}

			switch ($error) {
				case $this->codes['error_exists']:
					$message = getLabel('error-zip-archive-already-exits');
					break;
				case $this->codes['error_open']:
					$message = getLabel('error-cannot-open-file');
					break;
				default:
					$message = getLabel('error-unexpected-exception');
			}

			return $message;
		}

		/** Выполняет установленные действия с архивом (файлом). Закрывает архив. */
		protected function close() {
			$this->archive->close();
		}

		/**
		 * Возвращает путь до файла или директории в архиве
		 * @param string $filePath путь до файла или директории
		 * @param string|null $pathToRemove вырезаемый путь из пути до файла или директории
		 * @param string|null $pathToAdd добавляемый путь к пути до файла или директории
		 * @return mixed
		 */
		protected function getFilePathInArchive($filePath, $pathToRemove = null, $pathToAdd = null) {
			$quotedPathToRemove = preg_quote($pathToRemove, '/');
			$preparedPathToAdd = preg_replace('/^\.?\/?(.+)\.?\/?$/', '$1', $pathToAdd);
			$replacementsCount = 1;

			$pathInArchive = $filePath;

			if ($pathToRemove !== null) {
				$pathInArchive = preg_replace('/' . $quotedPathToRemove . '\/?/', '', $filePath, $replacementsCount);
			}

			if ($pathToAdd !== null) {
				$pathInArchive = preg_replace(
					'/\.?\/?(.+)/',
					$preparedPathToAdd . DIRECTORY_SEPARATOR . '$1',
					$pathInArchive,
					$replacementsCount
				);
			}

			return $pathInArchive;
		}

		/**
		 * Преобразует символы в названии директорий в кодировку UTF-8
		 * @param string $path путь до корневой директории
		 */
		protected function convertFolderNames($path) {
			/** @var SplFileInfo $file */
			foreach ($this->getDirectoryIterator($path) as $file) {

				if ($file->getFilename() == '.') {
					$newPath = $this->cp437ToUtf8($file->getPath());

					if (!is_dir($newPath)) {
						rename($file->getPath(), $this->cp437ToUtf8($file->getPath()));
					}
				}
			}
		}

		/**
		 * Выполняет функцию обратного вызова для файла
		 * @param Callable $callback
		 * @param string $fileFromZip путь до файла
		 * @return bool
		 */
		protected function executeCallback($callback, $fileFromZip) {
			if (is_callable($callback)) {
				$header = [
					'stored_filename' => $fileFromZip,
					'filename' => $fileFromZip
				];
				return (bool) $callback([], $header);
			}

			return true;
		}

		/**
		 * Преобразует кириллические символы из кодировки CP437 в кодировку UTF-8
		 * @param string $string исходная строка
		 * @return string преобразованная в кодировку UTF-8 строка
		 */
		private function cp437ToUtf8($string) {
			$cp437Codes = [
				'Ç',
				'ü',
				'é',
				'â',
				'ä',
				'à',
				'≡',
				'å',
				'ç',
				'ê',
				'ë',
				'è',
				'ï',
				'î',
				'ì',
				'Ä',
				'Å',
				'É',
				'æ',
				'Æ',
				'ô',
				'ö',
				'û',
				'ù',
				'ÿ',
				'Ö',
				'Ü',
				'¢',
				'£',
				'¥',
				'₧',
				'ƒ',
				'á',
				'í',
				'ó',
				'ú',
				'ñ',
				'Ñ',
				'±',
				'ª',
				'º',
				'¿',
				'⌐',
				'¬',
				'½',
				'¼',
				'¡',
				'«',
				'»',
				'α',
				'ß',
				'Γ',
				'π',
				'Σ',
				'σ',
				'µ',
				'τ',
				'Φ',
				'Θ',
				'Ω',
				'δ',
				'∞',
				'φ',
				'ε',
				'∩'
			];
			$utf8Codes = [
				'А',
				'Б',
				'В',
				'Г',
				'Д',
				'Е',
				'Ё',
				'Ж',
				'З',
				'И',
				'Й',
				'К',
				'Л',
				'М',
				'Н',
				'О',
				'П',
				'Р',
				'С',
				'Т',
				'У',
				'Ф',
				'Ц',
				'Ч',
				'Ш',
				'Щ',
				'Ъ',
				'Ы',
				'Ь',
				'Э',
				'Ю',
				'Я',
				'а',
				'б',
				'в',
				'г',
				'д',
				'е',
				'ё',
				'ж',
				'з',
				'и',
				'й',
				'к',
				'л',
				'м',
				'н',
				'о',
				'п',
				'р',
				'с',
				'т',
				'у',
				'ф',
				'x',
				'ц',
				'ч',
				'ш',
				'щ',
				'ъ',
				'ы',
				'ь',
				'э',
				'ю',
				'я'
			];

			return str_replace($cp437Codes, $utf8Codes, $string);
		}

		/**
		 * Возвращает имя файла по переданному пути до него
		 * @param string $filePath путь до файла
		 * @return string
		 */
		private function getBaseName($filePath) {
			preg_match('/\/([a-zA-Zа-яА-Я0-9._\-]+)?$/', $filePath, $matches);

			return (isset($matches[1]) ? $matches[1] : $filePath);
		}

		/**
		 * Заменяет несколько подряд идущих разделителей в пути на один
		 * @param string $path исходный путь
		 * @return mixed
		 */
		private function stripMultipleSeparators($path) {
			return preg_replace('/[^\:\/](\/{2,})/', DIRECTORY_SEPARATOR, $path);
		}

		/**
		 * Возвращает список путей до файлов или директорий
		 * @param string|array $files
		 * @return array
		 */
		private function getFilesList($files) {
			$filesList = $files;

			if (is_string($files)) {
				$filesList = [$files];
			}

			return $filesList;
		}

		/**
		 * Возвращает полный текст сообщения об ошибке.
		 * @param string|int $error код или собщение об ошибке
		 * @return string
		 */
		private function getFullErrorMessage($error) {
			$fullMessage = $this->getErrorPrefix() . $this->getErrorMessage($error);
			$this->lastError = $fullMessage;
			return $fullMessage;
		}

		/**
		 * Возвращает текст текущего префикса ошибки
		 * @return string
		 */
		private function getErrorPrefix() {
			return $this->errorPrefix;
		}

		/**
		 * Устанавливает текст префикса ошибки
		 * @param string $prefix текст нового префикса
		 */
		private function setErrorPrefix($prefix) {
			$this->errorPrefix = $prefix;
		}

		/**
		 * Является ли путь путем до директории
		 * @param string $path путь
		 * @return bool
		 */
		private function isFolderPath($path) {
			return (bool) preg_match('/\/$/', $path);
		}

		/**
		 * Определяет пустая ли директория
		 * @param string $dirPath уть до директории
		 * @return bool|null
		 */
		private function isDirEmpty($dirPath) {
			if (!is_readable($dirPath) || !is_dir($dirPath)) {
				return null;
			}

			return (umiCount(scandir($dirPath)) === 2);
		}

		/** Инициализирует коды класса */
		private function initCodes() {
			$archiveClass = $this->archiveClass;

			$this->codes['error_exists'] = $archiveClass::ER_EXISTS;
			$this->codes['error_open'] = $archiveClass::ER_OPEN;
			$this->codes['create'] = $archiveClass::CREATE;
			$this->codes['overwrite'] = $archiveClass::OVERWRITE;
		}
	}

