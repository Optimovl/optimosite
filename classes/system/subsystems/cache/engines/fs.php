<?php

	/** Реализация хранилища кеша в файлах */
	class fsCacheEngine implements iCacheEngine {

		/** @var iUmiDirectory $root корневая директория хранилища */
		protected $root;

		/**
		 * @var int entLevel уровень вложенности промежуточных директориц до файла хранилища
		 *
		 *  -> countComments0=hash          // часть ключа с некоторым хешем
		 *      ->c                         // первый уровень
		 *          ->o                     // второй уровень
		 *              ->m                 // третий уровень
		 *                  ->m             // четверты уровень
		 *                      ->e         // пятый уровень
		 *                          nts     // файл хранилище
		 *
		 */
		const NESTING_LEVEL = 5;

		/** @const string SEPARATOR разделитель кешируемых данных и времени жизни в файле хранилище */
		const SEPARATOR = "\n";

		/** @const string NAME название хранилища */
		const NAME = 'fs';

		/** @inheritdoc */
		public function __construct() {
			$rootPath = SYS_CACHE_RUNTIME . 'fs-cache/';
			$root = new umiDirectory($rootPath);

			if ($root->getIsBroken()) {
				$root::requireFolder($root->getPath());
				$root->refresh();
			}

			$this->setRoot($root);
		}

		/** @inheritdoc */
		public function getName() {
			return self::NAME;
		}

		/** @inheritdoc */
		public function saveRawData($key, $data, $expire) {
			if ($expire <= 0) {
				$this->delete($key);
			}

			$path = $this->calcPathByKey($key);
			$file = new umiFile($path);

			if ($file->getIsBroken()) {
				$file::requireFile($file->getFilePath());
			}

			$content = $this->packContent($data, $expire);

			return (bool) @$file->putContent($content);
		}

		/** @inheritdoc */
		public function loadRawData($key) {
			$path = $this->calcPathByKey($key);
			$file = new umiFile($path);

			if ($file->getIsBroken()) {
				return false;
			}

			$content = @$file->getContent();

			if (!is_string($content) || empty($content)) {
				return false;
			}

			$expire = $this->unpackExpire($content);

			if (time() > ($file->getModifyTime() + $expire)) {
				$this->delete($key);
				return false;
			}

			return $this->unpackData($content);
		}

		/** @inheritdoc */
		public function delete($key) {
			$path = $this->calcPathByKey($key);
			$cacheFile = new umiFile($path);

			if ($cacheFile->isExists()) {
				return $cacheFile->delete();
			}

			return false;
		}

		/** @inheritdoc */
		public function flush() {
			return $this->getRoot()->deleteRecursively();
		}

		/** @inheritdoc */
		public function getIsConnected() {
			$root = $this->getRoot();
			return $root->isExists() && $root->isWritable() && $root->isReadable();
		}

		/**
		 * Вычисляет путь до файла хранилища по ключу кешируемых данных
		 * @param string $key ключ
		 * @return string
		 */
		protected function calcPathByKey($key) {
			$length = self::NESTING_LEVEL;
			$parts = array_reverse(preg_split("/[_\.\/:]+/", $key));

			$lastPart = array_pop($parts);

			if (mb_strlen($lastPart) < $length) {
				$lastPart = str_repeat('0', $length - mb_strlen($lastPart)) . $lastPart;
			}

			for ($i = 0; $i < $length; $i++) {
				$parts[] = mb_substr($lastPart, $i, 1);
			}

			if ($length < mb_strlen($lastPart)) {
				$parts[] = mb_substr($lastPart, $length);
			}

			return $this->getRoot()->getPath() . '/' . implode('/', $parts);
		}

		/**
		 * Устанавливает корневую директорию хранилища
		 * @param iUmiDirectory $directory хранилище
		 * @return $this
		 */
		private function setRoot(iUmiDirectory $directory) {
			$this->root = $directory;
			return $this;
		}

		/**
		 * Возвращает корневую директорию хранилища
		 * @return iUmiDirectory
		 */
		private function getRoot() {
			return $this->root;
		}

		/**
		 * Упаковывывает кешируемые данные и время жизни кеша
		 * @param mixed $data кешируемые данные
		 * @param int $expire время жизни
		 * @return string
		 */
		private function packContent($data, $expire) {
			return (int) $expire . self::SEPARATOR . serialize($data);
		}

		/**
		 * Распаковывает время жизни кеша
		 * @param string $content содержимое хранилища
		 * @return int
		 */
		private function unpackExpire($content) {
			$separatorPosition = mb_strpos($content, self::SEPARATOR);
			return (int) mb_substr($content, 0, $separatorPosition);
		}

		/**
		 * Распаковывает кешируемые данные
		 * @param string $content содержимое хранилища
		 * @return mixed
		 */
		private function unpackData($content) {
			$separatorPosition = mb_strpos($content, self::SEPARATOR);
			$data = mb_substr($content, $separatorPosition + 1);
			return unserialize($data);
		}
	}
