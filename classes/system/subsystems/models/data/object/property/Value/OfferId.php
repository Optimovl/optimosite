<?php
	namespace UmiCms\System\Data\Object\Property\Value;

	/**
	 * Класс значения поля типа "Ссылка на торговое предложение"
	 * @package UmiCms\System\Data\Object\Property\Value
	 */
	class OfferId extends \umiObjectProperty {

		/** @var int|null $valueId идентификатор значения */
		private $valueId;

		/** @inheritdoc */
		protected function loadValue() {
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$tableName = $this->getTableName();
			$query = <<<SQL
SELECT `id`, `obj_id`, `field_id`, `offer_id` FROM `$tableName` 
WHERE `obj_id` = $objectId AND `field_id` = $fieldId LIMIT 0, 1
SQL;
			$result = $this->getConnection()
				->queryResult($query)
				->setFetchAssoc();

			if ($result->length() == 0) {
				return [];
			}

			$row = $result->fetch();
			$this->setValueId($row['id']);
			return [
				(int) $row['offer_id']
			];
		}

		/** @inheritdoc */
		protected function saveValue() {
			$offerId = getFirstValue($this->value);
			$offerId = is_numeric($offerId) ? (int) $offerId : null;

			if ($this->getValueId() === null) {
				$this->insertRow($offerId);
			} else {
				$this->updateRow($offerId);
			}

			return true;
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$newOfferId = (int) getFirstValue($newValue);
			$oldOfferId = (int) getFirstValue($this->value);
			return $oldOfferId !== $newOfferId;
		}

		/**
		 * Вставляет новую строку в хранилище
		 * @param int|null $offerId идентификатор предложения
		 * @throws \databaseException
		 */
		private function insertRow($offerId) {
			if ($offerId === null) {
				return $this->deleteCurrentRows();
			}

			$tableName = $this->getTableName();
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$offerId = (int) $offerId;
			$query = <<<SQL
INSERT INTO `$tableName` (`obj_id`, `field_id`, `offer_id`) 
VALUES ($objectId, $fieldId, $offerId)
SQL;
			$connection = $this->getConnection();
			$connection->query($query);

			$this->setValueId($connection->insertId());
		}

		/**
		 * Обновляет строку в хранилище
		 * @param int|null $offerId идентификатор предложения
		 * @throws \databaseException
		 */
		private function updateRow($offerId) {
			if ($offerId === null) {
				return $this->deleteCurrentRows();
			}

			$tableName = $this->getTableName();
			$offerId = (int) $offerId;
			$valueId = (int) $this->getValueId();
			$query = <<<SQL
UPDATE `$tableName` SET `offer_id` = $offerId WHERE `id` = $valueId
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