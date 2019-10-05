<?php

	namespace UmiCms\System\Data\Object\Property\Value;

	/**
	 * Класс значения поля типа "Ссылка на домен"
	 * @package UmiCms\System\Data\Object\Property\Value
	 */
	class DomainId extends \umiObjectProperty {
		
		/** @var int|null $valueId идентификатор значения */
		private $valueId;

		/** @inheritdoc */
		protected function loadValue() {
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$tableName = $this->getTableName();
			$query = <<<SQL
SELECT `id`, `obj_id`, `field_id`, `domain_id` FROM `$tableName` 
WHERE `obj_id` = $objectId AND `field_id` = $fieldId LIMIT 0, 1
SQL;
			$result = $this->getConnection()
				->queryResult($query);
			$result->setFetchType(\IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return [];
			}

			$row = $result->fetch();
			$this->setValueId($row['id']);
			return [
				(int) $row['domain_id']
			];
		}

		/** @inheritdoc */
		protected function saveValue() {
			$domainId = getFirstValue($this->value);
			$domainId = is_numeric($domainId) ? (int) $domainId : null;

			if ($this->getValueId() === null) {
				$this->insertRow($domainId);
			} else {
				$this->updateRow($domainId);
			}

			return true;
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$newDomainId = (int) getFirstValue($newValue);
			$oldDomainId = (int) getFirstValue($this->value);
			return $oldDomainId !== $newDomainId;
		}

		/**
		 * Вставляет новую строку в хранилище
		 * @param null|int $domainId идентификатор домена
		 * @throws \databaseException
		 */
		private function insertRow($domainId) {
			$tableName = $this->getTableName();
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$domainId = ($domainId === null) ? 'NULL' : (int) $domainId;
			$query = <<<SQL
INSERT INTO `$tableName` (`obj_id`, `field_id`, `domain_id`) 
VALUES ($objectId, $fieldId, $domainId)
SQL;
			$connection = $this->getConnection();
			$connection->query($query);

			$this->setValueId($connection->insertId());
		}

		/**
		 * Обновляет строку в хранилище
		 * @param null|int $domainId идентификатор домена
		 * @throws \databaseException
		 */
		private function updateRow($domainId) {
			$tableName = $this->getTableName();
			$domainId = ($domainId === null) ? 'NULL' : (int) $domainId;
			$valueId = (int) $this->getValueId();
			$query = <<<SQL
UPDATE `$tableName` SET `domain_id` = $domainId WHERE `id` = $valueId
SQL;
			$this->getConnection()
				->query($query);
		}

		/**
		 * Устанавливает идентификатор значения
		 * @param int $id идентификатор
		 * @return $this
		 */
		private function setValueId($id) {
			$this->valueId = (int) $id;
			return $this;
		}

		/**
		 * Возвращает идентификатор значения
		 * @return int|null
		 */
		private function getValueId() {
			return $this->valueId;
		}
	}
