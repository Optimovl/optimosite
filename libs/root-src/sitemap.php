<?php

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/config.php';
	define('SEARCH_ENGINES_SITE_MAP_URL_LIMIT', 50000);

	$buffer = Service::Response()
		->getCurrentBuffer();
	$buffer->contentType('text/xml');
	$buffer->charset('utf-8');
	$buffer->push('<?xml version="1.0" encoding="utf-8"?>');
	$cmsController = cmsController::getInstance();
	$currentDomain = Service::DomainDetector()->detect();
	$domainId = $currentDomain->getId();
	$host = $currentDomain->getHost();

	switch (true) {
		case (isset($_GET['id']) && $_GET['id'] === '') : {
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT count(`id`) FROM `cms_sitemap` WHERE `domain_id`=%d';
			$sql = sprintf($sql, $domainId);
			$res = $connection->queryResult($sql);
			$res->setFetchType(IQueryResult::FETCH_ROW);
			$res = ($res->length() > 0) ? $res->fetch() : [0];

			if (isset($res[0]) && (int) $res[0] > SEARCH_ENGINES_SITE_MAP_URL_LIMIT) {
				$siteMap = genSiteIndex($domainId, $host);
				break;
			}

			$siteMap = genSiteMap($domainId);
			break;
		}
		case (isset($_GET['id']) && (int) $_GET['id'] >= 0 && (int) $_GET['id'] <= 16) : {
			$siteMap = genSiteMap($domainId, $_GET['id']);
			break;
		}
		default : {
			$siteMap = getEmptySiteMap();
		}
	}

	$buffer->push($siteMap);
	$buffer->end();

	/**
	 * Возвращает индексную страницу карты сайта с пагинацией
	 * @param int $domainId идентификатор текущего домена
	 * @param string $host текущий домен
	 * @return string
	 */
	function genSiteIndex($domainId, $host) {
		$siteMap = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$connection = ConnectionPool::getInstance()->getConnection();
		$sql = 'SELECT `sort`, MAX(  `dt` ) FROM `cms_sitemap` WHERE `domain_id`=%d GROUP BY `sort`';
		$sql = sprintf($sql, $domainId);
		$res = $connection->queryResult($sql);
		$res->setFetchType(IQueryResult::FETCH_ROW);
		foreach ($res as $row) {
			list($sort, $dt) = $row;
			$dom = new DOMDocument();
			$url = $dom->createElement('sitemap');
			$loc = $dom->createElement('loc', sprintf('%s://%s/sitemap%d.xml', getSelectedServerProtocol(), $host, $sort));
			$lastMod = $dom->createElement('lastmod', date('c', strtotime($dt)));
			$dom->appendChild($url);
			$url->appendChild($loc);
			$url->appendChild($lastMod);
			$siteMap .= $dom->saveXML($url);
		}
		$siteMap .= '</sitemapindex>';
		return $siteMap;
	}

	/**
	 * Возвращает карту сайта
	 * @param int $domainId идентификатор текущего домена
	 * @param bool|int $pageId номер страницы в рамках пагинации
	 * @return string
	 */
	function genSiteMap($domainId, $pageId = false) {
		$siteMap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$domainId = (int) $domainId;
		$connection = ConnectionPool::getInstance()->getConnection();
		$sql = "SELECT `link`,`priority`,`dt` FROM `cms_sitemap` WHERE `domain_id` = $domainId";
		if ($pageId !== false) {
			$sql .= ' AND `sort`=%d';
			$sql = sprintf($sql, $pageId);
		}
		$sql .= ' ORDER BY `level`;';
		$res = $connection->queryResult($sql);
		$res->setFetchType(IQueryResult::FETCH_ROW);
		foreach ($res as $row) {
			list($link, $pagePriority, $dt) = $row;
			$dom = new DOMDocument();
			$url = $dom->createElement('url');
			$loc = $dom->createElement('loc', $link);
			$priority = $dom->createElement('priority', $pagePriority);
			$lastMod = $dom->createElement('lastmod', date('c', strtotime($dt)));
			$dom->appendChild($url);
			$url->appendChild($loc);
			$url->appendChild($lastMod);
			$url->appendChild($priority);
			$siteMap .= $dom->saveXML($url);
		}
		$siteMap .= '</urlset>';
		return $siteMap;
	}

	/**
	 * Возвращает пустую карту сайта
	 * @return string
	 */
	function getEmptySiteMap() {
		return '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
	}
