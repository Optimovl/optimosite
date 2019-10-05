<?php

	class RSSFeed implements iRSSFeed {

		private $url,
			$xml,
			$items;

		public function __construct($url) {
			$this->url = $url;
		}

		public function loadContent($charset = false) {
			$cont = umiRemoteFileGetter::get($this->url, false, false, false, true);

			$contentArray = explode("\r\n\r\n", $cont);
			$headers = $contentArray[0];
			unset($contentArray[0]);
			$cont = implode("\r\n\r\n", $contentArray);

			if (mb_strpos(mb_strtolower($headers), 'content-encoding: gzip')) {
				$cont = gzinflate(mb_substr($cont, 10));
			}

			if (!$cont) {
				throw new publicAdminException(getLabel('label-feed-can-not-load-content', false, $this->url));
			}

			set_error_handler([&$this, 'error_handler']);

			if ($charset) {
				$cont = iconv(mb_strtoupper($charset), 'UTF-8//IGNORE', $cont);
				$cont = preg_replace("/(encoding=\"{$charset}\")/i", 'encoding="UTF-8"', $cont);
			} elseif (function_exists('mb_detect_encoding')) {
				if (mb_detect_encoding($cont, 'UTF-8, ISO-8859-1, GBK, CP1251') != 'UTF-8') {
					$cont = iconv('CP1251', 'UTF-8//IGNORE', $cont);
					$cont = preg_replace('/(encoding="windows-1251")/i', 'encoding="UTF-8"', $cont);
				}
			}

			restore_error_handler();

			$this->xml = secure_load_simple_xml($cont);

			if (!$this->xml) {
				throw new publicAdminException(getLabel('label-feed-can-not-load-content', false, $this->url));
			}
		}

		public function error_handler($errno, $errstr) {
			if (mb_strpos($errstr, 'Detected an illegal character in input string')) {
				throw new publicAdminException(getLabel('label-feed-incorrect-charset', false, $this->url));
			}
		}

		public function loadRSS() {
			if (mb_strtolower($this->xml->getName()) != 'rss') {
				throw new publicAdminException(getLabel('label-feed-incorrect-type', false, $this->url));
			}
			foreach ($this->xml->channel->item as $xml_item) {
				$item = new RSSItem();
				$item->setTitle($xml_item->title);
				$item->setContent($xml_item->description);
				if ($xml_item->pubDate) {
					$item->setDate($xml_item->pubDate);
				} else {
					$item->setDate(date('Y-m-d H:i'));
				}
				$item->setUrl($xml_item->link);

				$this->items[] = $item;
			}
		}

		public function loadAtom() {
			if (mb_strtolower($this->xml->getName()) != 'feed') {
				throw new publicAdminException(getLabel('label-feed-incorrect-type', false, $this->url));
			}
			foreach ($this->xml as $tag => $xml_item) {
				if ($tag != 'entry') {
					continue;
				}

				if ($xml_item->content) {
					$content = $xml_item->content;
				} else {
					$content = $xml_item->summary;
				}

				$item = new RSSItem();
				$item->setTitle($xml_item->title);
				$item->setContent($content);
				$item->setDate($xml_item->published);
				$item->setUrl($xml_item->link['href']);

				$this->items[] = $item;
			}
		}

		public function returnItems() {
			return $this->items;
		}
	}

