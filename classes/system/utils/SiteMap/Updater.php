<?php

	namespace UmiCms\Utils\SiteMap;

	use UmiCms\System\Events\iEventPointFactory as EventFactory;

	/** Класс обновления карты сайта */
	class Updater implements iUpdater {

		/** @var \iConnection $connection подключение к базе данных */
		private $connection;

		/** @var \iUmiHierarchy $umiHierarchy модель иерархии */
		private $umiHierarchy;

		/** @var EventFactory $eventFactory фабрика событий */
		private $eventFactory;

		/** @inheritdoc */
		public function __construct(\IConnection $connection, \iUmiHierarchy $hierarchy, EventFactory $eventFactory) {
			$this->connection = $connection;
			$this->umiHierarchy = $hierarchy;
			$this->eventFactory = $eventFactory;
		}

		/** @inheritdoc */
		public function update(\iUmiHierarchyElement $element) {
			$elementId = (int) $element->getId();
			$link = $this->getLink($elementId);
			$updateTime = date('Y-m-d H:i:s', $element->getUpdateTime());
			$priority = (float) $this->getPriority($elementId);
			$maxLevel = (int) $this->getMaxLevel($element);
			$domainId = (int) $element->getDomainId();
			$langId = (int) $element->getLangId();
			$robotsDeny = $this->getRobotsDeny($element);

			$this->delete($elementId);

			$this->getEventFactory()
				->create('before_update_sitemap', 'before')
				->setParam('id', $elementId)
				->setParam('domainId', $domainId)
				->setParam('langId', $langId)
				->addRef('link', $link)
				->addRef('pagePriority', $priority)
				->setParam('updateTime', $updateTime)
				->setParam('level', $maxLevel)
				->addRef('robots_deny', $robotsDeny)
				->call();

			if ($element->getIsActive() && !$robotsDeny && !$element->getIsDeleted()) {
				$connection = $this->getConnection();
				$link = $connection->escape($link);
				$updateTime = $connection->escape($updateTime);
				mt_srand();
				$rnd = mt_rand(0, 16);
				$sql = <<<SQL
INSERT INTO `cms_sitemap` (`id`, `domain_id`, `link`, `sort`, `priority`, `dt`, `level`, `lang_id`)
VALUES ($elementId, $domainId, "$link", $rnd, $priority, "$updateTime", $maxLevel, $langId);
SQL;
				$connection->query($sql);
			}

			return $this;
		}

		/** @inheritdoc */
		public function updateList(array $elementList) {

			foreach ($elementList as $element) {

				if (!$element instanceof \iUmiHierarchyElement) {
					continue;
				}

				$this->update($element);
			}

			return $this;
		}

		/** @inheritdoc */
		public function delete($elementId) {
			if (!is_numeric($elementId) || (int) $elementId === 0) {
				return $this;
			}

			$escapedId = (int) $elementId;

			$sql = <<<SQL
DELETE FROM `cms_sitemap` WHERE `id` = $escapedId;
SQL;
			$this->getConnection()
				->query($sql);

			return $this;
		}

		/** @inheritdoc */
		public function deleteList(array $elementIdList) {
			if (empty($elementIdList)) {
				return $this;
			}

			$escapedIdList = array_map(
				function ($id) {
					return (int) $id;
				},
				$elementIdList
			);

			$condition = implode(', ', $escapedIdList);

			$sql = <<<SQL
DELETE FROM `cms_sitemap` WHERE `id` IN ($condition);
SQL;
			$this->getConnection()
				->query($sql);

			return $this;
		}

		/** @inheritdoc */
		public function deleteAll() {
			$sql = <<<SQL
TRUNCATE TABLE `cms_sitemap`;
SQL;
			$this->getConnection()
				->query($sql);

			return $this;
		}

		/** @inheritdoc */
		public function deleteByDomain($id) {
			$id = (int) $id;

			$sql = <<<SQL
DELETE FROM `cms_sitemap` WHERE `domain_id` = $id;
SQL;
			$this->getConnection()
				->query($sql);

			return $this;
		}

		/**
		 * Определяет заблокирована ли страница в robots.txt
		 * @param \iUmiHierarchyElement $element страница
		 * @return bool
		 */
		private function getRobotsDeny(\iUmiHierarchyElement $element) {
			$pageCollection = $this->getHierarchy();
			$parentIdList = $pageCollection->getAllParents($element->getId(), true);
			$parentList = $pageCollection->loadElements($parentIdList);

			foreach ($parentList as $parent) {
				if ($parent->getValue('robots_deny')) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Возвращает ссылку на страницу
		 * @param int $elementId идентификатор страницы
		 * @return string
		 */
		private function getLink($elementId) {
			$hierarchy = $this->getHierarchy();
			$oldValue = $hierarchy->forceAbsolutePath();

			$ignoreLangPrefix = false;
			$ignoreDefaultStatus = false;
			$ignoreCache = true;
			$link = $hierarchy->getPathById($elementId, $ignoreLangPrefix, $ignoreDefaultStatus, $ignoreCache);

			$hierarchy->forceAbsolutePath($oldValue);
			return $link;
		}

		/**
		 * Возвращает приоритет просмотра страницы поисковым роботом
		 * @param int $elementId идентификатор страницы
		 * @return float
		 */
		private function getPriority($elementId) {
			$escapedId = (int) $elementId;
			$sql = <<<SQL
SELECT `level` FROM `cms3_hierarchy_relations` WHERE (`rel_id` = '' or `rel_id` is null) and `child_id` = $escapedId;
SQL;
			$result = $this->getConnection()
				->queryResult($sql);
			$result->setFetchType(\IQueryResult::FETCH_ROW);
			$pagePriority = 0.5;

			foreach ($result as $row) {
				$level = (int) array_shift($row);
				$pagePriority = round(1 / ($level + 1), 1);

				if ($pagePriority < 0.1) {
					$pagePriority = 0.1;
				}
			}

			return $pagePriority;
		}

		/**
		 * Возвращает максимальный уровень вложенности относительно страницы
		 * @param \iUmiHierarchyElement $element
		 * @return int
		 */
		private function getMaxLevel(\iUmiHierarchyElement $element) {
			$hierarchy = $this->getHierarchy();
			return $element->getIsDefault() ? 0 : (int) $hierarchy->getMaxDepth($element->getId(), 1);
		}

		/**
		 * Возвращает подключение к базе данных
		 * @return \iConnection
		 */
		private function getConnection() {
			return $this->connection;
		}

		/**
		 * Возвращает модель иерархии
		 * @return \iUmiHierarchy
		 */
		private function getHierarchy() {
			return $this->umiHierarchy;
		}

		/**
		 * Возвращает фабрику событий
		 * @return EventFactory
		 */
		private function getEventFactory() {
			return $this->eventFactory;
		}
	}
