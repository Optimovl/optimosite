<?php

	namespace UmiCms\System\Utils\Url;

	use UmiCms\System\Utils\Url;
	use UmiCms\System\Utils\iUrl;

	/**
	 * Класс фабрики url
	 * @package UmiCms\System\Url
	 */
	class Factory implements iFactory {

		/**
		 * @inheritdoc
		 * @throws UrlParseException
		 */
		public function create($url) {
			$partList = $this->parsePartList($url);
			return $this->createByPartList($partList);
		}

		/**
		 * Извлекает из адреса список составляющих
		 * @param string $url адрес
		 * @return array
		 * @throws UrlParseException
		 */
		private function parsePartList($url) {
			$partList = parse_url($url);

			if (!$partList) {
				throw new UrlParseException(getLabel('error-wrong-url'));
			}

			$partNameList = ['scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'];

			foreach ($partNameList as $name) {
				if (isset($partList[$name])) {
					continue;
				}

				$partList[$name] = '';
			}

			return $partList;
		}

		/**
		 * Создает экземпляр url из его составляющих
		 * @param array $partList список составляющих
		 * @return iUrl
		 */
		private function createByPartList(array $partList) {
			$url = new Url();
			return $url->setScheme($partList['scheme'])
				->setHost($partList['host'])
				->setPort($partList['port'])
				->setUser($partList['user'])
				->setPass($partList['pass'])
				->setPath($partList['path'])
				->setQuery($partList['query'])
				->setFragment($partList['fragment']);
		}

	}