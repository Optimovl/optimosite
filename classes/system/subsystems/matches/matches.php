<?php

	use UmiCms\Service;

	/** @inheritdoc */
	class matches implements iMatches {

		/** @var string Путь до файла sitemap.xml */
		protected $sitemapFilePath;

		protected $uri;

		protected $dom;

		protected $matchNode;

		protected $buffer;

		protected $pattern;

		protected $params;

		/** @var bool|int $cache время жизни кэша в секундах */
		protected $cache = false;

		protected $externalCall = true;

		/** @inheritdoc */
		public function __construct($fileName = 'sitemap.xml') {
			$this->sitemapFilePath = $this->findSitemapFilePath($fileName);
		}

		/** @inheritdoc */
		public function setCurrentURI($uri) {
			$this->uri = (string) $uri;
		}

		/** @inheritdoc */
		public function execute($flushToBuffer = true) {
			$this->externalCall = $flushToBuffer;
			$this->loadXmlDOM();

			/** @var DOMElement $sitemapNode */
			$sitemapNode = $this->dom->firstChild;

			if (!$sitemapNode instanceof DOMElement) {
				return false;
			}

			$cache = ($sitemapNode->nodeName === 'sitemap') ? (int) $sitemapNode->getAttribute('cache') : 0;
			$this->setCacheTimeout($cache);

			$matchNode = $this->searchPattern();
			if ($matchNode) {
				return $this->beginProcessing($matchNode);
			}

			return false;
		}

		/**
		 * Определяет путь до файл с адресами по имени файла.
		 * Сначала пытается найти файл в директории шаблона дизайна.
		 * Если такого файла нет - берет файл из системной директории.
		 *
		 * @param string $fileName название файла с адресами
		 * @return string
		 * @throws publicException
		 */
		private function findSitemapFilePath($fileName) {
			$pathSuffix = "/umaps/$fileName";
			$fullPath = '';

			$resourcesDir = cmsController::getInstance()->getResourcesDirectory();
			if ($resourcesDir) {
				$fullPath = $resourcesDir . $pathSuffix;
			}

			$file = Service::FileFactory()->create($fullPath);

			if (!$file->isExists()) {
				$file->setFilePath(CURRENT_WORKING_DIR . $pathSuffix);
			}

			if (!$file->isExists()) {
				throw new publicException("Can't find sitemap file in $fullPath");
			}

			return $file->getFilePath();
		}

		/**
		 * Устанавливает время жизни кэша
		 * @param int $cache время жизни кэша в секундах
		 */
		private function setCacheTimeout($cache) {
			$cache = (int) $cache;
			$this->cache = ($cache > 0) ? $cache : false;
		}

		private function loadXmlDOM() {
			secure_load_dom_document(file_get_contents($this->sitemapFilePath), $this->dom);
		}

		/**
		 * Ищет в файле секцию "match" по запрошенному адресу.
		 * Возвращает первую подходящую по шаблону секцию или false.
		 * @return DOMElement|bool
		 */
		private function searchPattern() {
			$xpath = new DOMXPath($this->dom);
			$matchNodeList = $xpath->query('/sitemap/match');

			/** @var DOMElement $matchNode */
			foreach ($matchNodeList as $matchNode) {
				$pattern = $matchNode->getAttribute('pattern');

				if ($this->comparePattern($pattern)) {
					return $matchNode;
				}
			}

			return false;
		}

		private function comparePattern($pattern) {
			if (preg_match('|' . $pattern . '|', $this->uri, $params)) {
				$this->pattern = $pattern;
				$this->params = $params;
				return true;
			}

			return false;
		}

		private function beginProcessing(DOMElement $matchNode) {
			def_module::isXSLTResultMode(true);
			$this->processRedirection();

			$params = $this->extractParams($matchNode);
			if (isset($params['cache'])) {
				$this->cache = $params['cache'];
			}

			$this->processGeneration();
			$this->processTransformation();
			$this->processValidation();

			if ($this->externalCall) {
				$this->processSerialization();
				return true;
			}

			return $this->buffer;
		}

		/**
		 * Заменяет именнованные и порядковые параметры в строке запроса
		 * @param string $sourceUrl строка запроса
		 * @return string
		 */
		private function replaceParams($sourceUrl) {
			$replacedUrl = $this->substituteParams($this->params, $sourceUrl);
			$replacedUrl = $this->substituteParams($_GET, $replacedUrl, '', true);
			return $this->substituteParams($_SERVER, $replacedUrl, '_');
		}

		/**
		 * Заменяет параметры в строке $source значениями из массива $params
		 * @param array $params массив с данными о параметрах
		 * @param string $source строка, содержащая имена параметров
		 * @param string $prefix префикс для обозначения параметров в строке
		 * @param bool $encodeValues если true, то значения элементов массива будут закодированы
		 * @return string
		 */
		private function substituteParams($params, $source, $prefix = '', $encodeValues = false) {
			if (!is_array($params)) {
				return $source;
			}

			$result = $source;

			foreach ($params as $name => $value) {
				if (is_array($value) || is_object($value)) {
					continue;
				}

				$value = ($encodeValues ? urlencode($value) : $value);
				$result = str_replace($this->getParamSign($name, $prefix), $value, $result);
			}

			return $result;
		}

		/**
		 * Возвращает обозначение параметра
		 * @param string $paramName имя параметра
		 * @param string $prefix префикс обозначения
		 * @return string
		 */
		private function getParamSign($paramName, $prefix = '') {
			return '{' . $prefix . mb_strtolower($paramName) . '}';
		}

		private function processGeneration() {
			$xpath = new DOMXPath($this->dom);
			$nodeList = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/generate");

			if ($nodeList->length > 1) {
				throw new coreException('Only 1 generate tag allowed in match section.');
			}

			$node = $nodeList->item(0);
			$src = $this->replaceParams($node->getAttribute('src'));

			if ($this->cache !== false) {
				$data = Service::CacheFrontend()->loadSql($src);
			} else {
				$data = false;
			}

			if (!$data) {
				$data = file_get_contents($src);
				if ($this->cache !== false) {
					Service::CacheFrontend()->saveData($src, $data, $this->cache);
				}
			}

			$this->buffer = $data;
		}

		private function processTransformation() {
			$xpath = new DOMXpath($this->dom);
			$nodeList = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/transform");

			/** @var DOMElement $node */
			foreach ($nodeList as $node) {
				$src = $this->replaceParams($node->getAttribute('src'));

				if (!file_exists($src)) {
					throw new coreException("Transformation failed. File {$src} doesn't exist.");
				}

				$xsltDom = new DOMDocument('1.0', 'utf-8');
				$xsltDom->resolveExternals = true;
				$xsltDom->substituteEntities = true;
				$xsltDom->load($src, DOM_LOAD_OPTIONS);

				$xslt = new XSLTProcessor;
				$xslt->registerPHPFunctions();
				$xslt->importStylesheet($xsltDom);

				$params = $this->extractParams($node);
				foreach ($params as $name => $value) {
					$value = $this->replaceParams($value);
					$xslt->setParameter('', $name, $value);
				}

				$this->buffer = $xslt->transformToXml($this->loadBufferDom());
			}
		}

		private function processSerialization() {
			$xpath = new DOMXpath($this->dom);
			$nodeList = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/serialize");

			if ($nodeList->length === 0) {
				throw new coreException('Serializer tag required, but not found in umap rule.');
			}

			if ($nodeList->length > 1) {
				throw new coreException('Only 1 serialize tag allowed in match section.');
			}

			$node = $nodeList->item(0);
			$type = $node->getAttribute('type') ?: 'xml';
			$params = $this->extractParams($node);
			baseSerialize::serializeDocument($type, $this->buffer, $params);

			Service::Response()
				->getCurrentBuffer()
				->end();
		}

		private function processRedirection() {
			$xpath = new DOMXpath($this->dom);
			$nodeList = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/redirect");

			if ($nodeList->length === 0) {
				return false;
			}

			if ($nodeList->length > 1) {
				throw new coreException('Only 1 redirect tag allowed in match section.');
			}

			$node = $nodeList->item(0);
			$params = $this->extractParams($node);
			$status = isset($params['status']) ? $params['status'] : 301;
			$uri = $node->getAttribute('uri');

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->status($status);
			$buffer->redirect($uri);
		}

		private function processValidation() {
			$xpath = new DOMXpath($this->dom);
			$nodeList = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/validate");

			if ($nodeList->length === 0) {
				return false;
			}

			if ($nodeList->length > 1) {
				throw new coreException('Only 1 validate tag allowed in match section.');
			}

			$node = $nodeList->item(0);
			$src = $node->getAttribute('src');
			$type = $node->getAttribute('type');

			switch ($type) {
				case 'xsd': {
					if ($this->validateXmlByXsd($src)) {
						return true;
					}

					throw new coreException("Document is not valid according to xsd scheme \"{$src}\"");
					break;
				}

				case 'dtd': {
					if ($this->validateXmlByDtd($src)) {
						return true;
					}

					throw new coreException("Document is not valid according to dtd scheme \"{$src}\"");
					break;
				}

				default: {
					throw new coreException("Unknown validation method \"{$type}\"");
					break;
				}
			}
		}

		private function extractParams(DOMElement $node) {
			$params = [];

			$xpath = new DOMXpath($this->dom);
			$subnodes = $xpath->query('param', $node);

			foreach ($subnodes as $subnode) {
				$i = (string) $subnode->getAttribute('name');
				$v = (string) $subnode->getAttribute('value');

				$params[$i] = $v;

				$_subnodes = $xpath->query('param', $subnode);
				if ($_subnodes->length > 0) {
					$params[$i] = $this->extractParams($subnode);
				}
			}

			return $params;
		}

		private function validateXmlByXsd($src) {
			if (!file_exists($src)) {
				throw new coreException("Failed to validate, because xsd scheme not found \"{$src}\"");
			}

			$dom = $this->loadBufferDom();
			return $dom->schemaValidate($src);
		}

		private function validateXmlByDtd($src) {
			if (!file_exists($src)) {
				throw new coreException("Failed to validate, because dtd scheme not found \"{$src}\"");
			}

			$dom = $this->loadBufferDom();
			return $dom->validate($src);
		}

		private function loadBufferDom() {
			secure_load_dom_document($this->buffer, $dom);
			return $dom;
		}
	}
