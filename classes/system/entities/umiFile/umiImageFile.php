<?php

	use UmiCms\Classes\System\Utils\Watermark\Settings\iSettings;
	use UmiCms\Service;

	/** Класс для работы с файлами изображений, наследуется от класса umiFile */
	class umiImageFile extends umiFile implements iUmiImageFile {

		const IMAGE_TYPE_GIF = 'GIF';

		const IMAGE_TYPE_JPG = 'JPG';

		const IMAGE_TYPE_JPEG = 'JPEG';

		const IMAGE_TYPE_PNG = 'PNG';

		const IMAGE_TYPE_WBMP = 'WBMP';

		const IMAGE_TYPE_BMP = 'BMP';

		const IMAGE_TYPE_SWF = 'SWF';

		const IMAGE_TYPE_XBM = 'XBM';

		const IMAGE_TYPE_SVG = 'SVG';

		const IMAGE_TYPE_ICO = 'ICO';

		private static $aSupportedTypes;

		private static $useWatermark = false;

		private static $CurrentBit = 0;

		private $sImageType;

		private $iImageWidth;

		private $iImageHeight;

		/** @var string|null $alt альтернативный текст */
		private $alt;

		/** @var string|null $title наименование изображения */
		private $title;

		/**
		 * Конструктор, принимает в качестве аргумента путь до файла в локальной файловой системе.
		 * @param string $filePath путь до файла в локальной файловой системе
		 */
		public function __construct($filePath) {
			parent::__construct($filePath);

			if (!$this->is_broken) {
				$this->is_broken = !(self::getIsImage($this->name) && is_readable($this->filepath));
			}

			if ($this->getIsBroken()) {
				return;
			}

			if ($this->isSvg()) {
				return $this->loadSvgParams();
			}

			$arImageInfo = @getimagesize($this->filepath);
			$this->iImageWidth = $arImageInfo[0];
			$this->iImageHeight = $arImageInfo[1];

			switch ($arImageInfo['mime']) {
				case 'image/gif': {
					$this->sImageType = self::IMAGE_TYPE_GIF;
					break;
				}
				case 'image/jpg': {
					$this->sImageType = self::IMAGE_TYPE_JPG;
					break;
				}
				case 'image/jpeg': {
					$this->sImageType = self::IMAGE_TYPE_JPEG;
					break;
				}
				case 'image/png': {
					$this->sImageType = self::IMAGE_TYPE_PNG;
					break;
				}
				case 'image/vnd.wap.wbmp': {
					$this->sImageType = self::IMAGE_TYPE_WBMP;
					break;
				}
				case 'image/bmp': {
					$this->sImageType = self::IMAGE_TYPE_BMP;
					break;
				}
				case 'application/x-shockwave-flash': {
					$this->sImageType = self::IMAGE_TYPE_SWF;
					break;
				}
			}
		}

		/** @inheritdoc */
		public function getMimeType() {
			switch (strtoupper($this->getExt())) {
				case self::IMAGE_TYPE_GIF : {
					return 'image/gif';
				}
				case self::IMAGE_TYPE_JPEG : {
					return 'image/jpeg';
				}
				case self::IMAGE_TYPE_JPG : {
					return 'image/jpg';
				}
				case self::IMAGE_TYPE_PNG : {
					return 'image/png';
				}
				case self::IMAGE_TYPE_BMP : {
					return 'image/bmp';
				}
				case self::IMAGE_TYPE_SWF : {
					return 'application/x-shockwave-flash';
				}
				case self::IMAGE_TYPE_SVG : {
					return 'image/svg+xml';
				}
				case self::IMAGE_TYPE_ICO : {
					return 'image/x-icon';
				}
				default : {
					throw new ErrorException(sprintf('Unexpected type "%s"', $this->getExt()));
				}
			}
		}

		/**
		 * Определяет является ли изображение svg файлом
		 * @return bool
		 */
		private function isSvg() {
			return $this->getExt() === mb_strtolower(self::IMAGE_TYPE_SVG);
		}

		/** Загружает параметры svg файла */
		private function loadSvgParams() {
			$xml = simplexml_load_file($this->getFilePath());
			$attributeList = $xml->attributes();
			$this->iImageWidth = (int) $attributeList->width;
			$this->iImageHeight = (int) $attributeList->height;
			$this->sImageType = self::IMAGE_TYPE_SVG;
		}

		/**
		 * Получить список поддерживаемых расширений файлов
		 * @return array массив, стостоящий из допустимых расширений файлов изображений
		 */
		public static function getSupportedImageTypes() {
			if (self::$aSupportedTypes === null) {
				self::$aSupportedTypes = [];
				self::$aSupportedTypes[] = self::IMAGE_TYPE_GIF;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_JPG;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_JPEG;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_PNG;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_WBMP;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_BMP;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_SWF;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_SVG;
				self::$aSupportedTypes[] = self::IMAGE_TYPE_ICO;
			}

			return self::$aSupportedTypes;
		}

		/** Указывает на необходимость добавления водного знака к следующей загружаемой картинке */
		public static function setWatermarkOn() {
			self::$useWatermark = true;
		}

		/** Отключает водный знак */
		public static function setWatermarkOff() {
			self::$useWatermark = false;
		}

		/**
		 * Загружает файл из запроса и сохраняет локально.
		 * Информация о файле берется из массива $_FILES[$group_name]["size"][$var_name].
		 * Возвращает загруженный файл или `false` в случае неудачи.
		 *
		 * @param string $groupName
		 * @param string $varName
		 * @param string $targetDirectory локальная папка, в которую необходимо сохранить файл
		 * @param string|bool $id ID редактируемого объекта
		 * @return iUmiImageFile|bool
		 */
		public static function upload($groupName, $varName, $targetDirectory, $id = false) {
			self::$class_name = __CLASS__;
			$file = parent::upload($groupName, $varName, $targetDirectory, $id);

			$regedit = Service::Registry();
			$max_img_filesize = (int) $regedit->get('//settings/max_img_filesize');
			$upload_max_filesize = (int) ini_get('upload_max_filesize');
			$max_img_filesize = ($max_img_filesize < $upload_max_filesize) ? $max_img_filesize : $upload_max_filesize;

			$filesize = (int) filesize('.' . $file);
			$max_img_filesize = $max_img_filesize * 1024 * 1024;

			if ($max_img_filesize > 0) {
				if ($max_img_filesize < $filesize) {
					unlink('.' . $file);
					return false;
				}
			}

			//Пропуск через процессор, чтобы избавиться от EXIF
			$jpgThroughGD = (bool) mainConfiguration::getInstance()->get('kernel', 'jpg-through-gd');
			if ($jpgThroughGD) {
				$imageProcessor = imageUtils::getImageProcessor();
				$imageProcessor->optimize('.' . $file, IMAGE_COMPRESSION_LEVEL);
			}

			// Если нужно добавляем водяной знак и отключаем его для следующих изображений
			if (self::$useWatermark) {
				self::addWatermark('./' . $file);
			}

			self::setWatermarkOff();

			return $file;
		}

		/**
		 * Проверить, является ли файл допустимым изображением
		 * @param string $filePath путь до файла, который необходимо проверить
		 * @return bool true, если файл является изображением
		 */
		public static function getIsImage($filePath) {
			$extension = self::getFileExtension($filePath);
			$upperExtension = mb_convert_case($extension, MB_CASE_UPPER);
			return in_array($upperExtension, self::getSupportedImageTypes());
		}

		/** @inheritdoc */
		public function getAlt() {
			return $this->alt;
		}

		/** @inheritdoc */
		public function setAlt($alt) {
			$this->alt = (string) $alt;
			return $this;
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->title;
		}

		/** @inheritdoc */
		public function setTitle($title) {
			$this->title = (string) $title;
			return $this;
		}

		/** @inheritdoc */
		public function getWidth() {
			return $this->iImageWidth;
		}

		/** @inheritdoc */
		public function getHeight() {
			return $this->iImageHeight;
		}

		/**
		 * Получить тип изображения
		 * @return string Тип изображения (значение одной из констант класса: IMAGE_TYPE_*)
		 */
		public function getType() {
			return $this->sImageType;
		}

		/**
		 * Добавляет водный знак на изображение
		 * @param string $filePath путь до изображения
		 * @return boolean
		 */
		public static function addWatermark($filePath) {
			if (!empty($_REQUEST['disable_watermark'])) {
				return false;
			}

			$settings = self::getCurrentWatermarkSettings();
			$srcWaterImage = $settings->getImagePath();
			$alphaWaterImage = $settings->getAlpha();
			$valignWaterImage = $settings->getVerticalAlign();
			$halignWaterImage = $settings->getHorizontalAlign();

			if (!file_exists($srcWaterImage)) {
				return false;
			}

			if (!$alphaWaterImage) {
				$alphaWaterImage = 100;
			}

			if (!$valignWaterImage) {
				$valignWaterImage = 'bottom';
			}

			if (!$halignWaterImage) {
				$halignWaterImage = 'right';
			}

			$waterImgParam = self::createImage($srcWaterImage);
			$srcImgParam = self::createImage($filePath);
			$imageFileInfo = getPathInfo($filePath);

			if (!$waterImgParam || !$srcImgParam) {
				return false;
			}

			$x_ins = 0;
			$y_ins = 0;

			switch ($halignWaterImage) {
				case 'center' : {
					$x_ins = floor(($srcImgParam['width'] - $waterImgParam['width']) / 2);
					break;
				}
				case 'right' : {
					$x_ins = $srcImgParam['width'] - $waterImgParam['width'];
				}
			}

			switch ($valignWaterImage) {
				case 'center' : {
					$y_ins = floor(($srcImgParam['height'] - $waterImgParam['height']) / 2);
					break;
				}
				case 'bottom' : {
					$y_ins = $srcImgParam['height'] - $waterImgParam['height'];
				}
			}

			$tmp = $waterImgParam['im'];
			$cut = imagecreatetruecolor($waterImgParam['width'], $waterImgParam['height']);
			imagecopy(
				$cut,
				$srcImgParam['im'],
				0,
				0,
				$x_ins,
				$y_ins,
				$waterImgParam['width'],
				$waterImgParam['height']
			);
			imagecopy(
				$cut,
				$tmp,
				0,
				0,
				0,
				0,
				$waterImgParam['width'],
				$waterImgParam['height']
			);
			imagecopymerge(
				$srcImgParam['im'],
				$cut,
				$x_ins,
				$y_ins,
				0,
				0,
				$waterImgParam['width'],
				$waterImgParam['height'],
				$alphaWaterImage
			);

			switch ($imageFileInfo['extension']) {
				case 'jpeg' :
				case 'jpg'  :
				case 'JPEG' :
				case 'JPG'  : {
					imagejpeg($srcImgParam['im'], $filePath, 90);
					break;
				}
				case 'png' :
				case 'PNG' : {
					imagepng($srcImgParam['im'], $filePath);
					break;
				}
				case 'gif' :
				case 'GIF' : {
					imagegif($srcImgParam['im'], $filePath);
					break;
				}
				case 'bmp' :
				case 'BMP' :
					imagewbmp($srcImgParam['im'], $filePath);
					break;
			}

			imagedestroy($srcImgParam['im']);
			imagedestroy($waterImgParam['im']);
		}

		/**
		 * Возвращает настройки водяного знака, применимые для текущего запроса
		 * @return iSettings
		 */
		private static function getCurrentWatermarkSettings() {
			$factory = Service::WatermarkSettingsFactory();
			$settings = $factory->createCustom();

			if ($settings->shouldUseCustomSettings()) {
				return $settings;
			}

			return $factory->createCommon();
		}

		/**
		 * Создает и возвращает идентификатор изображения
		 * @param string $imageFilePath путь до изображения
		 * @return array|bool массив: идентификатор (im), ширина (width), высота (height)
		 */
		public static function createImage($imageFilePath) {
			$oldPath = $imageFilePath;
			$imageFilePath = str_replace(CURRENT_WORKING_DIR, '', $imageFilePath);

			$image_identifier = 0;
			$pathinfo = parse_url($imageFilePath);

			$imageFilePath = (mb_substr($pathinfo['path'], 0, 1) == '/')
				? mb_substr($pathinfo['path'], 1)
				: $pathinfo['path'];

			if (!file_exists($imageFilePath)) {
				$imageFilePath = $oldPath;
			}

			list ($width, $height, $type, $attr) = getimagesize($imageFilePath);

			$types = [
				'GIF' => '1',
				'JPG' => '2',
				'PNG' => '3',
				'BMP' => '6',
				'WBMP' => '15',
				'XBM' => '16'
			];

			switch ($type) {
				case $types['GIF'] : {
					$image_identifier = imagecreatefromgif($imageFilePath);
					break;
				}
				case $types['JPG'] : {
					$image_identifier = imagecreatefromjpeg($imageFilePath);
					break;
				}
				case $types['PNG'] : {
					$image_identifier = imagecreatefrompng($imageFilePath);
					break;
				}
				case $types['BMP'] : {
					$image_identifier = self::imagecreatefrombmp($imageFilePath);
					break;
				}
				case $types['WBMP'] : {
					$image_identifier = imagecreatefromwbmp($imageFilePath);
					break;
				}
				case $types['XBM']: {
					$image_identifier = imagecreatefromxbm($imageFilePath);
				}
			}

			if (!$image_identifier) {
				return false;
			}

			return [
				'im' => $image_identifier,
				'width' => $width,
				'height' => $height
			];
		}

		/** Создает изображение из bmp, т.к. встроенная поддержка в php отсутствует
		 * Нарыто на просторах интернета
		 * @param string $file путь до изображения
		 * @return resource type image идентификатор изображения
		 */
		private static function imagecreatefrombmp($file) {
			$f = fopen($file, 'r');
			$Header = fread($f, 2);

			if ($Header == 'BM') {
				$Size = self::freaddword($f);
				$Reserved1 = self::freadword($f);
				$Reserved2 = self::freadword($f);
				$FirstByteOfImage = self::freaddword($f);

				$SizeBITMAPINFOHEADER = self::freaddword($f);
				$Width = self::freaddword($f);
				$Height = self::freaddword($f);
				$biPlanes = self::freadword($f);
				$biBitCount = self::freadword($f);
				$RLECompression = self::freaddword($f);
				$WidthxHeight = self::freaddword($f);
				$biXPelsPerMeter = self::freaddword($f);
				$biYPelsPerMeter = self::freaddword($f);
				$NumberOfPalettesUsed = self::freaddword($f);
				$NumberOfImportantColors = self::freaddword($f);

				if ($biBitCount < 24) {
					$img = imagecreate($Width, $Height);
					$Colors = pow(2, $biBitCount);
					for ($p = 0; $p < $Colors; $p++) {
						$B = self::freadbyte($f);
						$G = self::freadbyte($f);
						$R = self::freadbyte($f);
						$Reserved = self::freadbyte($f);
						$Palette[] = imagecolorallocate($img, $R, $G, $B);
					}

					if ($RLECompression == 0) {
						$Zbytek = (4 - ceil($Width / (8 / $biBitCount)) % 4) % 4;

						for ($y = $Height - 1; $y >= 0; $y--) {
							$CurrentBit = 0;
							for ($x = 0; $x < $Width; $x++) {
								$C = self::freadbits($f, $biBitCount);
								imagesetpixel($img, $x, $y, $Palette[$C]);
							}
							if ($CurrentBit != 0) {
								self::freadbyte($f);
							}
							for ($g = 0; $g < $Zbytek; $g++) {
								self::freadbyte($f);
							}
						}
					}
				}

				if ($RLECompression == 1) {
					$y = $Height;
					$pocetb = 0;

					while (true) {
						$y--;
						$prefix = self::freadbyte($f);
						$suffix = self::freadbyte($f);
						$pocetb += 2;
						$echoit = false;

						if ($echoit) {
							//echo "Prefix: $prefix Suffix: $suffix<BR>";
							if (($prefix == 0) && ($suffix == 1)) {
								break;
							}

							if (feof($f)) {
								break;
							}

							while (!(($prefix == 0) and ($suffix == 0))) {
								if ($prefix == 0) {
									$pocet = $suffix;
									$Data .= fread($f, $pocet);
									$pocetb += $pocet;
									if ($pocetb % 2 == 1) {
										self::freadbyte($f);
										$pocetb++;
									}
								}

								if ($prefix > 0) {
									$pocet = $prefix;
									for ($r = 0; $r < $pocet; $r++) {
										$Data .= chr($suffix);
									}
								}
							}

							$prefix = self::freadbyte($f);
							$suffix = self::freadbyte($f);
							$pocetb += 2;
							//if($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
						}

						for ($x = 0; $x < mb_strlen($Data); $x++) {
							imagesetpixel($img, $x, $y, $Palette[ord($Data[$x])]);
						}

						$Data = '';
					}
				}

				if ($RLECompression == 2) {
					$y = $Height;
					$pocetb = 0;

					while (true) {
						$y--;
						$prefix = self::freadbyte($f);
						$suffix = self::freadbyte($f);
						$pocetb += 2;
						$echoit = false;

						//if ($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
						if (($prefix == 0) and ($suffix == 1)) {
							break;
						}

						if (feof($f)) {
							break;
						}

						while (!(($prefix == 0) and ($suffix == 0))) {
							if ($prefix == 0) {
								$pocet = $suffix;
								$CurrentBit = 0;
								for ($h = 0; $h < $pocet; $h++) {
									$Data .= chr(self::freadbits($f, 4));
								}

								if ($CurrentBit != 0) {
									self::freadbits($f, 4);
								}

								$pocetb += ceil($pocet / 2);

								if ($pocetb % 2 == 1) {
									self::freadbyte($f);
									$pocetb++;
								}
							}

							if ($prefix > 0) {
								$pocet = $prefix;
								$i = 0;

								for ($r = 0; $r < $pocet; $r++) {
									if ($i % 2 == 0) {
										$Data .= chr($suffix % 16);
									} else {
										$Data .= chr(floor($suffix / 16));
									}
									$i++;
								}
							}

							$prefix = self::freadbyte($f);
							$suffix = self::freadbyte($f);
							$pocetb += 2;
							//if ($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
						}

						for ($x = 0; $x < mb_strlen($Data); $x++) {
							imagesetpixel($img, $x, $y, $Palette[ord($Data[$x])]);
						}

						$Data = '';
					}
				}

				if ($biBitCount == 24) {
					$img = imagecreatetruecolor($Width, $Height);
					$Zbytek = $Width % 4;

					for ($y = $Height - 1; $y >= 0; $y--) {
						for ($x = 0; $x < $Width; $x++) {
							$B = self::freadbyte($f);
							$G = self::freadbyte($f);
							$R = self::freadbyte($f);
							$color = imagecolorexact($img, $R, $G, $B);

							if ($color == -1) {
								$color = imagecolorallocate($img, $R, $G, $B);
							}

							imagesetpixel($img, $x, $y, $color);
						}

						for ($z = 0; $z < $Zbytek; $z++) {
							self::freadbyte($f);
						}
					}
				}

				fclose($f);
				return $img;
			}
			fclose($f);
		}

		private static function freaddword($f) {
			$b1 = self::freadword($f);
			$b2 = self::freadword($f);
			return $b2 * 65536 + $b1;
		}

		private static function freadword($f) {
			$b1 = self::freadbyte($f);
			$b2 = self::freadbyte($f);
			return $b2 * 256 + $b1;
		}

		private static function freadbyte($f) {
			return ord(fread($f, 1));
		}

		private static function freadbits($f, $count) {
			$Byte = self::freadbyte($f);
			$LastCBit = self::$CurrentBit;

			self::$CurrentBit += $count;
			if (self::$CurrentBit == 8) {
				self::$CurrentBit = 0;
			} else {
				fseek($f, ftell($f) - 1);
			}
			return self::RetBits($Byte, $LastCBit, $count);
		}

		private static function RetBits($byte, $start, $len) {
			$bin = self::decbin8($byte);
			return bindec(mb_substr($bin, $start, $len));
		}

		private static function decbin8($d) {
			return self::decbinx($d, 8);
		}

		private static function decbinx($d, $n) {
			$bin = decbin($d);
			$sbin = mb_strlen($bin);
			for ($j = 0; $j < $n - $sbin; $j++) {
				$bin = "0$bin";
			}
			return $bin;
		}

		/**
		 * Сохраняет переданное изображение в файл
		 * @param resource $newImage ссылка на ресурс изображения (GD)
		 */
		private function saveFile($newImage) {

			switch ($this->getType()) {

				case self::IMAGE_TYPE_GIF:
					imagegif($newImage, $this->getFilePath());
					break;

				case self::IMAGE_TYPE_JPEG:
				case self::IMAGE_TYPE_JPG:
					imagejpeg($newImage, $this->getFilePath());
					break;

				case self::IMAGE_TYPE_PNG:
					imagepng($newImage, $this->getFilePath());
					break;

				case self::IMAGE_TYPE_WBMP:
				case self::IMAGE_TYPE_BMP:
					imagewbmp($newImage, $this->getFilePath());
					break;

				case self::IMAGE_TYPE_XBM:
					imagexbm($newImage, $this->getFilePath());
					break;
			}

			$this->__construct($this->getFilePath());
		}

		/**
		 * Получить ресурс изображения, с которым можно будет работать в GD
		 * @return Resource
		 * @throws coreException
		 */
		private function getImageResource() {
			$oImageData = $this->createImage($this->getFilePath());
			if (!$oImageData) {
				throw new coreException('Failed to load image');
			}
			return $oImageData['im'];
		}

		/**
		 * Поворачивает изображение на 90 градусов по часовой стрелке
		 * @return bool
		 */
		public function rotate() {

			if ($this->isSvg()) {
				return false;
			}

			$oImage = $this->getImageResource();

			$oImageRotated = $this->rotate_cw_90deg($oImage);

			$this->saveFile($oImageRotated);

			imagedestroy($oImage);
			imagedestroy($oImageRotated);

			return true;
		}

		/**
		 * Функция корректного поворота изображения на 90 градусов по часовой стрелке
		 * @param $image
		 * @return bool|resource
		 */
		private function rotate_cw_90deg($image) {
			$iWidth = imagesx($image);
			$iHeight = imagesy($image);
			$newImage = @imagecreatetruecolor($iHeight, $iWidth);
			$iColor = 0;
			if ($this->getType() === self::IMAGE_TYPE_PNG) {
				imagealphablending($newImage, false);
				imagesavealpha($newImage, true);
				$iColor = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
				imagefilledrectangle($newImage, 0, 0, $iHeight, $iWidth, $iColor);
			}
			$rotatedImage = imagerotate($image, 270, $iColor, true);
			if ($newImage) {
				imagecopyresized(
					$newImage,
					$rotatedImage,
					0,
					0,
					0,
					0,
					imagesx($newImage),
					imagesy($newImage),
					imagesx($rotatedImage),
					imagesy($rotatedImage)
				);
				return $newImage;
			}

			return false;
		}

		/**
		 * Обрезает изображение
		 * @param int $iSelectionLeft Координата Х верхнего левого угла области обрезания
		 * @param int $iSelectionWidth Ширина области обрезания
		 * @param int $iSelectionTop Координата Y верхнего левого угла области обрезания
		 * @param int $iSelectionHeight Высота области обрезания
		 * @return bool
		 */
		public function crop($iSelectionLeft, $iSelectionWidth, $iSelectionTop, $iSelectionHeight) {

			if ($this->isSvg()) {
				return false;
			}

			$oImage = $this->getImageResource();
			$oImageCropped = imagecreatetruecolor($iSelectionWidth, $iSelectionHeight);

			if ($this->getType() === self::IMAGE_TYPE_PNG) {
				imagealphablending($oImageCropped, false);
				imagesavealpha($oImageCropped, true);
				$iColor = imagecolorallocatealpha($oImageCropped, 255, 255, 255, 127);
				imagefilledrectangle($oImageCropped, 0, 0, $iSelectionWidth, $iSelectionHeight, $iColor);
			}

			imagecopyresampled(
				$oImageCropped,
				$oImage,
				0,
				0,
				$iSelectionLeft,
				$iSelectionTop,
				$iSelectionWidth,
				$iSelectionHeight,
				$iSelectionWidth,
				$iSelectionHeight
			);

			$this->saveFile($oImageCropped);

			return true;
		}

		/**
		 * Изменяет размер изображения
		 * @param int $iNewWidth новое значение ширины изображения
		 * @param int $iNewHeight новое значение высоты изображения
		 * @return bool
		 */
		public function resize($iNewWidth, $iNewHeight) {

			if ($this->isSvg()) {
				return false;
			}

			$oImage = $this->getImageResource();
			$oImageResized = imagecreatetruecolor($iNewWidth, $iNewHeight);

			if ($this->getType() === self::IMAGE_TYPE_PNG) {
				imagealphablending($oImageResized, false);
				imagesavealpha($oImageResized, true);
				$iColor = imagecolorallocatealpha($oImageResized, 255, 255, 255, 127);
				imagefilledrectangle($oImageResized, 0, 0, $iNewWidth, $iNewHeight, $iColor);
			}

			imagecopyresampled($oImageResized, $oImage, 0, 0, 0, 0, $iNewWidth, $iNewHeight, $this->getWidth(), $this->getHeight());

			$this->saveFile($oImageResized);

			return true;
		}

		/**
		 * @deprecated
		 * @return bool
		 */
		public static function imageOptim($source, $quality = 75) {
			$imageProcessor = imageUtils::getImageProcessor();
			$imageProcessor->optimize('.' . $source, $quality);
		}
	}
