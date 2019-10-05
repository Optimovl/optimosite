<?php

	use UmiCms\Service;

	class umiPagenum implements iPagenum {

		public static $max_pages = 5;

		public static function generateNumPage(
			$total,
			$perPage,
			$template = 'default',
			$pageParam = 'p',
			$maxPages = false
		) {
			$perPage = (int) $perPage;
			$total = (int) $total;

			if ($perPage == 0) {
				$perPage = $total;
			}

			if (!$template) {
				$template = 'default';
			}

			if (!$pageParam) {
				$pageParam = 'p';
			}

			if ($maxPages === false) {
				$maxPages = self::$max_pages;
			}

			list(
				$template_block,
				$template_block_empty,
				$template_item,
				$template_item_a,
				$template_quant,
				$template_tobegin,
				$template_tobegin_a,
				$template_toend,
				$template_toend_a,
				$template_toprev,
				$template_toprev_a,
				$template_tonext,
				$template_tonext_a
				) = def_module::loadTemplates(
				'numpages/' . $template,
				'pages_block',
				'pages_block_empty',
				'pages_item',
				'pages_item_a',
				'pages_quant',
				'pages_tobegin',
				'pages_tobegin_a',
				'pages_toend',
				'pages_toend_a',
				'pages_toprev',
				'pages_toprev_a',
				'pages_tonext',
				'pages_tonext_a'
			);

			$isXslt = def_module::isXSLTResultMode();

			$currentPage = (string) getRequest($pageParam);
			if ($currentPage != 'all') {
				$currentPage = (int) $currentPage;
			}

			if (self::isInvalidPage($total, $perPage, $currentPage)) {
				return $isXslt ? '' : $template_block_empty;
			}

			$getParams = self::getPreparedGetParams($pageParam);
			$queryString = self::getQueryString($getParams);

			$block_arr = [];
			$pages = [];
			$pageCount = ceil($total / $perPage);

			if (!$pageCount) {
				$pageCount = 1;
			}

			for ($i = 0; $i < $pageCount; $i++) {
				$line_arr = [];

				$n = $i + 1;

				if (($currentPage - $maxPages) >= $i) {
					continue;
				}

				if (($currentPage + $maxPages) <= $i) {
					break;
				}

				$tpl = $template_item;

				if ($currentPage !== 'all') {
					$tpl = ($i == $currentPage) ? $template_item_a : $template_item;
				}

				$link = "?{$pageParam}={$i}" . $queryString;

				$line_arr['attribute:link'] = $link;
				$line_arr['attribute:page-num'] = $i;

				if ($currentPage == $i) {
					$line_arr['attribute:is-active'] = true;
				}

				$line_arr['node:num'] = $n;
				$line_arr['void:quant'] = (($i < (($currentPage + $maxPages) - 1)) && ($i < ($pageCount - 1)))
					? $template_quant
					: '';

				$pages[] = def_module::parseTemplate($tpl, $line_arr);
			}

			$block_arr['subnodes:items'] = $block_arr['void:pages'] = $pages;

			if (!$isXslt) {
				$block_arr['tobegin'] = ($currentPage == 0 || $pageCount <= 1) ? $template_tobegin_a : $template_tobegin;
				$block_arr['toprev'] = ($currentPage == 0 || $pageCount <= 1) ? $template_toprev_a : $template_toprev;
				$block_arr['toend'] = ($currentPage == ($pageCount - 1) || $pageCount <= 1)
					? $template_toend_a
					: $template_toend;
				$block_arr['tonext'] = ($currentPage == ($pageCount - 1) || $pageCount <= 1)
					? $template_tonext_a
					: $template_tonext;
			}

			if ($currentPage != 0) {
				$tobegin_link = "?{$pageParam}=0" . $queryString;

				if ($isXslt) {
					$block_arr['tobegin_link'] = [
						'attribute:page-num' => 0,
						'node:value' => $tobegin_link,
					];
				} else {
					$block_arr['tobegin_link'] = $tobegin_link;
				}
			}

			if ($currentPage < $pageCount - 1) {
				$toend_link = "?{$pageParam}=" . ($pageCount - 1) . $queryString;

				if ($isXslt) {
					$block_arr['toend_link'] = [
						'attribute:page-num' => $pageCount - 1,
						'node:value' => $toend_link,
					];
				} else {
					$block_arr['toend_link'] = $toend_link;
				}
			}

			if ($currentPage - 1 >= 0) {
				$toprev_link = "?{$pageParam}=" . ($currentPage - 1) . $queryString;

				if ($isXslt) {
					$block_arr['toprev_link'] = [
						'attribute:page-num' => $currentPage - 1,
						'node:value' => $toprev_link,
					];
				} else {
					$block_arr['toprev_link'] = $toprev_link;
				}
			}

			if ($currentPage < $pageCount - 1) {
				$tonext_link = "?{$pageParam}=" . ($currentPage + 1) . $queryString;

				if ($isXslt) {
					$block_arr['tonext_link'] = [
						'attribute:page-num' => $currentPage + 1,
						'node:value' => $tonext_link,
					];
				} else {
					$block_arr['tonext_link'] = $tonext_link;
				}
			}

			$block_arr['current-page'] = (int) $currentPage;
			return def_module::parseTemplate($template_block, $block_arr);
		}

		public static function generateOrderBy($fieldName, $type_id, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			list($template_block, $template_block_a) =
				def_module::loadTemplates('numpages/' . $template, 'order_by', 'order_by_a');

			if (!($type = umiObjectTypesCollection::getInstance()->getType($type_id))) {
				return '';
			}

			$block_arr = [];

			if (($field_id = $type->getFieldId($fieldName)) || ($fieldName == 'name')) {
				$params = $_GET;
				unset($params['umi_authorization']);
				unset($params['path']);

				if (array_key_exists('scheme', $params)) {
					unset($params['scheme']);
				}

				$order_filter = getArrayKey($params, 'order_filter');

				if (is_array($order_filter)) {
					$tpl = array_key_exists($fieldName, $order_filter) ? $template_block_a : $template_block;
				} else {
					$tpl = $template_block;
				}

				unset($params['order_filter']);
				$params['order_filter'][$fieldName] = 1;
				$params = self::protectParams($params);

				$q = umiCount($params) ? http_build_query($params, '', '&') : '';
				$q = urldecode($q);
				$q = str_replace(
					['%', '<', '>', '%3C', '%3E'],
					['&#037;', '&lt;', '&gt;', '&lt;', '&gt;'],
					$q
				);

				$block_arr['link'] = '?' . $q;

				if ($fieldName == 'name') {
					$block_arr['title'] = getLabel('field-name');
				} else {
					$block_arr['title'] = umiFieldsCollection::getInstance()->getField($field_id)->getTitle();
				}

				return def_module::parseTemplate($tpl, $block_arr);
			}

			return '';
		}

		/**
		 * Возвращает подготовленные get-параметры
		 * @param string $pageParam название параметра текущей страницы пагинации (по умолчанию 'p')
		 * @return mixed
		 */
		private static function getPreparedGetParams($pageParam) {
			$params = Service::Request()
				->Get()
				->getArrayCopy();
			$extraParams = [$pageParam, 'path', 'umi_authorization', 'scheme'];

			foreach ($extraParams as $extra) {
				unset($params[$extra]);
			}

			return self::protectParams($params);
		}

		protected static function protectParams($params) {
			foreach ($params as $i => $v) {
				if (is_array($v)) {
					$params[$i] = self::protectParams($v);
				} else {
					$v = htmlspecialchars($v);
					$params[$i] = str_replace(
						['%', '<', '>', '%3C', '%3E'],
						['&#037;', '&lt;', '&gt;', '&lt;', '&gt;'],
						$v
					);
				}
			}

			return $params;
		}

		/**
		 * Возвращает отформатированную строку запроса
		 * @param array $getParams get-параметры
		 * @return mixed|string
		 */
		private static function getQueryString($getParams) {
			$queryString = umiCount($getParams) ? '&' . http_build_query($getParams, '', '&') : '';

			if (!def_module::isXSLTResultMode()) {
				$queryString = str_replace('%', '&#37;', $queryString);
			}

			return str_replace(['<', '>', '%3C', '%3E'], ['&lt;', '&gt;', '&lt;', '&gt;'], $queryString);
		}

		/**
		 * Является ли запрошенная страница пагинации некорректной
		 * @param int $total общее число элементов
		 * @param int $perPage число элементов на одной странице
		 * @param mixed $currentPage текущая страница пагинации, число или 'all'
		 * @return bool
		 */
		private static function isInvalidPage($total, $perPage, $currentPage) {
			if ($total <= 0) {
				return true;
			}

			if ($total <= $perPage) {
				return true;
			}

			if ($currentPage === 'all') {
				return false;
			}

			return ($currentPage * $perPage) > $total;
		}
	}
