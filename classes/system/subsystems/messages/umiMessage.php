<?php

	use UmiCms\Service;

	class umiMessage extends umiEntinty implements iUmiMessage {

		/** @var umiDate $createTime */
		protected $store_type = 'message', $title, $content, $senderId, $createTime, $type, $priority, $isSended;

		public function getTitle() {
			return $this->title;
		}

		public function setTitle($title) {
			$title = (string) $title;

			if ($this->getTitle() != $title) {
				$this->title = $title;
				$this->setIsUpdated();
			}
		}

		public function getContent() {
			return $this->content;
		}

		public function setContent($content) {
			$content = (string) $content;

			if ($this->getContent() != $content) {
				$this->content = $content;
				$this->setIsUpdated();
			}
		}

		public function getSenderId() {
			return $this->senderId;
		}

		public function setSenderId($senderId = null) {
			$senderId = (int) $senderId;

			if ($this->getSenderId() != $senderId) {
				$this->senderId = $senderId;
				$this->setIsUpdated();
			}
		}

		public function getType() {
			return $this->type;
		}

		public function setType($type) {
			$type = (string) $type;

			if (!in_array($type, umiMessages::getAllowedTypes())) {
				throw new coreException("Unknown message type \"{$type}\"");
			}

			if ($this->getType() != $type) {
				$this->type = $type;
				$this->setIsUpdated();
			}
		}

		public function getPriority() {
			return $this->priority;
		}

		public function setPriority($priority = 0) {
			$priority = (int) $priority;

			if ($this->getPriority() != $priority) {
				$this->priority = $priority;
				$this->setIsUpdated();
			}
		}

		public function getCreateTime() {
			return $this->createTime;
		}

		public function setCreateTime($time) {
			$time = ($time instanceof umiDate) ? $time : new umiDate($time);

			if ($this->getCreateTime()->getDateTimeStamp() != $time->getDateTimeStamp()) {
				$this->createTime = $time;
				$this->setIsUpdated();
			}
		}

		public function getIsSended() {
			return $this->isSended;
		}

		public function getRecipients() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$id = (int) $this->id;
			$sql = <<<SQL
SELECT `recipient_id` FROM `cms3_messages_inbox` WHERE `message_id` = '{$id}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$recipients = [];

			foreach ($result as $row) {
				$recipients[] = array_shift($row);
			}

			return $recipients;
		}

		public function send($recipients) {
			if ($this->getIsSended()) {
				return false;
			}

			if (umiCount($recipients)) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$recipientsSql = implode(', ', array_map('intval', $recipients));

				$id = (int) $this->id;

				$sql = <<<SQL
INSERT INTO `cms3_messages_inbox`
	(`message_id`, `recipient_id`)
		SELECT '{$id}', `id` FROM `cms3_objects`
			WHERE `id` IN ({$recipientsSql})
SQL;
				$connection->query($sql);
			}
			$this->setIsSended(true);
			$this->setIsUpdated();
		}

		public function setIsOpened($isOpened, $userId = false) {
			if ($userId) {
				$userId = (int) $userId;
			} else {
				$auth = Service::Auth();
				$userId = $auth->getUserId();
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$isOpened = (int) $isOpened;
			$id = (int) $this->id;

			$sql = <<<SQL
UPDATE `cms3_messages_inbox` SET `is_opened` = '{$isOpened}' WHERE `message_id` = '{$id}' AND `recipient_id` = '{$userId}'
SQL;
			$connection->query($sql);
		}

		private function setIsSended($isSended) {
			$isSended = (bool) $isSended;

			if ($this->getIsSended() != $isSended) {
				$this->isSended = $isSended;
				$this->setIsUpdated();
			}
		}

		protected function loadInfo($row = false) {
			if (!is_array($row) || count($row) < 8) {
				$connection = ConnectionPool::getInstance()->getConnection();
				$escapedId = (int) $this->getId();
				$sql = <<<SQL
SELECT `id`, `title`, `content`, `sender_id`, `create_time`, `type`, `priority`, `is_sended`
	FROM `cms3_messages` WHERE `id` = $escapedId LIMIT 0,1
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$row = $result->fetch();
			}

			if (!is_array($row) || count($row) < 8) {
				return false;
			}

			list($id, $title, $content, $senderId, $createTime, $type, $priority, $isSent) = $row;

			$this->title = (string) $title;
			$this->content = (string) $content;
			$this->senderId = (int) $senderId;
			$this->createTime = new umiDate($createTime);
			$this->type = (string) $type;
			$this->priority = (int) $priority;
			$this->isSended = (bool) $isSent;

			return true;
		}

		protected function save() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$title = $connection->escape($this->title);
			$content = $connection->escape($this->content);
			$senderId = $this->senderId ?: 'NULL';
			$createTime = $this->createTime->getDateTimeStamp();
			$priority = (int) $this->priority;
			$type = $this->type;
			$isSended = (int) $this->isSended;
			$id = (int) $this->id;

			$sql = <<<SQL
UPDATE `cms3_messages`
	SET `title` = '{$title}', `content` = '{$content}',
		`create_time` = '{$createTime}', `priority` = '{$priority}',
		`type` = '{$type}', `sender_id` = {$senderId}, `is_sended` = '{$isSended}'
			WHERE `id` = '{$id}'
SQL;
			$connection->query($sql);
		}

		/** Деструктор */
		public function __destruct() {
			//nothing
		}
	}
