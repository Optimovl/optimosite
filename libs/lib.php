<?php

	use UmiCms\Service;

	if (!defined('DEFAULT_THUMBNAIL_MAKE_MEMORY_LIMIT')) {
		define('DEFAULT_THUMBNAIL_MAKE_MEMORY_LIMIT', 128 * 1024 * 1024);
	}

	if (isset($_REQUEST['p'])) {
		$p = $_REQUEST['p'];
		if ($p < 0 && $p != 'all') {
			$p = 0;
		}
		if ($p != 'all') {
			$p = (int) $p;
		}
		$_REQUEST['p'] = $p;
		unset($p);
	}

	/**
	 * Возвращает количество байтов в строке
	 * @param $string
	 * @return int
	 */
	function bytes_strlen($string) {
		$current = mb_internal_encoding();
		mb_internal_encoding('latin1');
		$length = strlen($string);
		mb_internal_encoding($current);
		return $length;
	}

	/**
	 * Возвращает подстроку в однобайтовой кодировке
	 * @see substr
	 *
	 * @param $string
	 * @param $start
	 * @param bool|int $length
	 * @return bool|string
	 */
	function bytes_substr($string, $start, $length = false) {
		$current = mb_internal_encoding();
		mb_internal_encoding('latin1');

		if ($length !== false) {
			$result = substr($string, $start, $length);
		} else {
			$result = substr($string, $start);
		}

		mb_internal_encoding($current);
		return $result;
	}

	function getArrayKey($array, $key) {
		if (!is_array($array)) {
			return false;
		}

		if ($key === false) {
			return null;
		}

		if (array_key_exists($key, $array)) {
			return $array[$key];
		}

		return null;
	}

	/**
	 * Проверяет что значение массива содержит непустой массив по заданному ключу
	 * @param array $array массив
	 * @param mixed $valueKey ключ проверяемого значения
	 * @return bool результат проверки
	 */
	function arrayValueContainsNotEmptyArray(array $array, $valueKey) {
		if (!array_key_exists($valueKey, $array)) {
			return false;
		}

		$value = $array[$valueKey];
		return is_array($value) && count($value) > 0;
	}

	/**
	 * Возвращает уникальные значения массива.
	 * Умеет работать с многомерными массивами, в отличие от array_values.
	 * @param array $array массив
	 * @return array
	 */
	function getDeepArrayUniqueValues(array $array) {
		$result = [];

		foreach ($array as $value) {
			$value = is_array($value) ? getDeepArrayUniqueValues($value) : (array) $value;

			foreach ($value as $item) {
				$result[] = $item;
			}
		}

		return array_unique($result);
	}

	/**
	 * Определяет протокол работы сервера
	 * На данный момент различает HTTP и HTTPS протоколы
	 *
	 * @return string Протокол сервера в нижнем регистре
	 */
	function getServerProtocol() {
		static $protocol = null;

		if (!$protocol) {
			if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) ||
				(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
				mb_strtolower(mb_substr($_SERVER['SERVER_PROTOCOL'], 0, 5)) == 'https') {
				$protocol = 'https';
			} else {
				$protocol = 'http';
			}
		}

		return $protocol;
	}

	/**
	 * Возвращает протокол работы сервера, с которым требуется генерировать ссылки на сайт.
	 * @param iDomain /null $domain домен, на который ведет ссылка. Если не передан - возьмет текущий домен.
	 * Если домен не передан и его нельзя определить - вернет протокол по умолчанию.
	 * @return string
	 */
	function getSelectedServerProtocol(iDomain $domain = null) {
		$domain = $domain ?: Service::DomainDetector()->detect();

		if ($domain instanceof iDomain) {
			return $domain->getProtocol();
		}

		$selectedServerProtocol = mainConfiguration::getInstance()
			->get('system', 'server-protocol');
		$correctServerProtocols = ['http', 'https'];

		if (!is_string($selectedServerProtocol) || !in_array($selectedServerProtocol, $correctServerProtocols)) {
			return 'http';
		}

		return $selectedServerProtocol;
	}

	/**
	 * Определяет, начинается ли строка $source со строки $prefix
	 * @param string $source
	 * @param string $prefix
	 * @return bool
	 */
	function startsWith($source, $prefix) {
		if (!is_string($source) || !is_string($prefix)) {
			return false;
		}

		$prefixLength = mb_strlen($prefix);

		if ($prefixLength === 0) {
			return true;
		}

		if ($source === '') {
			return false;
		}

		return (mb_substr($source, 0, $prefixLength) === $prefix);
	}

	/**
	 * Определяет, оканчивается ли строка $source строкой $postfix
	 * @param string $source
	 * @param string $postfix
	 * @return bool
	 */
	function endsWith($source, $postfix) {
		if (!is_string($source) || !is_string($postfix)) {
			return false;
		}

		$postfixLength = mb_strlen($postfix);

		if ($postfixLength === 0) {
			return true;
		}

		if ($source === '') {
			return false;
		}

		return (mb_substr($source, -$postfixLength) === $postfix);
	}

	/**
	 * Определяет, содержит ли строка $source строку $suffix
	 * @param string $source
	 * @param string $suffix
	 * @return bool
	 */
	function contains($source, $suffix) {
		if (!is_string($source) || !is_string($suffix)) {
			return false;
		}

		if ($suffix === '') {
			return true;
		}

		if ($source === '') {
			return false;
		}

		return mb_strpos($source, $suffix) !== false;
	}

	/**
	 * Вычисляет количество элементов массива или объект класса, реализующего интефейс countable.
	 * Обертка над http://php.net/manual/ru/function.count.php.
	 * Сделан для обратной совместости, так как на php версии 7.2 count() стал выводить предупреждения, если
	 * в него передавать не массив или объект класса, реализующего интефейс countable.
	 * Метод создан для legacy кода, в новом коде можно применять стандартную функцию.
	 * @param array|Countable $arrayOrCountable массив или объект класса, реализующего интефейс countable
	 * @param bool $isRecursive вычислять рекурсивно, принимается для многомерных массивов
	 * @return int
	 */
	function umiCount($arrayOrCountable, $isRecursive = false) {

		if (is_array($arrayOrCountable)) {
			$mode = $isRecursive ? COUNT_RECURSIVE : COUNT_NORMAL;
			return count($arrayOrCountable, $mode);
		}

		if (is_object($arrayOrCountable)) {

			if ($arrayOrCountable instanceof Countable) {
				return count($arrayOrCountable);
			}

			if ($arrayOrCountable instanceof SimpleXMLElement) {
				return $arrayOrCountable->count();
			}
		}

		if ($arrayOrCountable !== null) {
			return 1;
		}

		return 0;
	}

	/**
	 * Возвращает первое значение массива, либо переданное значение, если массив не передан
	 * @param mixed $array массив
	 * @return mixed
	 */
	function getFirstValue($array) {
		$copy = (array) $array;
		return array_shift($copy);
	}

	/**
	 * Определяет пуст ли массив
	 * @param array $array массив
	 * @return bool
	 */
	function isEmptyArray(array $array) {
		return count($array) === 0;
	}

	/**
	 * Определяет пуста ли строка
	 * @param string $string строка
	 * @return bool
	 */
	function isEmptyString($string) {
		return $string === '';
	}

	/**
	 * Склеивает многомерный массив в строку
	 * @link https://stackoverflow.com/questions/3899971/implode-and-explode-multi-dimensional-arrays/3900091#3900091
	 * @param string $glue разделитель значений
	 * @param array $array массив
	 * @return string
	 */
	function implodeRecursively($glue, array $array) {
		$result = '';

		foreach ($array as $item) {
			if (is_array($item)) {
				$result .= implodeRecursively($glue, $item) . $glue;
			} else {
				$result .= $item . $glue;
			}
		}

		return mb_substr($result, 0, 0 - mb_strlen($glue));
	}

	/**
	 * Возвращает корректный уровень качества изображения
	 * @param int $quality уровень качества изображения
	 * @return int
	 */
	function getImageQualityLevel($quality) {
		if (defined('IMAGE_COMPRESSION_LEVEL') && !is_numeric($quality)) {
			return IMAGE_COMPRESSION_LEVEL;
		}

		if ($quality < 0) {
			return 0;
		} elseif ($quality > 100) {
			return 100;
		}

		return (int) $quality;
	}

	function getRequest($key) {
		if ($key == 'p' || $key == 'per_page_limit') {
			$answer = prepareRequest($key);
			if ($answer !== false) {
				return $answer;
			}
		}
		return getArrayKey($_REQUEST, $key);
	}

	function getServer($key) {
		if ($key == 'REMOTE_ADDR' && getArrayKey($_SERVER, 'HTTP_X_REAL_IP') !== null) {
			return getArrayKey($_SERVER, 'HTTP_X_REAL_IP');
		}
		return getArrayKey($_SERVER, $key);
	}

	function getLabel($key, $path = false) {
		$args = func_get_args();
		return ulangStream::getLabel($key, $path, $args);
	}

	function getI18n($key, $pattern = '') {
		return ulangStream::getI18n($key, $pattern);
	}

	function removeDirectory($dir) {
		if (!$dh = @opendir($dir)) {
			return false;
		}
		while (($obj = readdir($dh)) !== false) {
			if ($obj == '.' || $obj == '..') {
				continue;
			}
			if (!@unlink($dir . '/' . $obj)) {
				removeDirectory($dir . '/' . $obj);
			}
		}
		@rmdir($dir);
		return true;
	}

	function array_extract_values($array, &$result = null, $ignoreVoidValues = false) {
		if (!is_array($array)) {
			return [];
		}

		if (!is_array($result)) {
			$result = [];
		}

		foreach ($array as $value) {
			if (is_array($value)) {
				array_extract_values($value, $result, $ignoreVoidValues);
			} else {
				if ($value || $ignoreVoidValues) {
					$result[] = $value;
				}
			}
		}

		return $result;
	}

	function array_unique_arrays($array, $key) {
		$result = [];
		$keys = [];

		foreach ($array as $arr) {
			$currKey = isset($arr[$key]) ? $arr[$key] : null;
			if (in_array($currKey, $keys)) {
				continue;
			}

			$keys[] = $currKey;
			$result[] = $arr;
		}

		return $result;
	}

	function array_distinct($array) {
		$result = $hashes = [];

		foreach ($array as $subArray) {
			$key = sha1(serialize($subArray));

			if (in_array($key, $hashes)) {
				continue;
			}
			$result[] = $subArray;
			$hashes[] = $key;
		}

		return $result;
	}

	function array_positive_values($arr, $recursion = true) {
		if (!is_array($arr)) {
			return [];
		}

		$result = [];
		foreach ($arr as $key => $value) {
			if ($value) {
				if (is_array($value)) {
					if ($recursion) {
						$value = array_positive_values($value, $recursion);
						if (count($value) == 0) {
							continue;
						}
					}
				}
				$result[$key] = $value;
			}
		}

		return $result;
	}

	function set_timebreak($time_end = false) {
		global $time_start;

		if (!$time_end) {
			$time_end = microtime(true);
		}
		$time = $time_end - $time_start;
		return "\r\n<!-- This page generated in {$time} secs -->\r\n";
	}

	// Thanks, Anton Timoshenkov
	function makeThumbnailFullUnsharpMask($img, $amount, $radius, $threshold) {
		if (function_exists('UnsharpMask')) {
			return UnsharpMask($img, $amount, $radius, $threshold);
		}

		// Attempt to calibrate the parameters to Photoshop:
		if ($amount > 500) {
			$amount = 500;
		}
		$amount = $amount * 0.016;
		if ($radius > 50) {
			$radius = 50;
		}
		$radius = $radius * 2;
		if ($threshold > 255) {
			$threshold = 255;
		}

		$radius = abs(round($radius));    // Only integers make sense.
		if ($radius == 0) {
			return $img;
		}
		$w = imagesx($img);
		$h = imagesy($img);
		$imgCanvas = $img;
		$imgCanvas2 = $img;
		$imgBlur = imagecreatetruecolor($w, $h);

		// Gaussian blur matrix:
		//	1	2	1
		//	2	4	2
		//	1	2	1

		// Move copies of the image around one pixel at the time and merge them with weight
		// according to the matrix. The same matrix is simply repeated for higher radii.
		for ($i = 0; $i < $radius; $i++) {
			imagecopy($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
			imagecopymerge($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
			imagecopymerge($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
			imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
			imagecopymerge($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
			imagecopymerge($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
			imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20); // up
			imagecopymerge($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
			imagecopymerge($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
		}
		$imgCanvas = $imgBlur;

		// Calculate the difference between the blurred pixels and the original
		// and set the pixels
		for ($x = 0; $x < $w; $x++) { // each row
			for ($y = 0; $y < $h; $y++) { // each pixel
				$rgbOrig = imagecolorat($imgCanvas2, $x, $y);
				$aOrig = (($rgbOrig >> 24) & 0x7F);
				$rOrig = (($rgbOrig >> 16) & 0xFF);
				$gOrig = (($rgbOrig >> 8) & 0xFF);
				$bOrig = ($rgbOrig & 0xFF);
				$rgbBlur = imagecolorat($imgCanvas, $x, $y);
				$aBlur = (($rgbBlur >> 24) & 0x7F);
				$rBlur = (($rgbBlur >> 16) & 0xFF);
				$gBlur = (($rgbBlur >> 8) & 0xFF);
				$bBlur = ($rgbBlur & 0xFF);

				// When the masked pixels differ less from the original
				// than the threshold specifies, they are set to their original value.
				$aNew = (abs($aOrig - $aBlur) >= $threshold)
					? max(0, min(127, ($amount * ($aOrig - $aBlur)) + $aOrig))
					: $aOrig;
				$rNew = (abs($rOrig - $rBlur) >= $threshold)
					? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
					: $rOrig;
				$gNew = (abs($gOrig - $gBlur) >= $threshold)
					? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
					: $gOrig;
				$bNew = (abs($bOrig - $bBlur) >= $threshold)
					? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
					: $bOrig;

				if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
					$pixCol = imagecolorallocatealpha($img, $rNew, $gNew, $bNew, $aNew);
					imagesetpixel($img, $x, $y, $pixCol);
				}
			}
		}
		return $img;
	}

	/**
	 * @deprecated
	 * @use pathinfo
	 *
	 * Обертка над стандартной функцией pathinfo
	 * TODO убрать использования этой функции из кода
	 *
	 * @param (string) $filename - полный путь
	 * @param (string|int) default=null $req_param ('dirname'|'basename'|'extension'|'filename')
	 * @return string|array or requested value
	 */
	function getPathInfo($filename, $req_param = null) {
		$info = pathinfo($filename);

		if ($req_param === null) {
			return $info;
		}

		switch ($req_param) {
			case 'dirname':
			case '1':
				return $info['dirname'];

			case 'basename':
			case '2':
				return $info['basename'];

			case 'extension':
			case '4':
				return $info['extension'];

			case 'filename':
			case '8':
				return $info['filename'];

			default:
				return $info;
		}
	}

	/**
	 * Проверяет хватает ли памяти для создания миниатюры картинки
	 * @param int $width - ширина
	 * @param int $height - высота
	 * @param bool $isTruecolor - флаг полноцветного изображения
	 * @return bool
	 */
	function checkEnoughMemoryForThumbnail($width, $height, $isTrueColor = true) {
		$rgbRate = $isTrueColor ? 3 : 1;
		$memoryRate = (float) mainConfiguration::getInstance()->get('system', 'bitmap-memory-rate');

		if ($memoryRate <= 0 || $width <= 0 || $height <= 0) {
			return true;
		}

		$needMemory = $width * $height * $rgbRate * $memoryRate;

		return $needMemory < (getThumbnailCreateMemoryLimit() - memory_get_usage());
	}

	/**
	 * Возвращает количество выделенной памяти для создания миниатюры картинки
	 * @return float
	 */
	function getThumbnailCreateMemoryLimit() {
		$sizesLetters = [
			'K' => 1,
			'M' => 2,
			'G' => 3,
		];

		$memoryLimit = ini_get('memory_limit');
		if (preg_match('/^(\d+)(.)$/', $memoryLimit, $matches)) {
			if (umiCount($matches) < 2 || !is_numeric($matches[1])) {
				return DEFAULT_THUMBNAIL_MAKE_MEMORY_LIMIT;
			}

			$limitSize = (float) $matches[1];
			if (umiCount($matches) < 3 || !$matches[2]) {
				return $limitSize;
			}

			$letter = $matches[2];
			$lettersList = array_keys($sizesLetters);
			$letterIdx = array_search($letter, $lettersList, true);
			if ($letterIdx !== false) {
				return $limitSize * pow(1024, $sizesLetters[$letter]);
			}

			return $limitSize;
		}
		return DEFAULT_THUMBNAIL_MAKE_MEMORY_LIMIT;
	}

	function makeThumbnailFull(
		$path,
		$thumbs_path,
		$width,
		$height,
		$crop = true,
		$cropside = 5,
		$isLogo = false,
		$quality = 'default'
	) {
		if ($quality === 'default') {
			$quality = IMAGE_COMPRESSION_LEVEL;
		}
		$isSharpen = true;

		$no_image_file = mainConfiguration::getInstance()->includeParam('no-image-holder');

		$image = new umiImageFile($path);
		$file_name = $image->getFileName();
		$file_ext = mb_strtolower($image->getExt());
		$file_ext = ($file_ext == 'bmp' ? 'jpg' : $file_ext);

		$allowedExts = ['gif', 'jpeg', 'jpg', 'png', 'bmp'];

		if (!in_array($file_ext, $allowedExts)) {
			return '';
		}

		$file_name = mb_substr($file_name, 0, mb_strlen($file_name) - (mb_strlen($file_ext) + 1));

		$thumbPath = sha1($image->getDirName());

		if (!is_dir($thumbs_path . $thumbPath)) {
			mkdir($thumbs_path . $thumbPath, 0755, true);
		}

		$file_name_new = $file_name . '_' . $width . '_' . $height . '_' . $image->getExt(true) . '_' . $cropside .
			'_' . $quality . '.' . $file_ext;

		$path_new = $thumbs_path . $thumbPath . '/' . $file_name_new;

		if (!file_exists($path_new) || filemtime($path_new) < filemtime($path)) {
			if (file_exists($path_new)) {
				unlink($path_new);
			}

			$width_src = $image->getWidth();
			$height_src = $image->getHeight();

			if (!checkEnoughMemoryForThumbnail($width_src, $height_src)) {
				/** @var $tooBigImageFilePath - путь до картинки с сообщением, что размер
				 * загруженного изображения слишком большой */
				$tooBigImageFilePath = mainConfiguration::getInstance()->includeParam('too-big-image-holder');

				$tooBigImageFile = new umiImageFile($tooBigImageFilePath);
				return [
					'src' => $tooBigImageFile->getFilePath(true),
					'width' => $tooBigImageFile->getWidth(),
					'heigth' => $tooBigImageFile->getHeight(),
				];
			}

			if (!($width_src && $height_src)) {
				$path = $no_image_file;
				$image = new umiImageFile($path);
				$file_name = $image->getFileName();
				$file_ext = mb_strtolower($image->getExt());
				$file_ext = ($file_ext == 'bmp' ? 'jpg' : $file_ext);
				$file_name = mb_substr($file_name, 0, mb_strlen($file_name) - (mb_strlen($file_ext) + 1));
				$thumbPath = sha1($image->getDirName());
				if (!is_dir($thumbs_path . $thumbPath)) {
					mkdir($thumbs_path . $thumbPath, 0755, true);
				}
				$file_name_new =
					$file_name . '_' . $width . '_' . $height . '_' . $cropside . '_' . $quality . '.' . $file_ext;
				$path_new = $thumbs_path . $thumbPath . '/' . $file_name_new;
				if (file_exists($path_new)) {
					unlink($path_new);
				}
				$width_src = $image->getWidth();
				$height_src = $image->getHeight();
			}

			if (!($width_src && $height_src)) {
				return '';
			}

			$real_height = $height;
			$real_width = $width;

			switch (true) {
				case ($height == 'auto' && $width == 'auto'): {
					$real_width = (int) $width_src;
					$real_height = (int) $height_src;
					break;
				}
				case ($height == 'auto'): {
					$real_height = (int) round($height_src * ($width / $width_src));
					$real_width = (int) $width;
					break;
				}
				case ($width == 'auto'): {
					$real_width = (int) round($width_src * ($height / $height_src));
					$real_height = (int) $height;
					break;
				}
				default:
					// No default
			}

			$width = $real_width;
			$height = $real_height;

			$offset_h = 0;
			$offset_w = 0;

			if (!(int) $width || !(int) $height) {
				$crop = false;
			}

			if ($crop) {
				$width_ratio = $width_src / $width;
				$height_ratio = $height_src / $height;

				if ($width_ratio > $height_ratio) {
					$offset_w = round(($width_src - $width * $height_ratio) / 2);
					$width_src = round($width * $height_ratio);
				} elseif ($width_ratio < $height_ratio) {
					$offset_h = round(($height_src - $height * $width_ratio) / 2);
					$height_src = round($height * $width_ratio);
				}

				if ($cropside) {
					//defore all it was cropside work like as - 5
					//123
					//456
					//789
					switch ($cropside):
						case 1:
							$offset_w = 0;
							$offset_h = 0;
							break;
						case 2:
							$offset_h = 0;
							break;
						case 3:
							$offset_w += $offset_w;
							$offset_h = 0;
							break;
						case 4:
							$offset_w = 0;
							break;
						case 5:
							break;
						case 6:
							$offset_w += $offset_w;
							break;
						case 7:
							$offset_w = 0;
							$offset_h += $offset_h;
							break;
						case 8:
							$offset_h += $offset_h;
							break;
						case 9:
							$offset_w += $offset_w;
							$offset_h += $offset_h;
							break;
					endswitch;
				}
			}

			try {
				$pr = imageUtils::getImageProcessor();
				$pr->cropThumbnail(
					$path,
					$path_new,
					$width,
					$height,
					$width_src,
					$height_src,
					$offset_w,
					$offset_h,
					$isSharpen,
					$quality
				);
			} catch (coreException $exception) {
				umiExceptionHandler::report($exception);
				return '';
			}

			if ($isLogo) {
				umiImageFile::addWatermark($path_new);
			}
		}

		$value = new umiImageFile($path_new);
		$info = imageUtils::getGDProcessor()->info($path_new);
		$arr = [];
		$arr['size'] = $value->getSize();
		$arr['filename'] = $value->getFileName();
		$arr['filepath'] = $value->getFilePath();
		$arr['src'] = $value->getFilePath(true);
		$arr['ext'] = $value->getExt();
		$arr['width'] = $info['width'];
		$arr['height'] = $info['height'];

		if (Service::Request()->isAdmin()) {
			$arr['src'] = str_replace('&', '&amp;', $arr['src']);
		}

		return $arr;
	}

	/**
	 * Конвертирует дату в строку
	 * @param int $timestamp дата в формате Unix timestamp
	 * @return string
	 */
	function dateToString($timestamp) {
		$monthsList = [
			getLabel('month-jan'),
			getLabel('month-feb'),
			getLabel('month-mar'),
			getLabel('month-apr'),
			getLabel('month-may'),
			getLabel('month-jun'),
			getLabel('month-jul'),
			getLabel('month-aug'),
			getLabel('month-sep'),
			getLabel('month-oct'),
			getLabel('month-nov'),
			getLabel('month-dec'),
		];

		$date = date('j.m.Y', $timestamp);
		list($day, $month, $year) = explode('.', $date);
		return implode(' ', [
			$day,
			$monthsList[(int) $month - 1],
			$year,
		]);
	}

	function sumToString(
		$i_number,
		$i_gender = 1,
		$s_w1 = 'рубль',
		$s_w2to4 = 'рубля',
		$s_w5to10 = 'рублей',
		$convertCopecks = true
	) {
		if (!$i_number) {
			return rtrim('ноль ' . $s_w5to10);
		}

		$s_answer = '';
		$v_number = $i_number;

		if (mb_strpos($i_number, '.') !== 0) {
			$i_number = number_format($i_number, 2, '.', '');
			list($v_number, $copecks) = explode('.', $i_number);
			$arr_tmp = SummaStringThree($s_answer, $copecks, 2, 'копейка', 'копейки', 'копеек', $convertCopecks);
			$s_answer = $arr_tmp['Summa'];
		}

		$arr_tmp = SummaStringThree($s_answer, $v_number, $i_gender, $s_w1, $s_w2to4, $s_w5to10);
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) {
			return $s_answer;
		}

		$arr_tmp = SummaStringThree($s_answer, $v_number, 2, 'тысяча', 'тысячи', 'тысяч');
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) {
			return $s_answer;
		}

		$arr_tmp = SummaStringThree($s_answer, $v_number, 1, 'миллион', 'миллиона', 'миллионов');
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) {
			return $s_answer;
		}

		$arr_tmp = SummaStringThree($s_answer, $v_number, 1, 'миллиард', 'миллиарда', 'миллиардов');
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) {
			return $s_answer;
		}

		$arr_tmp = SummaStringThree($s_answer, $v_number, 1, 'триллион', 'триллиона', 'триллионов');
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		return $s_answer;
	}

	function SummaStringThree($Summa, $TempValue, $Rod, $w1, $w2to4, $w5to10, $convertNumber = true) {
		$s10 = '';
		$s100 = '';

		$Rest = mb_strlen($TempValue) < 4 ? $TempValue : mb_substr($TempValue, -3);
		$newTempValue = floor($TempValue / 1000);
		if ($Rest === 0) {
			if ($Summa === '') {
				$Summa = $w5to10 . ' ';
			}
			return ['TempValue' => $newTempValue, 'Summa' => $Summa];
		}

		$EndWord = $w5to10;

		$i_i = floor($Rest / 100);
		switch ($i_i) {
			case 0:
				$s100 = '';
				break;
			case 1:
				$s100 = 'сто ';
				break;
			case 2:
				$s100 = 'двести ';
				break;
			case 3:
				$s100 = 'триста ';
				break;
			case 4:
				$s100 = 'четыреста ';
				break;
			case 5:
				$s100 = 'пятьсот ';
				break;
			case 6:
				$s100 = 'шестьсот ';
				break;
			case 7:
				$s100 = 'семьсот ';
				break;
			case 8:
				$s100 = 'восемьсот ';
				break;
			case 9:
				$s100 = 'девятьсот ';
				break;
		}
		$Rest = $Rest % 100;
		$Rest1 = (int) floor($Rest / 10);
		$s1 = '';
		switch ($Rest1) {
			case 0:
				$s10 = '';
				break;
			case 1:
				switch ($Rest) {
					case 10:
						$s10 = 'десять ';
						break;
					case 11:
						$s10 = 'одиннадцать ';
						break;
					case 12:
						$s10 = 'двенадцать ';
						break;
					case 13:
						$s10 = 'тринадцать ';
						break;
					case 14:
						$s10 = 'четырнадцать ';
						break;
					case 15:
						$s10 = 'пятнадцать ';
						break;
					case 16:
						$s10 = 'шестнадцать ';
						break;
					case 17:
						$s10 = 'семнадцать ';
						break;
					case 18:
						$s10 = 'восемнадцать ';
						break;
					case 19:
						$s10 = 'девятнадцать ';
						break;
				}
				break;
			case 2:
				$s10 = 'двадцать ';
				break;
			case 3:
				$s10 = 'тридцать ';
				break;
			case 4:
				$s10 = 'сорок ';
				break;
			case 5:
				$s10 = 'пятьдесят ';
				break;
			case 6:
				$s10 = 'шестьдесят ';
				break;
			case 7:
				$s10 = 'семьдесят ';
				break;
			case 8:
				$s10 = 'восемьдесят ';
				break;
			case 9:
				$s10 = 'девяносто ';
				break;
		}

		if ($Rest1 !== 1) {
			$i_j = $Rest % 10;
			switch ($i_j) {
				case 1:
					switch ($Rod) {
						case 1:
							$s1 = 'один ';
							break;
						case 2:
							$s1 = 'одна ';
							break;
						case 3:
							$s1 = 'одно ';
							break;
					}
					$EndWord = $w1;
					break;
				case 2:
					if ($Rod === 2) {
						$s1 = 'две ';
					} else {
						$s1 = 'два ';
					}
					$EndWord = $w2to4;
					break;
				case 3:
					$s1 = 'три ';
					$EndWord = $w2to4;
					break;
				case 4:
					$s1 = 'четыре ';
					$EndWord = $w2to4;
					break;
				case 5:
					$s1 = 'пять ';
					break;
				case 6:
					$s1 = 'шесть ';
					break;
				case 7:
					$s1 = 'семь ';
					break;
				case 8:
					$s1 = 'восемь ';
					break;
				case 9:
					$s1 = 'девять ';
					break;
			}
		}
		$stringNum = ($convertNumber === true) ? $s100 . $s10 . $s1 : $TempValue . ' ';
		$Summa = rtrim(rtrim($stringNum . $EndWord) . ' ' . $Summa);

		return ['TempValue' => $newTempValue, 'Summa' => $Summa];
	}

	function prepareRequest($key) {
		$cmsController = cmsController::getInstance();
		if (Service::Request()->isNotAdmin()) {
			return false;
		}

		$domains = getRequest('domain_id');
		$langs = getRequest('lang_id');
		$rels = getRequest('rel');
		if (!is_array($domains) || !is_array($langs)) {
			return false;
		}
		$module = $cmsController->getCurrentModule();
		$method = $cmsController->getCurrentMethod();
		if (!$module || !$method) {
			return false;
		}

		$session = Service::Session();
		$paging = $session->get('paging');
		$paging = is_array($paging) ? $paging : [];

		$domainId = $domains[0];
		if (!isset($paging[$domainId])) {
			$paging[$domainId] = [];
		}
		$langId = $langs[0];
		if (!isset($paging[$domainId][$langId])) {
			$paging[$domainId][$langId] = [];
		}
		if (!isset($paging[$domainId][$langId][$module])) {
			$paging[$domainId][$langId][$module] = [];
		}
		if (!isset($paging[$domainId][$langId][$module][$method])) {
			$paging[$domainId][$langId][$module][$method] = [];
		}
		if (!isset($paging[$domainId][$langId][$module][$method]['per_page_limit'])) {
			$paging[$domainId][$langId][$module][$method]['per_page_limit'] = 20;
		}

		if (is_array($rels) && isset($rels[0])) {
			$relId = $rels[0];
		} else {
			$relId = 0;
		}

		if (!isset($paging[$domainId][$langId][$module][$method][$relId])) {
			$paging[$domainId][$langId][$module][$method][$relId] = null;
		}

		if ($key == 'per_page_limit') {
			$perPageLimit = getArrayKey($_REQUEST, $key);
			if ($perPageLimit !== null) {
				$oldLimit = $paging[$domainId][$langId][$module][$method]['per_page_limit'];
				$paging[$domainId][$langId][$module][$method]['per_page_limit'] = $perPageLimit;
				$pageRecalcCoef = $oldLimit / $perPageLimit;

				foreach ($paging[$domainId][$langId][$module][$method] as $rel => $page) {
					if ((string) $rel == 'per_page_limit') {
						continue;
					}

					$paging[$domainId][$langId][$module][$method][$rel] = (int) ($page * $pageRecalcCoef);
				}
				if (($oldLimit != $perPageLimit) && isset($_REQUEST['p']) && $_REQUEST['p'] != 'all') {
					$_REQUEST['p'] = $paging[$domainId][$langId][$module][$method][$relId];
				}
			}

			$session->set('paging', $paging);
			return $paging[$domainId][$langId][$module][$method]['per_page_limit'];
		}

		if ($key == 'p') {
			$currentPage = getArrayKey($_REQUEST, $key);
			if ($currentPage !== null) {
				$paging[$domainId][$langId][$module][$method][$relId] = $currentPage;
			}
		}

		$session->set('paging', $paging);
		return $paging[$domainId][$langId][$module][$method][$relId];
	}

	function umi_var_dump($value, $return = false) {
		$remoteIp = getServer('HTTP_X_REAL_IP');
		if (!$remoteIp) {
			$remoteIp = getServer('REMOTE_ADDR');
		}

		$config = mainConfiguration::getInstance();
		$allowedIps = $config->get('debug', 'allowed-ip');
		$allowedIps = is_array($allowedIps) ? $allowedIps : [];

		if (in_array($remoteIp, $allowedIps)) {
			var_dump($value);
		} elseif ($return) {
			var_dump($value);
		}
	}

	/**
	 * Возвращает хеш пути до директории или файла для файлового менеджера
	 * @param string $path путь до директории или файла
	 * @return string
	 */
	function elfinder_get_hash($path) {
		$path = (string) $path;

		if ($path === '') {
			return '';
		}

		$path = str_replace('\\', '/', realpath('./' . trim($path, "./\\")));
		$auth = Service::Auth();
		$userId = $auth->getUserId();
		$user = umiObjectsCollection::getInstance()->getObject($userId);

		$source = '';
		$filemanagerDirectory = $user->getValue('filemanager_directory');

		if ($filemanagerDirectory) {
			$i = 1;
			$directories = explode(',', $filemanagerDirectory);

			foreach ($directories as $directory) {
				$directory = trim($directory);
				$directory = trim($directory, '/');

				if ($directory === '') {
					continue;
				}

				$directoryPath = realpath(CURRENT_WORKING_DIR . '/' . $directory);

				if (!contains($directoryPath, CURRENT_WORKING_DIR) || !is_dir($directoryPath)) {
					continue;
				}

				if (contains($path, $directory)) {
					$source = 'files' . $i;
					$path = trim(str_replace(CURRENT_WORKING_DIR . '/' . $directory, '', $path), '/');
					break;
				}

				$i++;
			}
		} else {
			$images_path = str_replace('\\', '/', realpath(USER_IMAGES_PATH));
			$files_path = str_replace('\\', '/', realpath(USER_FILES_PATH));

			if (startsWith($path, $images_path)) {
				$path = trim(str_replace($images_path, '', $path), '/');
				$source = 'images';
			} elseif (startsWith($path, $files_path)) {
				$path = trim(str_replace($files_path, '', $path), '/');
				$source = 'files';
			}
		}

		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);
		$hash = strtr(base64_encode($path), '+/=', '-_.');
		$hash = rtrim($hash, '.');

		/**
		 * @see:
		 *
		 * DataFileManager::FILES_HASH_PREFIX
		 * DataFileManager::IMAGES_HASH_PREFIX
		 */

		return $hash !== '' ? 'umi' . $source . '_' . $hash : 'umi' . $source;
	}

	function get_server_load() {
		if (!stristr(PHP_OS, 'win') && function_exists('sys_getloadavg')) {
			$load = sys_getloadavg();
			return getLabel('load-average') . $load[0] . ', ' . $load[1] . ', ' . $load[2];
		}
	}

	/**
	 * Создает и возвращает SimpleXMLElement.
	 * Игнорирует загрузку внешних сущностей.
	 * @param string $xml
	 * @param null $xmlElement
	 * @return SimpleXMLElement|bool SimpleXMLElement либо false
	 */
	function secure_load_simple_xml($xml, &$xmlElement = null) {
		$disableEntities = libxml_disable_entity_loader();
		$xmlElement = @simplexml_load_string($xml);
		libxml_disable_entity_loader($disableEntities);

		return $xmlElement;
	}

	/**
	 * Загружает и возвращает DOMDocument.
	 * Игнорирует загрузку внешних сущностей.
	 * @param string $xml
	 * @param DOMDocument|null $domDocument если null, будет создан новый DOMDocument
	 * @return bool результат операции
	 */
	function secure_load_dom_document($xml, &$domDocument = null) {
		$disableEntities = libxml_disable_entity_loader();

		if ($domDocument === null) {
			$domDocument = new DOMDocument('1.0', 'utf-8');
		}
		$result = @$domDocument->loadXML($xml, DOM_LOAD_OPTIONS);

		libxml_disable_entity_loader($disableEntities);
		return $result;
	}

	if (!function_exists('idn_to_utf8')) {
		/** @deprecated */
		function idn_to_utf8($host) {
			return Service::IdnConverter()->decode($host);
		}
	}

	if (!function_exists('idn_to_ascii')) {
		/** @deprecated */
		function idn_to_ascii($host) {
			return Service::IdnConverter()->encode($host);
		}
	}

	/** Возвращает разделитель слов. */
	function chooseSeparator() {
		$separator = mainConfiguration::getInstance()->get('seo', 'alt-name-separator');

		if ($separator == '_' || $separator == '-') {
			return $separator;
		}

		return '-';
	}

	/**
	 * Возвращает человекопонятное представление размера файла,
	 * например: 524288000 => 500 MB
	 * @param int $bytes байты
	 * @return string
	 */
	function bytesToString($bytes) {
		$bytes = (float) $bytes;

		$unitsBindings = [
			0 => [
				'unit' => 'TB',
				'value' => pow(1024, 4),
			],
			1 => [
				'unit' => 'GB',
				'value' => pow(1024, 3),
			],
			2 => [
				'unit' => 'MB',
				'value' => pow(1024, 2),
			],
			3 => [
				'unit' => 'KB',
				'value' => 1024,
			],
			4 => [
				'unit' => 'B',
				'value' => 1,
			],
		];

		$result = '';

		foreach ($unitsBindings as $unitBinding) {
			if ($bytes >= $unitBinding['value']) {
				$result = $bytes / $unitBinding['value'];
				$result = str_replace('.', ',', (string) round($result, 2)) . ' ' . $unitBinding['unit'];
				break;
			}
		}
		return $result;
	}

	/**
	 * Осуществляет поиск заданного значения в многомерном
	 * массиве и возвращает соответствующий ключ в случае удачи
	 * @param mixed $needle искомое значение
	 * @param array $haystack многомерный массив
	 * @return bool|int|string
	 */
	function recursive_array_search($needle, $haystack) {
		foreach ($haystack as $key => $value) {
			if ($needle === $value || (is_array($value) && recursive_array_search($needle, $value) !== false)) {
				return $key;
			}
		}
		return false;
	}

	/**
	 * Удаляет пространство имен из имени класса
	 * @param string $className
	 * @return null|string|string[]
	 * @throws ErrorException
	 */
	function trimNameSpace($className) {
		if (!is_string($className)) {
			throw new ErrorException('Class name expected');
		}

		return preg_replace('/^.*\\\/', '', $className);
	}

	/**
	 * @deprecated
	 * @use:
	 * session::getInstance()->getAndClose($key)
	 */
	function getSession($key) {
		return Service::Session()->get($key);
	}

	/**
	 * @deprecated
	 * @use:
	 * $connection = ConnectionPool::getInstance()->getConnection();
	 * $connection->queryResult();
	 */
	function l_mysql_query($sql, $no_cache = false, $className = 'core') {
		static $pool, $i = 0;
		if ($pool === null) {
			$pool = ConnectionPool::getInstance();
		}

		$conn = $pool->getConnection($className);
		return $conn->query($sql, $no_cache);
	}

	/**
	 * @deprecated
	 * @use:
	 * $connection = ConnectionPool::getInstance()->getConnection();
	 * $connection->escape();
	 */
	function l_mysql_real_escape_string($inputString, $className = 'core') {
		static $pool = null;
		if ($pool === null) {
			$pool = ConnectionPool::getInstance();
		}

		$conn = $pool->getConnection($className);
		if ($conn->isOpen()) {
			$info = $conn->getConnectionInfo();
			$link = $info['link'];
			$result = mysql_real_escape_string($inputString, $link);
		} else {
			$result = addslashes($inputString);
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @use:
	 * $connection = ConnectionPool::getInstance()->getConnection();
	 * $connection->insertId();
	 */
	function l_mysql_insert_id($className = 'core') {
		static $pool = null;

		if ($pool === null) {
			$pool = ConnectionPool::getInstance();
		}

		$connection = $pool->getConnection($className);
		$info = $connection->getConnectionInfo();
		$link = $info['link'];

		return mysql_insert_id($link);
	}

	/**
	 * @deprecated
	 * @use:
	 * $connection = ConnectionPool::getInstance()->getConnection();
	 * $connection->errorMessage();
	 */
	function l_mysql_error($className = 'core') {
		static $pool = null;

		if ($pool === null) {
			$pool = ConnectionPool::getInstance();
		}

		$connection = $pool->getConnection($className);
		$info = $connection->getConnectionInfo();
		$link = $info['link'];

		return mysql_error($link);
	}

	/**
	 * @deprecated
	 * @use:
	 * $connection = ConnectionPool::getInstance()->getConnection();
	 * $connection->affectedRows();
	 */
	function l_mysql_affected_rows($className = 'core') {
		static $pool = null;

		if ($pool === null) {
			$pool = ConnectionPool::getInstance();
		}

		$connection = $pool->getConnection($className);
		$info = $connection->getConnectionInfo();
		$link = $info['link'];

		return mysql_affected_rows($link);
	}

	/** @deprecated */
	function check_session() {
		return true;
	}

	/** @deprecated */
	function getCookie($key) {
		return Service::CookieJar()
			->get($key);
	}

	/** @Deprecated */
	function natsort2d(&$originalArray, $seekKey = 0) {
		if (!is_array($originalArray)) {
			return;
		}

		$temp = $resultArray = [];
		foreach ($originalArray as $key => $value) {
			$temp[$key] = $value[$seekKey];
		}
		natsort($temp);
		foreach ($temp as $key => $value) {
			$resultArray[] = $originalArray[$key];
		}
		$originalArray = $resultArray;
	}

	/**
	 * @deprecated
	 * @use mb_strtolower
	 */
	function wa_strtolower($str) {
		return mb_strtolower($str);
	}

	/**
	 * @deprecated
	 * @use mb_strtoupper
	 */
	function wa_strtoupper($str) {
		return mb_strtoupper($str);
	}

	/**
	 * @deprecated
	 * @use mb_substr
	 */
	function wa_substr($str, $pos, $offset) {
		return mb_substr($str, $pos, $offset);
	}

	/**
	 * @deprecated
	 * @use mb_substr
	 */
	function wa_strlen($str) {
		return mb_strlen($str);
	}

	/**
	 * @deprecated
	 * @use mb_strpos
	 */
	function wa_strpos($str, $seek) {
		return mb_strpos($str, $seek);
	}

	/**
	 * @deprecated
	 * @use bytes_strlen
	 */
	function string_bytes($string) {
		return bytes_strlen($string);
	}
