<?php

	/** Класс обработки изображений с помощью модуля  php5-gd */
	class gdProcessor implements iImageProcessor {

		/** @inheritdoc */
		public function crop($imagePath, $top, $left, $width, $height) {
			$file = new umiImageFile($imagePath);

			if ($file->getIsBroken()) {
				return false;
			}

			try {
				$file->crop($left, $width, $top, $height);
			} catch (coreException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function rotate($imagePath) {
			$file = new umiImageFile($imagePath);

			if ($file->getIsBroken()) {
				return false;
			}

			try {
				$file->rotate();
			} catch (coreException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function resize($imagePath, $width, $height) {
			$file = new umiImageFile($imagePath);

			if ($file->getIsBroken()) {
				return false;
			}

			try {
				$file->resize($width, $height);
			} catch (coreException $e) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function optimize($imagePath, $quality = 75) {
			$file = new umiImageFile($imagePath);
			$quality = getImageQualityLevel($quality);

			if ($file->getIsBroken()) {
				return false;
			}

			try {
				$info = @getimagesize($imagePath);
			} catch (Exception $e) {
				return false;
			}

			if (!$info) {
				return false;
			}

			if ($info['mime'] == 'image/jpeg') {
				$image = imagecreatefromjpeg($imagePath);

				if (!$image) {
					return false;
				}

				imagejpeg($image, $imagePath, $quality);
				imagedestroy($image);
			} elseif ($info['mime'] == 'image/png') {
				$image = imagecreatefrompng($imagePath);

				if (!$image) {
					return false;
				}

				imagealphablending($image, true);
				imagesavealpha($image, true);

				$png_quality = 9 - (($quality * 9) / 100);
				imagepng($image, $imagePath, $png_quality);
				imagedestroy($image);
			}

			return true;
		}

		/** @inheritdoc */
		public function info($imagePath) {
			$file = new umiImageFile($imagePath);

			if ($file->getIsBroken()) {
				return false;
			}

			try {
				$info = @getimagesize($imagePath);
			} catch (Exception $e) {
				return [
					'mime' => '',
					'height' => 0,
					'width' => 0
				];
			}

			return [
				'mime' => $info['mime'],
				'height' => $info[1],
				'width' => $info[0]
			];
		}

		/** @inheritdoc */
		public function thumbnail($imagePath, $thumbPath, $width, $height, $quality = false) {
			$image = new umiImageFile($imagePath);

			if ($image->getIsBroken()) {
				return false;
			}

			$info = $this->info($imagePath);
			$sourceWidth = $image->getWidth();
			$sourceHeight = $image->getHeight();

			$thumb = imagecreatetruecolor($width, $height);
			$thumb_white_color = imagecolorallocate($thumb, 255, 255, 255);

			$source_array = $image->createImage($imagePath);
			$imagePath = $source_array['im'];

			imagefill($thumb, 0, 0, $thumb_white_color);
			imagecolortransparent($thumb, $thumb_white_color);

			switch ($info['mime']) {
				case 'image/gif':
					imagealphablending($imagePath, true);
					imagealphablending($thumb, true);
					break;
				case 'image/png':
					imagealphablending($thumb, false);
					imagesavealpha($thumb, true);
					imagealphablending($imagePath, false);
					imagesavealpha($imagePath, true);
					break;

				default:
			}

			imagecopyresampled($thumb, $imagePath, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

			switch ($info['mime']) {
				case 'image/gif':
					$res = imagegif($thumb, $thumbPath);
					break;
				case 'image/png':
					$res = imagepng($thumb, $thumbPath);
					break;
				default:
					$res = imagejpeg($thumb, $thumbPath, getImageQualityLevel($quality));
			}

			if (!$res) {
				throw new coreException(getLabel('label-errors-16008'));
			}

			imagedestroy($imagePath);
			imagedestroy($thumb);

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

			$thumb = imagecreatetruecolor($width, $height);
			$quality = getImageQualityLevel($quality);

			$image = new umiImageFile($imagePath);

			if ($image->getIsBroken()) {
				return false;
			}

			$source_array = $image->createImage($imagePath);
			$source = $source_array['im'];
			$info = $this->info($imagePath);
			$mime = $info['mime'];

			if ($width * 4 < $cropWidth && $height * 4 < $cropHeight) {
				$tempData = [
					'width' => round($width * 4),
					'height' => round($height * 4),
				];

				$tempData['image'] = imagecreatetruecolor($tempData['width'], $tempData['height']);

				if ($mime == 'image/gif') {
					$tempData['image_white'] = imagecolorallocate($tempData['image'], 255, 255, 255);
					imagefill($tempData['image'], 0, 0, $tempData['image_white']);
					imagecolortransparent($tempData['image'], $tempData['image_white']);
					imagealphablending($source, true);
					imagealphablending($tempData['image'], true);
				} else {
					imagealphablending($tempData['image'], false);
					imagesavealpha($tempData['image'], true);
				}

				imagecopyresampled(
					$tempData['image'],
					$source,
					0,
					0,
					$xCord,
					$yCord,
					$tempData['width'],
					$tempData['height'],
					$cropWidth,
					$cropHeight
				);

				imagedestroy($source);

				$source = $tempData['image'];
				$cropWidth = $tempData['width'];
				$cropHeight = $tempData['height'];

				$xCord = 0;
				$yCord = 0;
				unset($tempData);
			}

			if ($mime == 'image/gif') {
				$thumbWithWhiteColor = imagecolorallocate($thumb, 255, 255, 255);
				imagefill($thumb, 0, 0, $thumbWithWhiteColor);
				imagecolortransparent($thumb, $thumbWithWhiteColor);
				imagealphablending($source, true);
				imagealphablending($thumb, true);
			} else {
				imagealphablending($thumb, false);
				imagesavealpha($thumb, true);
			}

			imagecopyresampled($thumb, $source, 0, 0, $xCord, $yCord, $width, $height, $cropWidth, $cropHeight);

			if ($isSharpen) {
				$thumb = makeThumbnailFullUnsharpMask($thumb, 80, .5, 3);
			}

			switch ($mime) {
				case 'image/gif':
					$res = imagegif($thumb, $thumbPath);
					break;
				case 'image/png':
					$png_quality = 9 - (($quality * 9) / 100);
					$res = imagepng($thumb, $thumbPath, $png_quality);
					break;
				default:
					$res = imagejpeg($thumb, $thumbPath, $quality);
			}

			if (!$res) {
				throw new coreException(getLabel('label-errors-16008'));
			}

			imagedestroy($source);
			imagedestroy($thumb);

			return true;
		}

		/**
		 * Возвращаем тип обработчика изображений
		 * @return string
		 */
		public function getLibType() {
			return 'gd';
		}
	}
