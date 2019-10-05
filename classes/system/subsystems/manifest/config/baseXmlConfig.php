<?php

	/** Класс менеджера конфигурации в xml файле */
	class baseXmlConfig implements iBaseXmlConfig {

		/** @var string $configFileName путь до xml файла */
		protected $configFileName;

		/** @var DOMDocument|null $dom DOMDocument из xml файла */
		protected $dom;

		/** @var DOMXPath|null $xpath DOMXPath для запросов к DOMDocument */
		protected $xpath;

		/** @inheritdoc */
		public function __construct($configFileName) {
			$this->configFileName = (string) $configFileName;
			$this->dom = $this->loadDOM($this->configFileName);
			$this->xpath = new DOMXPath($this->dom);
		}

		/** @inheritdoc */
		public function getName() {
			return getPathInfo($this->configFileName, 'filename');
		}

		/** @inheritdoc */
		public function getValue($xpath) {
			$nodes = $this->executeXPath($xpath);

			switch ($nodes->length) {
				case 0 : {
					return null;
				}
				case 1: {
					$node = $nodes->item(0);
					return (string) $node->nodeValue;
				}
				default: {
					$format = 'Parsing getValue "%s" xpath failed. More than 1 result in "%s".';
					$message = sprintf($format, $xpath, $this->configFileName);
					throw new Exception($message);
				}
			}
		}

		/** @inheritdoc */
		public function getList($xpath, array $attributes = []) {
			$nodes = $this->executeXPath($xpath);

			if ($nodes->length === 0) {
				return [];
			}

			$result = [];
			$needAttributes = !empty($attributes);

			for ($i = 0; $i < $nodes->length; $i++) {
				$node = $nodes->item($i);

				if (!$needAttributes) {
					$result[$i] = $node->nodeValue;
					continue;
				}

				$result[$i] = $this->getNodeProperties($node, $attributes);
			}

			return $result;
		}

		/**
		 * Возвращает данные, связанные с нодой (атрибуты, значение, значения дочерних нод)
		 * @param DOMElement $node
		 * @param array $attributes см. iBaseXmlConfig::getList()
		 * @return array
		 */
		protected function getNodeProperties(DOMElement $node, array $attributes) {
			$attributeValueList = [];

			foreach ($attributes as $name => $seek) {
				switch (true) {
					case ($seek === '+params') : {
						$attributeValue = $this->extractParams($node);
						break;
					}
					case ($seek === '.') : {
						$attributeValue = $node->nodeValue;
						break;
					}
					case (startsWith($seek, '/')) : {
						$subNodes = $this->xpath->evaluate(mb_substr($seek, 1), $node);
						$attributeValue = ($subNodes->length == 0) ? null : $subNodes->item(0)->nodeValue;
						break;
					}
					case (startsWith($seek, '@')) : {
						$key = mb_substr($seek, 1);
						$attributeValue = $node->hasAttribute($key) ? (string) $node->getAttribute($key) : null;
						break;
					}
					default : {
						$attributeValue = null;
					}
				}

				$attributeValueList[$name] = $attributeValue;
			}

			return $attributeValueList;
		}

		/**
		 * Формирует DOMDocument из файла конфигурации
		 * @param string $configFileName путь до файла
		 * @return DOMDocument
		 * @throws Exception
		 */
		protected function loadDOM($configFileName) {
			if (!file_exists($configFileName)) {
				throw new Exception("Can't find config file \"{$configFileName}\"");
			}

			if (!secure_load_dom_document(file_get_contents($configFileName), $dom)) {
				throw new Exception("Can't parse xml config \"{$configFileName}\"");
			}

			return $dom;
		}

		/**
		 * Выполняет xpath запрос к файлу конфигурации
		 * @param string $xpath
		 * @return DOMNodeList
		 */
		protected function executeXPath($xpath) {
			return $this->xpath->query((string) $xpath);
		}

		/**
		 * Формирует массив параметров со значениями из узла
		 *
		 * @param DOMElement $node узел
		 *
		 * <action name="action">
		 *      <param name="foo" value="bar" />
		 *      <param name="baz" value="foo" />
		 * </action>
		 *
		 * @return array
		 *
		 * [
		 *      foo => bar,
		 *      baz => foo
		 * ]
		 */
		protected function extractParams(DOMElement $node) {
			$result = [];

			/** @var DOMNodeList $nodeParamList */
			$nodeParamList = $this->xpath->query('param', $node);

			/** @var DOMElement $nodeParam */
			foreach ($nodeParamList as $nodeParam) {
				$name = (string) $nodeParam->getAttribute('name');
				$value = (string) $nodeParam->getAttribute('value');

				$subeNodeParamList = $this->xpath->query('param', $nodeParam);

				if ($subeNodeParamList->length > 0) {
					$value = $this->extractParams($nodeParam);
				}

				$result[$name] = $value;
			}

			return $result;
		}
	}
