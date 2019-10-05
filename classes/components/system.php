<?php

	use UmiCms\Service;

	/**
	 * Виртуальный модуль "system"
	 * Предоставляет реализацию макросов, которые по той или иной причине не вошли ни в один из модулей.
	 */
	class system {

		/** Максимальная глубина рекурсии при парсинге tpl-шаблонов */
		const MAX_RECURSIVE_DEPTH = 30;

		/**
		 * Предел рекурсии при парсинге tpl-шаблонов достигнут?
		 * @return bool
		 */
		protected function isRecursive() {
			static $currentRecursiveDepth = 0;
			$currentRecursiveDepth += 1;
			return ($currentRecursiveDepth > self::MAX_RECURSIVE_DEPTH);
		}

		/** Путь до уменьшенных копий изображений */
		public $thumbs_path = './images/cms/thumbs/';

		/**
		 * Строковое представление класса
		 * @return string
		 */
		public function __toString() {
			return 'umi.__system';
		}

		/**
		 * Вызвать метод на объекте этого класса
		 * @param string $method название метода
		 * @param array $args аргументы метода
		 * @return mixed
		 */
		public function cms_callMethod($method, $args) {
			return call_user_func_array([$this, $method], $args);
		}

		/**
		 * Метод существует в классе?
		 * @param string $methodName название метода
		 * @return bool
		 */
		public function isMethodExists($methodName) {
			return method_exists($this, $methodName);
		}

		/**
		 * Магический метод
		 * @param string $method название метода
		 * @param array $args аргументы метода
		 * @throws publicException
		 */
		public function __call($method, $args) {
			throw new publicException('Method ' . get_class($this) . '::' . $method . " doesn't exist");
		}

		/**
		 * Установить путь до уменьшенных копий изображений (thumbnails), вернуть предыдущее значение
		 * @param string $newPath
		 * @return string
		 */
		public function setThumbsPath($newPath) {
			$oldPath = $this->thumbs_path;
			$this->thumbs_path = $newPath;
			return $oldPath;
		}

		/**
		 * Вывести содержимое удаленного ресурса (через протокол http или umap), либо локального файла tpl-шаблона
		 * @param string $path URL удаленной страницы, либо путь до файла шаблонов (*.tpl) в локальной файловой системе
		 * @param string $sourceCharset Кодировка удаленной страницы либо локального файла шаблона
		 * @return mixed|string|umiFile
		 * @throws publicException
		 * @throws umiRemoteFileGetterException
		 */
		public function getOuterContent($path, $sourceCharset = 'UTF-8') {
			if (!$sourceCharset) {
				$sourceCharset = 'UTF-8';
			}

			if (str_replace('http://', '', $path) != $path || str_replace('umap://', '', $path) != $path) {
				$content = umiRemoteFileGetter::get($path);

				if ($sourceCharset != 'UTF-8') {
					$content = iconv($sourceCharset, 'UTF-8//IGNORE', $content);
				}

				return $content;
			}

			if (mb_substr($path, -4) != '.tpl') {
				throw new publicException(getLabel('error-resource-not-found', null, $path));
			}
			$path = preg_replace('|[\/]{2,}|', '/', $path);
			$content = file_get_contents($path);
			if ($sourceCharset != 'UTF-8') {
				$content = iconv($sourceCharset, 'UTF-8//IGNORE', $content);
			}
			$cmsController = cmsController::getInstance();
			$parseVariables = $cmsController->getGlobalVariables();
			if ($this->isRecursive()) {
				throw new publicException(getLabel('error-recursive-max-depth', null, $path));
			}
			return def_module::parseTPLMacroses($content, $cmsController->getCurrentElementId(), false, $parseVariables);
		}

		/**
		 * Вывести размер файла
		 * @param string $relativePath Путь к файлу в файловой системе, размер которого необходимо вывести
		 * @param string $unit Единицы измерения, в которых необходимо вывести размер файла
		 * @param int $precision Количество знаков после запятой
		 * @return float
		 * @throws publicException
		 */
		public function getSize($relativePath, $unit = 'B', $precision = 0) {
			if (is_numeric($relativePath)) {
				$size = $relativePath;
			} else {
				$path = CURRENT_WORKING_DIR . '/' . $relativePath;
				if (!file_exists($path)) {
					throw new publicException(getLabel('error-file-does-not-exist', null, $relativePath));
				}
				$size = filesize($path);
			}
			if (!$precision) {
				$precision = 0;
			}
			$unit = mb_strtoupper($unit);
			switch ($unit) {
				case 'K':
					return round($size / 1024, $precision);
				case 'M':
					return round($size / (1024 * 1024), $precision);
				case 'G':
					return round($size / (1024 * 1024 * 1024), $precision);
				case 'B':
				default :
					return round($size, $precision);
			}
		}

		/**
		 * Форматирует дату из формата UNIX TIMESTAMP в заданный формат
		 * @param string $timestamp Дата в формате UNIX TIMESTAMP (число или 'now')
		 * @param bool|string $format Формат даты, который необходимо получить,
		 * @see http://php.net/manual/en/function.date.php
		 * @param bool|string $timeString словесное описание даты, см. http://php.net/manual/en/function.strtotime.php
		 * @return bool|string
		 */
		public function convertDate($timestamp, $format = false, $timeString = false) {
			if ($timestamp == 'now') {
				$timestamp = time();
			}

			if ($timeString) {
				$convertedDate = strtotime($timeString);
				if ($convertedDate && $convertedDate != -1) {
					$timestamp = $convertedDate;
				}
			}

			if (!is_numeric($timestamp)) {
				return '';
			}
			if (!$format) {
				$format = DEFAULT_DATE_FORMAT;
			}
			return $timestamp ? date($format, $timestamp) : '';
		}

		/**
		 * Выводит параметр ifTrue, если condition не равно пустой строке (") и не равное нулю («0»),
		 * либо выводит параметр ifFalse в случае, если condition равно ", либо «0»
		 * @param bool $condition Условие
		 * @param string $then Параметр, который выводит макрос в том случае, если condition равен «true»
		 * @param string $else Параметр, который выводит макрос в том случае, если condition равен «false»
		 * @return string
		 */
		public function ifClause($condition, $then = '', $else = '') {
			return $condition ? $then : $else;
		}

		/**
		 * Форматирует число с разделением групп
		 * @param int $num число
		 * @return string
		 */
		public function parse_price($num = 0) {
			return number_format($num, 0, ',', ' ');
		}

		/**
		 * Выводит URI (адрес) текущей страницы
		 * @param bool $toRedirect
		 * @return bool|mixed|null
		 */
		public function getCurrentURI($toRedirect = false) {
			$from_page = getRequest('from_page');
			return ($from_page && $toRedirect) ? $from_page : getServer('REQUEST_URI');
		}

		/**
		 * Создать и вывести уменьшенное изображение (миниатюру) указанной картинки
		 * @param bool|string $path Путь к изображению-оригиналу
		 * @param string $width Ширина миниатюры в пикселях (можно указать значение «auto»)
		 * @param string $height Высота миниатюры в пикселях (можно указать значение «auto»)
		 * @param string $template Имя шаблона, по которому следует выводить миниатюру
		 * @param bool $returnArray Вернуть массив с данными о миниатюре без парсинга шаблонов
		 * @param int $flags Указывает варианты изменения размеров изображения
		 * @param int|bool $quality Указывает степень сжатия (качество) изображения
		 * @param string $alt Alt-name изображения
		 * @param string $title Title изображения
		 * @return array|string
		 * @throws coreException
		 */
		public function makeThumbnail(
			$path = false,
			$width = 'auto',
			$height = 'auto',
			$template = 'default',
			$returnArray = false,
			$flags = 0,
			$quality = false,
			$alt = "",
			$title = ""
		) {

			$template = $template ?: 'default';

			$noImageFilePath = mainConfiguration::getInstance()
				->includeParam('no-image-holder');

			$flags = (int) $flags;
			$image = new umiImageFile($path);
			$fileName = $image->getFileName();
			$fileExtension = mb_strtolower($image->getExt());
			$fileExtension = ($fileExtension == 'bmp') ? 'jpg' : $fileExtension;
			$hashedPath = sha1($image->getDirName());

			if (!is_dir($this->thumbs_path . $hashedPath)) {
				mkdir($this->thumbs_path . $hashedPath, 0755, true);
			}

			$allowedExtensions = ['gif', 'jpeg', 'jpg', 'png', 'bmp'];

			if (!in_array($fileExtension, $allowedExtensions) || $image->getIsBroken()) {
				return $this->returnEmptyThumbnailResult($returnArray);
			}

			$fileName = mb_substr($fileName, 0, mb_strlen($fileName) - (mb_strlen($fileExtension) + 1));
			$newFileName = $fileName . '_' . $width . '_' . $height . '_' . $image->getExt(true) . '.' . $fileExtension;
			$newPath = $this->thumbs_path . $hashedPath . '/' . $newFileName;

			if (!file_exists($newPath) || filemtime($newPath) < filemtime($path)) {
				if (file_exists($newPath)) {
					unlink($newPath);
				}
				$sourceWidth = $image->getWidth();
				$sourceHeight = $image->getHeight();

				if (!($sourceWidth && $sourceHeight)) {
					$path = $noImageFilePath;
					$flags = (int) $flags;
					$image = new umiImageFile($path);
					$fileName = $image->getFileName();
					$fileExtension = mb_strtolower($image->getExt());
					$fileExtension = ($fileExtension == 'bmp') ? 'jpg' : $fileExtension;
					$hashedPath = sha1($image->getDirName());

					if (!is_dir($this->thumbs_path . $hashedPath)) {
						mkdir($this->thumbs_path . $hashedPath, 0755, true);
					}

					$fileName = mb_substr($fileName, 0, mb_strlen($fileName) - (mb_strlen($fileExtension) + 1));
					$newFileName =
						$fileName . '_' . $width . '_' . $height . '_' . $image->getExt(true) . '.' . $fileExtension;
					$newPath = $this->thumbs_path . $hashedPath . '/' . $newFileName;
					if (file_exists($newPath)) {
						unlink($newPath);
					}
					$sourceWidth = $image->getWidth();
					$sourceHeight = $image->getHeight();
				}

				if (!($sourceWidth && $sourceHeight)) {
					return $this->returnEmptyThumbnailResult($returnArray);
				}

				if (!$sourceWidth) {
					return $this->returnEmptyThumbnailResult($returnArray);
				}

				if ($sourceWidth <= $width && $sourceHeight <= $height) {
					copy($path, $newPath);
				} else {
					if ($width == 'auto' && $height == 'auto') {
						$realHeight = $sourceHeight;
						$realWidth = $sourceWidth;
					} elseif ($width == 'auto' || $height == 'auto') {
						if ($height == 'auto') {
							// Flag: Reduce only
							if ($flags & 0x2 && $width > $sourceWidth) {
								$realHeight = $sourceHeight;
								$realWidth = $sourceWidth;
							} else {
								$realWidth = (int) $width;
								$realHeight = (int) round($sourceHeight * ($width / $sourceWidth));
							}
						} elseif ($width == 'auto') {
							// Flag: Reduce only
							if ($flags & 0x2 && $height > $sourceHeight) {
								$realHeight = $sourceHeight;
								$realWidth = $sourceWidth;
							} else {
								$realHeight = (int) $height;
								$realWidth = (int) round($sourceWidth * ($height / $sourceHeight));
							}
						}
					} else {
						// Flag: Keep proportions
						if ($flags & 0x1) {
							$kwidth = (float) $width / $sourceWidth;
							$kheight = (float) $height / $sourceHeight;
							$k = min([$kwidth, $kheight]);
							if (($flags & 0x2) && ($k > 1.0)) {
								$k = 1.0;
							}
							$realWidth = (int) round($sourceWidth * $k);
							$realHeight = (int) round($sourceHeight * $k);
						} else {
							$realWidth = $width;
							$realHeight = $height;
						}
					}

					try {
						$imageProcessor = imageUtils::getImageProcessor();
						$imageProcessor->thumbnail($path, $newPath, $realWidth, $realHeight, $quality);
					} catch (coreException $exception) {
						umiExceptionHandler::report($exception);
						return $this->returnEmptyThumbnailResult($returnArray);
					}
				}
			}

			$event = new umiEventPoint('makeThumbnail');
			$event->setParam('newPath', $newPath);
			$event->call();

			$value = new umiImageFile($newPath);

			$imageInfo = [
				'img_alt' => $alt,
				'img_title' => $title,
				'size' => $value->getSize(),
				'filename' => $value->getFileName(),
				'filepath' => $value->getFilePath(),
				'src' => $value->getFilePath(true),
				'ext' => $value->getExt(),
				'width' => $value->getWidth(),
				'height' => $value->getHeight(),
				'void:template' => $template,
			];

			if (Service::Request()->isAdmin()) {
				$imageInfo['src'] = str_replace('&', '&amp;', $imageInfo['src']);
			}

			if ($returnArray) {
				return $imageInfo;
			}

			list($tpl) = def_module::loadTemplates('thumbs/' . $template, 'image');
			return def_module::parseTemplate($tpl, $imageInfo);
		}

		/**
		 * Выводит список страниц при постраничном выводе
		 * @param int $total Общее количество страниц
		 * @param int $per_page Количество страниц, выводимых на одной странице
		 * @param string $template Имя шаблона, по которому следует выводить список страниц
		 * @param string $varName Имя переменной, которая будет использоваться
		 * для задания номера страницы пагинации в URL в списке страниц
		 * @param bool|int $max_pages Максимальное количество элементов в списке номеров страниц
		 * @return mixed
		 */
		public function numpages($total = 0, $per_page = 0, $template = 'default', $varName = 'p', $max_pages = false) {
			if (!$max_pages) {
				$max_pages = false;
			}
			return umiPagenum::generateNumPage($total, $per_page, $template, $varName, $max_pages);
		}

		/**
		 * Выводит ссылку для сортировки страницы каталога (или других списков,
		 * поддерживающих сортировку и фильтрацию) по указанному свойству
		 * @param string $fieldName Идентификатор свойства, по которому предполагается сортировка
		 * @param int $typeId Числовой идентификатор типа объектов, которые будут сортироваться.
		 * Как правило, макросы, которые поддерживают фильтрацию и сортировку передают его через макрос %type_id%.
		 * @param string $template Имя шаблона, по которому следует вывести ссылку
		 * @return mixed|string
		 */
		public function order_by($fieldName, $typeId, $template = 'default') {
			$from = ['%5B', '%5D'];
			$to = ['[', ']'];
			$result = umiPagenum::generateOrderBy($fieldName, $typeId, $template);
			$result = str_replace($from, $to, $result);
			return $result;
		}

		/**
		 * Выводит CAPTCHA
		 * @param string $template Имя шаблона, по которому следует вывести CAPTCHA
		 * @param string $captchaId Идентификатор CAPTCHA (для вывода нескольких CAPTCHA на одной странице)
		 * @return array|string
		 */
		public function captcha($template = 'default', $captchaId = '') {
			$template = $template ?: $this->determineCaptchaTemplate();
			return umiCaptcha::generateCaptcha($template, 'sys_captcha', '', $captchaId);
		}

		/**
		 * Возвращает название шаблона для капчи в TPL-шаблонизаторе
		 * @return string
		 * @throws coreException
		 */
		private function determineCaptchaTemplate() {
			$currentLanguage = Service::LanguageDetector()->detect();
			$languagePrefix = $currentLanguage->getPrefix();
			$isDefaultLanguage = $currentLanguage->getIsDefault();

			if ($isDefaultLanguage) {
				return 'default';
			}

			$template = "default.{$languagePrefix}";
			$file = "tpls/captcha/{$template}.tpl";

			if (!file_exists($file)) {
				$template = 'default';
			}

			return $template;
		}

		/**
		 * Обрезает строку до указанной длины, добавляя многоточие в конце
		 * @param string $string строка
		 * @param int $maxLength максимальная длина результата
		 * @return string
		 */
		public function smartSubstring($string, $maxLength = 30) {
			if (!$maxLength) {
				$maxLength = 30;
			}
			if (mb_strlen($string) > ($maxLength - 3)) {
				return mb_substr($string, 0, $maxLength - 3) . '...';
			}

			return $string;
		}

		/**
		 * Выводит адрес ссылающейся страницы (REFERER_URI)
		 * @return string
		 */
		public function referer_uri() {
			return htmlspecialchars(getServer('HTTP_REFERER'));
		}

		/**
		 * Выводит ссылку на следующую страницу
		 * @param int|string $path Id или путь страницы, относительно которой берется следующая
		 * @param string $template Шаблон, по которому выводится ссылка
		 * @param string $propName Имя свойства, по которому сортируются страницы в разделе
		 * @param int $order Направление сортировки. 0 — по убыванию, 1 — по возрастанию
		 * @return mixed
		 * @throws publicException
		 * @throws selectorException
		 */
		public function getNext($path, $template = 'default', $propName = '', $order = 0) {
			$umiHierarchy = umiHierarchy::getInstance();

			if (!$template) {
				$template = 'default';
			}

			$contentModule = cmsController::getInstance()->getModule('content');

			if (!$contentModule instanceof def_module) {
				throw new publicException(__METHOD__ . ': cant get content module');
			}

			$elementId = $contentModule->analyzeRequiredPath($path);

			if ($elementId === false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$element = umiHierarchy::getInstance()->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException('error-require-more-permissions');
			}

			$parentId = $element->getParentId();

			if ($propName) {
				$sel = new selector('pages');
				$sel->types('hierarchy-type')->id($element->getTypeId());
				$sel->where('hierarchy')->page($parentId);
				$sel->where('is_active')->equals(1);
				$sel->option('return')->value('id');

				$orderMethod = ($order === 0) ? 'desc' : 'asc';
				$sel->order($propName)->$orderMethod();

				$result = $sel->result();
				$sortArray = array_map(function ($info) {
					return (int) $info['id'];
				}, $result);
			} else {
				$sortArray = $umiHierarchy->getChildrenTree($parentId, false);
				$sortArray = array_keys($sortArray);
			}

			$nextId = false;
			$isMatched = false;

			foreach ($sortArray as $id) {
				if ($isMatched) {
					$nextId = $id;
					break;
				}

				if ($id == $elementId) {
					$isMatched = true;
				}
			}

			list($nextTpl, $nextLastTpl) = def_module::loadTemplates('content/slider/' . $template, 'next', 'next_last');

			if ($nextId !== false) {
				$blockArr = [];
				$blockArr['id'] = $nextId;
				$blockArr['link'] = $umiHierarchy->getPathById($nextId);

				return def_module::parseTemplate($nextTpl, $blockArr, $nextId);
			}

			return $nextLastTpl;
		}

		/**
		 * @param $path
		 * @param string $template
		 * @param string $propName
		 * @param int $order
		 * @return mixed|string
		 * @throws publicException
		 * @throws selectorException
		 * @throws coreException
		 */
		public function getPrevious($path, $template = 'default', $propName = '', $order = 0) {
			if (!$template) {
				$template = 'default';
			}

			$contentModule = cmsController::getInstance()->getModule('content');

			if (!$contentModule instanceof def_module) {
				throw new publicException(__METHOD__ . ': cant get content module');
			}

			$elementId = $contentModule->analyzeRequiredPath($path);

			if ($elementId === false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			$element = umiHierarchy::getInstance()->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException('error-require-more-permissions');
			}

			$parentId = $element->getParentId();

			if ($propName) {
				$sel = new selector('pages');
				$sel->types('hierarchy-type')->id($element->getTypeId());
				$sel->where('hierarchy')->page($parentId);
				$sel->where('is_active')->equals(1);
				$sel->option('return')->value('id');

				$orderMethod = ($order === 0) ? 'desc' : 'asc';
				$sel->order($propName)->$orderMethod();

				$result = $sel->result();
				$sortArray = array_map(function ($info) {
					return (int) $info['id'];
				}, $result);
			} else {
				$sortArray = umiHierarchy::getInstance()->getChildrenTree($parentId, false);
				$sortArray = array_keys($sortArray);
			}

			$prevId = false;

			foreach ($sortArray as $id) {
				if ($id == $elementId) {
					break;
				}

				$prevId = $id;
			}

			list($tpl, $tplFirst) = def_module::loadTemplates('content/slider/' . $template, 'previous', 'previous_first');

			if ($prevId !== false) {
				$blockArr = [];
				$blockArr['id'] = $prevId;
				$blockArr['link'] = umiHierarchy::getInstance()->getPathById($prevId);

				return def_module::parseTemplate($tpl, $blockArr, $prevId);
			}

			return def_module::isXSLTResultMode() ? '' : $tplFirst;
		}

		/**
		 * Выводит ошибки, которые произошли при запросе
		 * @param string $template Имя шаблона, по которому следует вывести ошибки
		 * @return mixed|string
		 * @throws Exception
		 */
		public function listErrorMessages($template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			$requestId = getRequest('_err');
			if (!$requestId) {
				return '';
			}

			$errors = Service::Session()
				->get("errors_{$requestId}");
			if (!$errors) {
				return '';
			}

			try {
				list($templateBlock, $templateBlockLine) = def_module::loadTemplates(
					"errors/{$template}",
					'errors_block',
					'errors_block_line'
				);
			} catch (publicException $e) {
				$templateBlock = '<div class="errorsBlock">%items%</div>';
				$templateBlockLine = '<div class="errorsBlockLine">%message%</div>';
			}

			$blockArr = [];
			$items = [];

			foreach ($errors as $error) {
				$lineArr = [];
				if (isset($error['code']) && $code = $error['code']) {
					$lineArr['attribute:code'] = $code;
				}
				if (isset($error['strcode']) && $strcode = $error['strcode']) {
					$lineArr['attribute:str-code'] = $strcode;
				}
				$error['message'] = def_module::parseTPLMacroses($error['message']);
				$lineArr['node:message'] = $error['message'];
				$items[] = def_module::parseTemplate($templateBlockLine, $lineArr);
			}

			$blockArr['subnodes:items'] = $items;
			return def_module::parseTemplate($templateBlock, $blockArr);
		}

		/**
		 * Возвращает список страниц указанного типа, у которых заданное свойство равно указанному значению
		 * @param int $typeId Id типа объекта, который привязан к странице(umiObjectType)
		 * @param string $propName Имя интересующего свойства
		 * @param mixed $value Значение интересующего свойства, указанного в prop_name. Проверяется точное соответствие.
		 * @param int $perPage Количество отображаемых результатов на странице
		 * @param string $template Шаблон отображения результатов
		 * @param bool $ignorePaging Игнорировать пейджинг (параметр ?p=1 в URL)
		 * @param bool|int $orderFieldId id поля, по которому должна выполняться сортировка.
		 * По умолчанию страницы будут отсортированы по id объектов, которые привязаны к страницам.
		 * @param bool $asc Порядок сортировки. Значение "1" задает прямой порядок сортировки, "0" — обратный.
		 * @return mixed
		 * @throws coreException
		 * @throws publicException
		 */
		public function getFilteredPages(
			$typeId,
			$propName,
			$value,
			$perPage = 10,
			$template = 'default',
			$ignorePaging = false,
			$orderFieldId = false,
			$asc = true
		) {
			$umiFields = umiFieldsCollection::getInstance();
			$currentPage = getRequest('p');

			if ($ignorePaging) {
				$currentPage = 0;
			}

			list($templateBlock, $templateBlockLine, $templateBlockEmpty) = def_module::loadTemplates(
				"filtered_pages/{$template}",
				'pages_block',
				'pages_block_line',
				'pages_block_empty'
			);

			$type = umiObjectTypesCollection::getInstance()->getType($typeId);

			if (!$type instanceof iUmiObjectType) {
				throw new publicException("Wrong type id \"{$typeId}\"");
			}

			$propId = $type->getFieldId($propName);

			if (!$propId) {
				throw new publicException('Type "' . $type->getName() . "\" doesn't have property \"{$propName}\"");
			}

			$sel = new selector('pages');
			$sel->types('object-type')->id($typeId);

			$field = $umiFields->getField($propId);
			$guideId = $field->getGuideId();

			if ($guideId && !is_numeric($value)) {
				$guideItems = umiObjectsCollection::getInstance()->getGuidedItems($guideId);
				$value = array_search($value, $guideItems);
			}

			$sel->where($propName)->equals($value);
			$sel->where('is_active')->equals(1);
			$sel->limit($currentPage, $perPage);

			$orderField = $umiFields->getField($orderFieldId);
			$orderMethod = $asc ? 'asc' : 'desc';

			if ($orderField) {
				$sel->order($orderField->getName())->$orderMethod();
			} else {
				$sel->order('id')->$orderMethod();
			}

			$pages = $sel->result();
			$total = $sel->length();
			$blockArr = [];

			if ($total > 0) {
				$items = [];

				foreach ($pages as $page) {
					if ($page instanceof iUmiHierarchyElement) {
						$items[] = def_module::parseTemplate($templateBlockLine, [
							'attribute:id' => $page->getId(),
							'attribute:link' => $page->link,
							'node:name' => $page->getName()
						]);
					}
				}

				$blockArr['subnodes:items'] = $items;
				$template = $templateBlock;
			} else {
				$template = $templateBlockEmpty;
			}

			$blockArr['total'] = $total;
			$blockArr['per_page'] = $perPage;

			return def_module::parseTemplate($template, $blockArr);
		}

		/**
		 * Возвращает список иерархических типов системы
		 * @return array
		 */
		public function hierarchyTypesList() {
			$items = [];
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$umiHierarchyTypesList = $umiHierarchyTypes->getTypesList();

			/* @var iUmiHierarchyType $type */
			foreach ($umiHierarchyTypesList as $type) {
				$items[$type->getId()] = $type->getTitle();
			}

			natsort($items);
			$sortedTypes = [];

			foreach ($items as $key => $value) {
				$type = $umiHierarchyTypes->getType($key);
				if ($type instanceof iUmiHierarchyType) {
					$sortedTypes[] = def_module::parseTemplate('', [
						'attribute:id' => $type->getId(),
						'attribute:module' => $type->getName(),
						'attribute:method' => $type->getExt(),
						'node:title' => $value
					]);
				}
			}

			return def_module::parseTemplate('', [
				'subnodes:items' => $sortedTypes
			]);
		}

		/**
		 * Получить список типов полей в системе (класс umiFieldType)
		 * @return mixed
		 */
		public function fieldTypesList() {
			$items = [];
			$fieldTypesList = umiFieldTypesCollection::getInstance()->getFieldTypesList();

			foreach ($fieldTypesList as $fieldType) {
				$lineArr = [
					'attribute:id' => $fieldType->getId(),
					'attribute:data-type' => $fieldType->getDataType()
				];

				if ($fieldType->getIsMultiple()) {
					$lineArr['attribute:is-multiple'] = true;
				}

				if ($fieldType->getIsUnsigned()) {
					$lineArr['attribute:is-unsigned'] = true;
				}

				$lineArr['node:name'] = $fieldType->getName();
				$items[] = def_module::parseTemplate('', $lineArr);
			}

			return def_module::parseTemplate('', [
				'subnodes:items' => $items
			]);
		}

		/**
		 * Получить список типов данных, которые можно использовать в качестве справочников
		 * @return mixed
		 */
		public function publicGuidesList() {
			$items = [];
			$guidesList = umiObjectTypesCollection::getInstance()->getGuidesList();

			foreach ($guidesList as $guideId => $guideName) {
				$items[] = def_module::parseTemplate('', [
					'attribute:id' => $guideId,
					'node:name' => $guideName
				]);
			}

			return def_module::parseTemplate('', [
				'subnodes:items' => $items
			]);
		}

		/**
		 * Получить список доступных скинов в системе
		 * @return array
		 */
		public function getSkinsList() {
			$result = [];
			$skins = [];
			$config = mainConfiguration::getInstance();
			$skinsList = $config->get('system', 'skins');
			$currentSkin = system_get_skinName();

			foreach ($skinsList as $skinName) {
				$skins[] = [
					'attribute:id' => $skinName,
					'node:name' => getLabel('skin-' . $skinName)
				];
			}

			$result['items'] = [
				'nodes:item' => $skins,
				'attribute:current' => $currentSkin
			];

			return $result;
		}

		/**
		 * Получить разделитель слов для аякс-запроса
		 * @return array
		 */
		public function getSeparator() {
			$result = [];
			$config = mainConfiguration::getInstance();
			$separator = $config->get('seo', 'alt-name-separator');
			if ($separator == '_' || $separator == '-') {
				$result['separator'] = [
					'attribute:value' => $separator
				];
				return $result;
			}

			$result['separator'] = [
				'attribute:value' => '_'
			];
			return $result;
		}

		/**
		 * Возвращает объектные типы, связанные с базовым типом и доменом
		 * @param string $module модуль базового типа
		 * @param bool|string $method метод базового типа
		 * @param bool|int $domainId идентификатор домена
		 * @return array
		 * @throws coreException
		 * @throws publicException
		 */
		public function getObjectTypesList($module, $method = false, $domainId = false) {
			$typeCollection = umiObjectTypesCollection::getInstance();
			$baseTypesCollection = umiHierarchyTypesCollection::getInstance();

			if (is_numeric($module) && !$method) {
				$type = $typeCollection->getType($module);

				if (!$type instanceof iUmiObjectType) {
					throw new publicException("Object type #{$module} doesn't exist");
				}

				$hierarchyTypeId = $type->getHierarchyTypeId();
				$baseType = $baseTypesCollection->getType($hierarchyTypeId);
			} else {
				$baseType = $baseTypesCollection->getTypeByName($module, $method);
			}

			if (!$baseType instanceof iUmiHierarchyType) {
				throw new publicException("Hierarchy type for {$module}/{$method} not found");
			}

			$typeList = [];

			foreach ($typeCollection->getListByBaseTypeAndDomain($baseType->getId(), $domainId) as $type) {
				$typeList[] = [
					'attribute:id' => $type->getId(),
					'node:name' => $type->getName()
				];
			}

			return [
				'items' => [
					'nodes:item' => $typeList
				]
			];
		}

		/**
		 * Получить список всех дочерних типов от $typeId на всю глубину наследования
		 * @param int $typeId объектный тип
		 * @return array
		 * @throws coreException
		 */
		public function getChildObjectTypesList($typeId) {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$types = $typesCollection->getChildTypeIds($typeId);
			$result = [];

			foreach ($types as $id) {
				$itemArr = [];
				$itemArr['attribute:id'] = $id;
				$itemArr['node:name'] = $typesCollection->getType($id)->getName();

				$result[] = $itemArr;
			}
			return ['items' => ['nodes:item' => $result]];
		}

		/**
		 * Получить количество всех объектов в справочнике $guideId (id типа данных)
		 * @param string $guideId
		 * @return array
		 */
		public function getGuideItemsCount($guideId) {
			$guideId = is_numeric($guideId) ? $guideId : umiObjectTypesCollection::getInstance()->getTypeIdByGUID($guideId);
			$count = umiObjectsCollection::getInstance()->getCountByTypeId($guideId);
			return ['items' => ['attribute:total' => $count]];
		}

		/**
		 * Получить список всех языков в системе
		 * @return array
		 */
		public function getLangsList() {
			$langs = Service::LanguageCollection()
				->getList();
			$blockArr = [];
			$blockArr['items'] = ['nodes:item' => $langs];
			return $blockArr;
		}

		/**
		 * Получить список доступных языковых версий
		 * @return array
		 */
		public function getInterfaceLangsList() {
			$config = mainConfiguration::getInstance();
			$interfaceLangs = $config->get('system', 'interface-langs');
			$itemsArr = [];

			foreach ($interfaceLangs as $langPrefix) {
				$itemsArr[] = [
					'attribute:prefix' => $langPrefix,
					'node:title' => getLabel('interface-lang-' . $langPrefix)
				];
			}

			$blockArr = [];
			$blockArr['items'] = [
				'attribute:current' => ulangStream::getLangPrefix(),
				'nodes:item' => $itemsArr
			];
			return $blockArr;
		}

		/**
		 * Получить список всех шаблонов дизайна для заданного домена и текущего языка
		 * @param mixed $host доменное имя
		 * @return array
		 */
		public function getTemplatesList($host = false) {
			$domainId = false;

			if ($host) {
				$domainId = Service::DomainCollection()->getDomainId($host);
			}

			if (!$domainId) {
				$domainId = Service::DomainDetector()->detectId();
			}

			$langId = Service::LanguageDetector()->detectId();
			$templates = templatesCollection::getInstance()
				->getTemplatesList($domainId, $langId);
			$items = [];

			foreach ($templates as $template) {
				$itemArr = [];
				$itemArr['attribute:id'] = $template->getId();
				$itemArr['node:name'] = $template->getTitle();
				$items[] = $itemArr;
			}

			return ['items' => ['nodes:item' => $items]];
		}

		/**
		 * Получить список всех типов полей
		 * @return array
		 */
		public function getFieldTypesList() {
			$fieldTypes = umiFieldTypesCollection::getInstance()->getFieldTypesList();
			$items = [];

			foreach ($fieldTypes as $fieldTypeId => $fieldType) {
				$itemArr = [];
				$itemArr['attribute:id'] = $fieldTypeId;
				$itemArr['node:name'] = $fieldType->getName();
				$items[] = $itemArr;
			}

			$blockArr = [];
			$blockArr['nodes:item'] = $items;
			return $blockArr;
		}

		/**
		 * Закодировать/раскодировать строку в формате base64
		 * @param string $mode режим ('encode' или 'decode')
		 * @param string $string строка
		 * @return string
		 * @throws publicException
		 */
		public function base64($mode, $string) {
			switch ($mode) {
				case 'encode': {
					return base64_encode($string);
				}

				case 'decode': {
					return base64_decode($string);
				}

				default: {
					throw new publicException("Don't know, how to do base64 \"{$mode}\". Type \"encode\" or \"decode\".");
				}
			}
		}

		/**
		 * Возвращает имя родительского каталога из указанного пути
		 * @param string $mode режим ('dirname')
		 * @param string $file путь до файла
		 * @return string
		 * @throws publicException
		 */
		public function fs($mode, $file) {
			switch ($mode) {
				case 'dirname': {
					return dirname($file);
				}

				default: {
					throw new publicException("Don't know, how to do fs \"{$mode}\".");
				}
			}
		}

		/**
		 * Возвращает данные для вывода хлебных крошек в админке
		 * @param bool $elementId идентификатор текущей страницы
		 * @return array
		 * @throws publicException
		 */
		public function getSubNavibar($elementId = false) {
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$module = $cmsController->getCurrentModule();
			$method = $cmsController->getCurrentMethod();

			$result = [];
			$result['module'] = [
				'attribute:label' => getLabel('module-' . $module),
				'node:name' => $module
			];

			if ($elementId) {
				$parents = $hierarchy->getAllParents($elementId, true);
				$pagesArr = [];

				foreach ($parents as $parentId) {
					if ($parentId == 0) {
						continue;
					}
					$element = $hierarchy->getElement($parentId);
					if ($element instanceof iUmiHierarchyElement) {
						$pageArr = [];
						$pageArr['attribute:id'] = $parentId;
						$pageArr['xlink:href'] = 'upage://' . $parentId;
						$pageArr['attribute:name'] = $element->getName();
						$pageArr['attribute:edit-link'] = $this->getEditLink($parentId);
						$pagesArr[] = $pageArr;
					}
				}

				if (umiCount($pagesArr)) {
					$result['parents'] = ['nodes:page' => $pagesArr];
				}
			}

			if ($method) {
				$label = 'header-' . $module . '-' . $method;

				if ($cmsController->headerLabel) {
					$label = $cmsController->headerLabel;
				}

				$result['method'] = [
					'attribute:label' => getLabel($label),
					'node:name' => $method
				];
			}

			return $result;
		}

		/**
		 * Получить ссылку на редактирование запрошенной страницы
		 * @param $elementId
		 * @return mixed
		 * @throws publicException
		 */
		public function getEditLink($elementId) {
			$element = umiHierarchy::getInstance()->getElement($elementId);
			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException("Element #{$elementId} doesn't exist.");
			}

			$moduleName = $element->getModule();
			$methodName = $element->getMethod();

			/** @var CatalogAdmin|UsersAdmin|NewsAdmin|FaqAdmin|ForumAdmin|EmarketAdmin|Blogs20Admin|ForumAdmin|def_module $module */
			$module = cmsController::getInstance()->getModule($moduleName);
			if (!$module instanceof def_module) {
				throw new publicException("Module \"{$moduleName}\" not found. So I can't get edit link for element #{$elementId}");
			}

			$link = $module->getEditLink($elementId, $methodName);
			if (isset($link[1])) {
				return $link[1];
			}
		}

		/**
		 * Получить имя объекта по его Id
		 * @param int $objectId идентификатор объекта
		 * @return string
		 * @throws publicException
		 */
		public function getObjectName($objectId) {
			$object = umiObjectsCollection::getInstance()
				->getObject($objectId);

			if ($object instanceof iUmiObject) {
				return $object->getName();
			}

			throw new publicException(getLabel('error-object-does-not-exist', null, $objectId));
		}

		/**
		 * Создает и выводит уменьшенное изображение (миниатюру) указанной картинки с возможностью обрезки
		 * @param string $path Путь к изображению-оригиналу
		 * @param int $width Ширина миниатюры в пикселях (можно указать значение «auto»)
		 * @param int $height Высота миниатюры в пикселях (можно указать значение «auto»)
		 * @param string $template Имя шаблона, по которому следует выводить миниатюру
		 * @param bool $returnArray Вернуть массив с данными о миниатюре без парсинга шаблонов
		 * @param bool $crop Обрезать или не обрезать миниатюру
		 * @param int $cropSide Указывает положение рамки обрезания
		 * @param bool $isLogo Указывает необходимость наложения водяного знака на изображение
		 * @param int $quality Указывает степень сжатия (качество) изображения
		 * @param string $alt Alt-name изображения
		 * @param string $title Title изображения
		 * @return array|string
		 * @throws coreException
		 */
		public function makeThumbnailFull(
			$path,
			$width,
			$height,
			$template = 'default',
			$returnArray = false,
			$crop = true,
			$cropSide = 5,
			$isLogo = false,
			$quality = 80,
			$alt = "",
			$title = ""
		) {

			$result = makeThumbnailFull($path, $this->thumbs_path, $width, $height, $crop, $cropSide, $isLogo, $quality);

			if (!is_array($result)) {
				return $this->returnEmptyThumbnailResult($returnArray);
			}

			$event = new umiEventPoint('makeThumbnailFull');
			$event->setParam('result', $result);
			$event->call();

			if (Service::Request()->isAdmin() && isset($result['src'])) {
				$result['src'] = str_replace('&', '&amp;', $result['src']);
			}

			$result['void:template'] = $template;
			$result['img_alt'] = $alt;
			$result['img_title'] = $title;

			if ($returnArray) {
				return $result;
			}

			list($tpl) = def_module::loadTemplates('thumbs/' . $template, 'image');
			return def_module::parseTemplate($tpl, $result);
		}

		/**
		 * Возвращает пустой результат генерации миниатюры
		 * @param bool $returnArray флаг необходимости вернуть массив
		 * @return array|string
		 */
		private function returnEmptyThumbnailResult($returnArray) {
			return $returnArray ? [] : '';
		}

		/**
		 * Получить значение настройки из реестра для указанного модуля
		 * @param string $moduleName модуль
		 * @param string $settingName настройка
		 * @return string|null|bool
		 * @throws publicException
		 */
		public function getModuleSetting($moduleName, $settingName) {

			if (Service::Request()->isNotAdmin()) {
				throw new publicException('Sorry, but you are not allowed to use this method here');
			}

			$module = cmsController::getInstance()
				->getModule($moduleName);

			if ($module instanceof def_module) {
				$settingValue = Service::Registry()
					->get('//modules/' . get_class($module) . '/' . $settingName);
				return $settingValue ?: null;
			}

			return false;
		}

		/**
		 * Выводит код для сбора статистики Google Analytics
		 * @return string
		 */
		public function googleAnalyticsCode() {
			$regedit = Service::Registry();
			$domainId = Service::DomainDetector()->detectId();
			$googleAnalyticsId = $regedit->get("//settings/ga-id/{$domainId}");

			if ($googleAnalyticsId) {
				return <<<END
<script>

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '{$googleAnalyticsId}']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
END;
			}

			return '';
		}

		/**
		 * @param $moduleName
		 * @param $methodName
		 * @return mixed|string
		 */
		public function get_module_tabs($moduleName, $methodName) {
			$cmsController = cmsController::getInstance();
			$module = $cmsController->getModule($moduleName);
			if (!$module instanceof def_module) {
				return '';
			}

			$commonTabs = $module->getCommonTabs();
			$configTabs = $module->getConfigTabs();
			$affectedTabs = null;
			$currentTab = false;

			if ($commonTabs && $currentTab = $commonTabs->getTabNameByAlias($methodName)) {
				$affectedTabs = $commonTabs;
			} elseif ($configTabs && $currentTab = $configTabs->getTabNameByAlias($methodName)) {
				$affectedTabs = $configTabs;
			}

			if (!$affectedTabs instanceof adminModuleTabs) {
				return '';
			}

			$langPrefix = $cmsController->getPreLang();
			$items = [];

			foreach ($affectedTabs->getAll() as $tabName => $methodAliases) {

				if (!system_is_allowed($moduleName, $tabName)) {
					continue;
				}

				$labelSuffix = "{$moduleName}-{$tabName}";
				$tabsLabel = getLabel("tabs-{$labelSuffix}");

				if ($tabsLabel != "tabs-{$labelSuffix}") {
					$label = $tabsLabel;
				} else {
					$label = getLabel("header-{$labelSuffix}");
				}

				$itemArr = [];
				$itemArr['attribute:name'] = $tabName;
				$itemArr['attribute:label'] = $label;
				$itemArr['attribute:link'] = "{$langPrefix}/admin/{$moduleName}/{$tabName}/";
				$aliases = [];

				if (is_array($methodAliases)) {
					foreach ($methodAliases as $alias) {
						$aliases[] = def_module::parseTemplate('', ['attribute:name' => $alias]);
					}
				}

				if ($tabName == $currentTab) {
					$itemArr['attribute:active'] = 1;
				}
				$itemArr['subnodes:aliases'] = $aliases;
				$items[] = def_module::parseTemplate('', $itemArr);
			}

			return def_module::parseTemplate('', [
				'subnodes:items' => $items
			]);
		}

		/**
		 * Получить таблицу вида
		 * <letter count="1" link="/shop/?fields_filter[name][like]=a%">a</letter>
		 * <letter count="1" link="/shop/?fields_filter[name][like]=a%">a</letter>
		 * ...
		 * для страниц на сайте
		 * @param mixed $elementId идентификатор родительской страницы
		 * @param int $depth глубина поиска страниц
		 * @param string $template шаблон обработки результата на TPL-шаблонизаторе
		 * @param string $pattern нужные буквы и цифры (регулярное выражение вида "a-k1-5)
		 * @return mixed
		 * @throws publicException
		 * @throws selectorException
		 */
		public function alphabeticalIndex($elementId = false, $depth = 1, $template = 'default', $pattern = 'а-яa-z0-9') {
			list($tplBlock, $tplLetter, $tplLetterActive) = def_module::loadTemplates(
				"alphabetical-index/{$template}",
				'block',
				'block_item',
				'block_item_a'
			);

			$contentModule = cmsController::getInstance()->getModule('content');
			if (!$contentModule instanceof def_module) {
				throw new publicException(__METHOD__ . ': cant get content module');
			}

			$elementId = $contentModule->analyzeRequiredPath($elementId);
			$pages = new selector('pages');

			if ($elementId) {
				$pages->where('hierarchy')->page($elementId)->childs($depth);
			}

			$index = new alphabeticalIndex($pages);
			$letters = $index->index($pattern);

			$baseLink = null;
			$element = selector::get('page')->id($elementId);
			$baseLink = $element->link;

			$itemsArr = [];
			foreach ($letters as $letter => $count) {
				$templateItem = $count ? $tplLetterActive : $tplLetter;

				$link = $count ? "{$baseLink}?fields_filter[name][like]={$letter}%" : null;
				$itemsArr[] = def_module::parseTemplate($templateItem, [
					'@count' => $count,
					'@link' => $link,
					'#letter' => (string) $letter
				]);
			}

			$blockArr = [
				'nodes:letter' => $itemsArr
			];
			return def_module::parseTemplate($tplBlock, $blockArr);
		}

		/**
		 * Вывести календарную таблицу для страницы
		 * @param int $elementId идентификатор страницы
		 * @param string $fieldName строковой идентификатор поля
		 * @param bool $year год
		 * @param bool $month месяц
		 * @param int $depth глубина поиска
		 * @param string $template шаблон, по которому выводится результат (только для tpl)
		 * @return mixed
		 * @throws publicException
		 * @throws selectorException
		 */
		public function calendarIndex(
			$elementId,
			$fieldName,
			$year = false,
			$month = false,
			$depth = 1,
			$template = 'default'
		) {
			list($tplBlock, $tplWeek, $tplDay, $tplDayActive, $tplDayNull) = def_module::loadTemplates(
				'./tpls/calendar/' . $template,
				'calendar',
				'week',
				'day',
				'day_a',
				'day_null'
			);

			$contentModule = cmsController::getInstance()->getModule('content');

			if (!$contentModule instanceof def_module) {
				throw new publicException(__METHOD__ . ': cant get content module');
			}

			$elementId = $contentModule->analyzeRequiredPath($elementId);

			if (!$elementId) {
				throw new publicException("Page #{$elementId} not found");
			}

			$hierarchy = umiHierarchy::getInstance();
			$objectTypeId = $hierarchy->getDominantTypeId($elementId);
			if (!$objectTypeId) {
				return;
			}

			$pages = new selector('pages');
			$pages->types('object-type')->id($objectTypeId);
			$pages->where('hierarchy')->page($elementId)->childs($depth);

			try {
				$index = new calendarIndex($pages);
				$calendar = $index->index($fieldName, $year, $month);
			} catch (baseException $e) {
				throw new publicException($e->getMessage());
			}

			$weeks = [];
			$weeksCount = ceil((umiCount($calendar['days']) + $calendar['first-day']) / 7);
			$baseLink = null;
			$element = selector::get('page')->id($elementId);

			if ($element) {
				$baseLink = $element->link;
			}

			$fromTs = $index->timeStart;
			for ($i = 0; $i < $weeksCount; $i++) {
				$days = [];

				for ($j = 0; $j < 7; $j++) {
					$number = $i * 7 + $j - $calendar['first-day'] + 1;
					if ($number > umiCount($calendar['days']) || $number <= 0) {
						$number = false;
						$tpl = $tplDayNull;
						$count = 0;
					} else {
						$count = (int) $calendar['days'][$number];
						$tpl = $count ? $tplDayActive : $tplDay;
					}

					$link = null;
					if ($count) {
						$t1 = $fromTs + 3600 * 24 * ($number - 1);
						$t2 = $t1 + 3600 * 24;
						$link = "{$baseLink}?fields_filter[{$fieldName}][]={$t1}&fields_filter[{$fieldName}][]={$t2}";
					}

					$days[] = def_module::parseTemplate($tpl, [
						'@count' => $count,
						'@link' => $link,
						'#day' => $number

					]);
				}

				$week = [
					'void:days' => $days,
					'nodes:day' => $days
				];
				$weeks[] = def_module::parseTemplate($tplWeek, $week);
			}

			return def_module::parseTemplate($tplBlock, [
				'date' => $index->timeStart,
				'year' => $calendar['year'],
				'month' => $calendar['month'],
				'void:weeks' => $weeks,
				'nodes:week' => $weeks
			]);
		}

		/**
		 * Определить id типа данных, которому принадлежат больше всего страниц под $elementId
		 * @param int $elementId идентификатор страницы
		 * @return int
		 */
		public function getDominantTypeId($elementId = 0) {
			$hierarchy = umiHierarchy::getInstance();
			return $hierarchy->getDominantTypeId((int) $elementId);
		}

		/**
		 * Вывести видеоплеер
		 * @return mixed|null
		 * @throws publicException
		 */
		public function getVideoPlayer() {
			$width = 640;
			$height = 360;
			$template = 'default';
			$arguments = func_get_args();
			if (!umiCount($arguments)) {
				throw new publicException('No video specified');
			}

			if (umiCount($arguments) > 1 && (string) ((int) $arguments[0]) === $arguments[0]) {
				$entityId = (int) $arguments[0];
				$fieldName = $arguments[1];
				$indexBase = 2;
				$entity = umiHierarchy::getInstance()->getElement($entityId);
				if (!$entity) {
					$entity = umiObjectsCollection::getInstance()->getObject($entityId);
					if (!$entity) {
						throw new publicException("Entity {$entityId} doesn't exist");
					}
				}

				$path = (string) $entity->getValue($fieldName);
			} else {
				$path = $arguments[0];
				$indexBase = 1;
			}

			if (umiCount($arguments) > $indexBase) {
				$width = (int) $arguments[$indexBase];
			}
			if (umiCount($arguments) > $indexBase + 1) {
				$height = (int) $arguments[$indexBase + 1];
			}
			if (umiCount($arguments) > $indexBase + 2) {
				$template = (int) $arguments[$indexBase + 2];
			}
			if (umiCount($arguments) > $indexBase + 3) {
				$autoload = (string) $arguments[$indexBase + 3];
			} else {
				$autoload = 'false';
			}

			list($template) = def_module::loadTemplates('video/' . $template, 'video_player');
			return def_module::parseTemplate($template, [
				'path' => $path,
				'width' => $width,
				'height' => $height,
				'autoload' => $autoload
			]);
		}

		/**
		 * Проверяет лицензионный ключ и возвращает форму запроса в Службу Заботы
		 * при нажатии на кнопку "Обратиться за помощью" в административной панели.
		 * @return mixed
		 */
		public function checkLicenseKey() {
			if (!permissionsCollection::getInstance()->isAdmin()) {
				return false;
			}

			$keyCode = Service::RegistrySettings()->getLicense();
			$response = umiRemoteFileGetter::get("https://www.umi-cms.ru/udata/updatesrv/checkLicenseKey/{$keyCode}/");

			$dom = new DOMDocument();
			if (!@$dom->loadXML($response)) {
				return def_module::parseTemplate('', ['error' => getLabel('error-invalid_answer')]);
			}

			$udata = $dom->getElementsByTagName('udata')->item(0);
			$nodeList = $udata->childNodes;
			$error = false;
			$variables = [];

			/** @var DOMElement $node */
			foreach ($nodeList as $node) {
				$name = $node->nodeName;
				if ($name == 'error') {
					$error = true;
				}

				$variables[$name] = html_entity_decode($node->nodeValue);
			}

			if ($error) {
				return def_module::parseTemplate('', $variables);
			}

			$userId = Service::Auth()->getUserId();
			$user = umiObjectsCollection::getInstance()->getObject($userId);

			$variables['user'] = [
				'attribute:domain' => Service::DomainDetector()->detectHost(),
				'attribute:name' => $user->getValue('fname'),
				'attribute:email' => $user->getValue('e-mail')
			];

			/** @var core $core */
			$core = system_buildin_load('core');
			$variables += $core->getDomainsList();

			return def_module::parseTemplate('', $variables);
		}

		/**
		 * Отправляет запрос в Службу Заботы
		 * @return mixed
		 */
		public function sendSupportRequest() {
			if (isDemoMode()) {
				return def_module::parseTemplate('', [
					'success' => getLabel('no-ask-support-in-demo-mode')
				]);
			}

			$post = $_POST;
			$files = Service::Request()
				->Files()
				->get('data');
			$fileError = isset($files['error']['attach_file']) ? $files['error']['attach_file'] : '';

			if ($fileError === UPLOAD_ERR_OK) {
				$filename = basename($files['name']['attach_file']);
				$type = basename($files['type']['attach_file']);
				$filePath = realpath($files['tmp_name']['attach_file']);

				$post['data'] = array_merge(
					$post['data'],
					['attach_file' => curl_file_create($filePath, $type, $filename)]
				);
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://www.umi-cms.ru/webforms/post_support/?ajax=1');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildPostFields($post));

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response = curl_exec($ch);

			$dom = new DOMDocument();

			if (@$dom->loadXML($response)) {
				$node = $dom->documentElement;
				$variables = [$node->nodeName => html_entity_decode($node->nodeValue)];
			} else {
				$variables = ['error' => getLabel('error-invalid_answer')];
			}

			return def_module::parseTemplate('', $variables);
		}

		/**
		 *  Метод возвращает информацию о системе.
		 *  Доступен для использования только в админке.
		 * @param int options модификаторы для вывода различных блоков
		 * @return array массив с общей информацией о системе
		 *  'system' - информация о системе. Требуется указать опцию SYSTEM.
		 *   - 'version' - версия системы
		 *   - 'revision' - сборка системы
		 *   - 'license' - редакция системы
		 * @example $sysInfo = $system->info();
		 * @throws Exception
		 */
		public function info() {
			if (Service::Request()->isNotAdmin()) {
				return [];
			}

			return Service::SystemInfo()
				->getInfo(iSystemInfo::SYSTEM);
		}

		/**
		 * Возвращает версию визуального редактора tinyMCE для административной панели
		 * @return string
		 */
		public function getAdminWysiwygVersion() {
			$version = (string) mainConfiguration::getInstance()->get('system', 'admin-wysiwyg-version');
			return $version ?: 'tinymce47';
		}

		/** @deprecated используйте php-функции is_numeric и is_int */
		public function is_int($number) {
			return is_numeric($number);
		}

		/** @deprecated */
		public function bool2str($arg) {
			return $arg ? 'true' : 'false';
		}

		/** @deprecated */
		public function fileExists($arg) {
			return file_exists(ini_get('include_path') . $arg);
		}

		/** @deprecated */
		public function isSubscribedOnChanges($elementId) {
			if (!$elementId) {
				return '';
			}
			$checked = ' checked';

			$objects = umiObjectsCollection::getInstance();

			$userId = Service::Auth()
				->getUserId();
			$user = $objects->getObject($userId);
			if (!$user instanceof iUmiObject) {
				return false;
			}
			foreach ($user->subscribed_pages as $page) {
				if ($page instanceof iUmiHierarchyElement) {
					if ($page->id == $elementId) {
						return $checked;
					}
				} else {
					if ($page == $elementId) {
						return $checked;
					}
				}
			}
		}

		/** @deprecated */
		private function getImageProps($filePath, $expect = 'width') {
			$filePath = ini_get('include_path') . $filePath;
			if (!file_exists($filePath)) {
				return false;
			}

			list($width, $height) = getimagesize($filePath);

			if ($expect == 'width') {
				return $width;
			}
			if ($expect == 'height') {
				return $height;
			}
		}

		/** @deprecated */
		public function getImageWidth($filePath) {
			return $this->getImageProps($filePath, 'width');
		}

		/** @deprecated */
		public function getImageHeight($filePath) {
			return $this->getImageProps($filePath, 'height');
		}

		/** @deprecated */
		public function uri_path_pic() {
			list($res) = explode('/', $_REQUEST['path']);

			$allowed_res = ['about', 'portfolio', 'sites', 'promotion', 'multimedia', 'own_projects', 'contacts'];
			if (!in_array($res, $allowed_res)) {
				$res = array_pop($allowed_res);
			}
			return $res;
		}

		/**
		 * @deprecated
		 * @see ContentMacros::includeFrontendResources()
		 * @return string
		 */
		public function includeQuickEditJs() {
			/** @var ContentMacros $content */
			$content = cmsController::getInstance()->getModule('content');
			return $content->includeFrontendResources();
		}

		/**
		 * Строит запрос для отправки многомерных массивов и CURLFiles
		 *  `curl_setopt($ch, CURLOPT_POSTFIELDS, buildPostFields($postfields));`
		 *
		 * @param array $data данные
		 * @param string $existingKeys имя ключа
		 * @param array $returnArray данные с которых начнется создание запроса
		 * @return array
		 * @link https://gist.github.com/yisraeldov/ec29d520062575c204be7ab71d3ecd2f
		 */
		private function buildPostFields($data, $existingKeys = '', &$returnArray = []) {
			if (($data instanceof CURLFile) || !(is_array($data) || is_object($data))){
				$returnArray[$existingKeys] = $data;

				return $returnArray;
			}

			foreach ($data as $key => $item) {
				$this->buildPostFields($item, $existingKeys ? $existingKeys . "[$key]" : $key, $returnArray);
			}

			return $returnArray;
		}
	}
