<?php

	use UmiCms\Service;

	/** Класс обработки изображений с помощью модуля php5-imagick */
	class imageMagickProcessor implements iImageProcessor {

		/** @inheritdoc */
		public function crop($imagePath, $top, $left, $width, $height) {
			$file = $this->getImagickByImagePath($imagePath);

			if (!$file instanceof Imagick) {
				return false;
			}

			$iccContent = $this->getImageProfileContent($file, 'icc');

			if ($file->cropImage($width, $height, $left, $top)) {
				$file->stripImage();
				$file =	$this->addImageProfile($file, 'icc', $iccContent);
				$file->setImageCompressionQuality(IMAGE_COMPRESSION_LEVEL);
				$file->writeImage();
				$file->destroy();
			} else {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function rotate($imagePath) {
			$file = $this->getImagickByImagePath($imagePath);

			if (!$file instanceof Imagick) {
				return false;
			}

			$iccContent = $this->getImageProfileContent($file, 'icc');

			if ($file->rotateImage(new ImagickPixel('#ffffff'), 90)) {
				$file->stripImage();
				$file =	$this->addImageProfile($file, 'icc', $iccContent);
				$file->setImageCompressionQuality(IMAGE_COMPRESSION_LEVEL);
				$file->writeImage();
				$file->destroy();
			} else {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function resize($imagePath, $width, $height) {
			$file = $this->getImagickByImagePath($imagePath);

			if (!$file instanceof Imagick) {
				return false;
			}

			$iccContent = $this->getImageProfileContent($file, 'icc');

			if ($file->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1)) {
				$file->setImagePage($width, $height, 0, 0);
				$file->stripImage();
				$file =	$this->addImageProfile($file, 'icc', $iccContent);
				$file->setImageCompressionQuality(IMAGE_COMPRESSION_LEVEL);
				$file->writeImage();
				$file->destroy();
			} else {
				return false;
			}

			return true;
		}

		/**
		 * @inheritdoc
		 * @throws ErrorException
		 */
		public function optimize($imagePath, $quality = 75) {
			$file = $this->getImagickByImagePath($imagePath);

			if (!$file instanceof Imagick) {
				return false;
			}

			$iccContent = $this->getImageProfileContent($file, 'icc');
			$mimeType = Service::ImageFactory()
				->create($imagePath)
				->getMimeType();

			switch ($mimeType) {
				case 'image/png': {
					$quality = 100 - $quality;
					$file->resizeImage(
						$file->getImageWidth(),
						$file->getImageHeight(),
						Imagick::FILTER_POINT,
						$quality
					);
					break;
				}

				case 'image/svg+xml':
				case 'image/gif': {
					$file->destroy();
					return true;
				}

				default: {
					$file->setImageCompression(getImageQualityLevel($quality));
				}
			}

			$file->stripImage();
			$file = $this->addImageProfile($file, 'icc', $iccContent);
			$file->writeImage();
			$file->destroy();

			return true;
		}

		/** @inheritdoc */
		public function info($imagePath) {
			$file = $this->getImagickByImagePath($imagePath);

			if (!$file instanceof Imagick) {
				return [
					'mime' => '',
					'height' => 0,
					'width' => 0
				];
			}

			$info = [
				'mime' => $file->getImageMimeType(),
				'height' => $file->getImageHeight(),
				'width' => $file->getImageWidth()
			];

			$file->destroy();

			return $info;
		}

		/** @inheritdoc */
		public function thumbnail($imagePath, $thumb, $width, $height, $quality = false) {
			$file = $this->getImagickByImagePath($imagePath);

			if (!$file instanceof Imagick) {
				return false;
			}

			$iccContent = $this->getImageProfileContent($file, 'icc');

			$file->setBackgroundColor('rgb(255,255,255)');
			$file->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
			$file->setImagePage($width, $height, 0, 0);
			$file->setImageCompressionQuality(getImageQualityLevel($quality));
			$file->stripImage();

			$file =	$this->addImageProfile($file, 'icc', $iccContent);

			try {
				$file->writeImage($thumb);
			} catch (Exception $e) {
				throw new coreException(getLabel('label-errors-16008'));
			}

			$file->destroy();

			return true;
		}

		/** @inheritdoc */
		public function cropThumbnail(
			$imagePath,
			$thumbPath,
			$width,
			$height,
			$cropWidth,
			$cropHeight,
			$xCord,
			$yCord,
			$isSharpen,
			$quality = 75
		) {
			$file = $this->getImagickByImagePath($imagePath);

			if (!$file instanceof Imagick) {
				return false;
			}

			$iccContent = $this->getImageProfileContent($file, 'icc');

			$mime = $file->getImageMimeType();
			$file->setGravity(Imagick::GRAVITY_CENTER);

			if (in_array($mime, ['image/png', 'image/gif'])) {
				$file->setBackgroundColor(new ImagickPixel('transparent'));
			}

			$file->cropImage($cropWidth, $cropHeight, $xCord, $yCord);
			$file->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
			$file->setImagePage($width, $height, 0, 0);
			$file->unsharpMaskImage(0.5, 0.5, 80, 3);
			$file->setImageCompressionQuality(getImageQualityLevel($quality));
			$file->stripImage();

			$file =	$this->addImageProfile($file, 'icc', $iccContent);

			try {
				$file->writeImage($thumbPath);
			} catch (Exception $e) {
				throw new coreException(getLabel('label-errors-16008'));
			}

			$file->destroy();
			return true;
		}

		/** @inheritdoc */
		public function getLibType() {
			return 'imagick';
		}

		/**
		 * Возвращает экземпляр Imagick по пути до изображения
		 * @param string $imagePath путь до изображения
		 * @return bool|Imagick
		 */
		private function getImagickByImagePath($imagePath) {
			try {
				return new Imagick($imagePath);
			} catch (ImagickException $e) {
				return false;
			}
		}

		/**
		 * Добавляет профиль в изображение
		 * @param Imagick $file объект Imagick
		 * @param string $profileName имя профиля
		 * @param string $content содержимое профиля
		 * @return mixed
		 */
		private function addImageProfile(Imagick $file, $profileName, $content) {
			if (!isEmptyString($content)) {
				$file->setImageProfile($profileName, $content);
			}

			return $file;
		}

		/**
		 * Возвращает содержимое профиля изображения
		 * @param Imagick $file объект Imagick
		 * @param string $profileName имя профиля
		 * @return string
		 */
		private function getImageProfileContent(Imagick $file, $profileName) {
			$profiles = $file->getImageProfiles('*', true);
			return isset($profiles[$profileName]) ? $profiles[$profileName] : '';
		}
	}
