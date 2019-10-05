<?php

	class elFinderVolumeUmiLocalFileSystem extends elFinderVolumeLocalFileSystem {

		protected $driverId = 'umi';

		/** @inheritdoc */
		public function _save($fp, $dst, $name, $stat) {
			if (mb_strpos($dst, USER_FILES_PATH) !== false || mb_strpos($dst, USER_IMAGES_PATH) !== false) {
				$quota_byte = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-files-and-images'));
				if ($quota_byte != 0) {
					$all_size = getBusyDiskSize();
					if ($all_size >= $quota_byte) {
						return $this->setError(getLabel('error-files_quota_exceeded'));
					}
				}
			}
			
			$isPaste = $this->ARGS['cmd'] === 'paste';
			$isCopy = $isPaste && empty($this->ARGS['cut']);
			$isUpload = $this->ARGS['cmd'] === 'upload';

			$isCmdCopy = $isCopy ? 'copy' : '';
			$cmd = $isUpload ? 'upload' : $isCmdCopy;

			//Загрузка файла
			$sMethodName = method_exists($this, "_doSave_{$cmd}") ? "_doSave_{$cmd}" : '_doSave_unknown';
			$path = $this->$sMethodName($fp, $dst, $name);

			return $path;
		}

		/** Перекрываем обработку архивов */
		protected function _checkArchivers() {
			return $this->options['archivers'] = $this->options['archive'] = [];
		}

		/**
		 * Действия для сохранения файла при его загрузке
		 * @param string $dst
		 * @param string $name
		 * @return string
		 */
		protected function _doSave_upload($fp, $dst, $name) {
			$cwd = getcwd();
			chdir(CURRENT_WORKING_DIR);
			$filesIndex = 0;
			$filename = '.' . rtrim($dst, "/\\") . DIRECTORY_SEPARATOR . $name;

			if (isset($_FILES['upload'])) {
				foreach ($_FILES['upload']['name'] as $i => $f_name) {
					if ($f_name == $name) {
						$filename = $_FILES['upload']['tmp_name'][$i];
						$filesIndex = $i;
					}
				}
			}

			/** @var data $data */
			$data = cmsController::getInstance()
				->getModule('data');
			$fileSize = (int) filesize($filename);

			if (umiImageFile::getIsImage($name)) {
				$max_img_filesize = $data->getAllowedMaxFileSize('img') * 1024 * 1024;

				if ($max_img_filesize > 0) {
					if ($max_img_filesize < $fileSize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_img_filesize') . ' ' . ($max_img_filesize / 1024 / 1024) . 'M');
					}
				}

				if (getRequest('water_mark')) {
					umiImageFile::setWatermarkOn();
				}

				$file = umiImageFile::upload('upload', $filesIndex, $dst);

			} else {

				$extension = umiFile::getFileExtension($name);

				if (!umiFile::isAllowedFileType($extension)) {
					return $this->setError(elFinder::ERROR_UPLOAD_FILE_MIME);
				}

				$upload_max_filesize = $data->getAllowedMaxFileSize() * 1024 * 1024;

				if ($upload_max_filesize > 0) {
					if ($upload_max_filesize < $fileSize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_filesize') . ' ' . ($upload_max_filesize / 1024 / 1024) . 'M');
					}
				}

				$file = umiFile::upload('upload', $filesIndex, $dst);
			}

			chdir($cwd);

			if (!$file instanceof iUmiFile || $file->getIsBroken()) {
				return $this->setError(elFinder::ERROR_UPLOAD);
			}

			return CURRENT_WORKING_DIR . $file->getFilePath(true);
		}

		/**
		 * Действия для сохранения файла при его копировании
		 * @param string $dst
		 * @param string $name
		 * @return string
		 */
		protected function _doSave_copy($fp, $dst, $name) {
			$path = $dst . DIRECTORY_SEPARATOR . $name;

			if (!($target = @fopen($path, 'wb'))) {
				$this->setError(elFinder::ERROR_COPY);
				return false;
			}

			while (!feof($fp)) {
				fwrite($target, fread($fp, 8192));
			}
			fclose($target);
			@chmod($path, $this->options['fileMode']);
			clearstatcache();

			return $path;
		}

		/**
		 * Неизвестный режим сохранения файла
		 * @param mixed $dst
		 * @param mixed $name
		 * @return false
		 */
		protected function _doSave_unknown($fp, $dst, $name) {
			return $this->setError(elFinder::ERROR_UNKNOWN_CMD);
		}

		/** @inheritdoc */
		protected function _abspath($path) {
			$path = preg_replace('/(\.\.\/?)/i', '', $path);
			return ($path == DIRECTORY_SEPARATOR) ? $this->root : $this->root . DIRECTORY_SEPARATOR . $path;
		}
	}

