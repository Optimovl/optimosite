<?php

	namespace UmiCms\System\Data\Object\Property\Value;

	/**
	 * Класс значения поля типа "Ссылка на список доменов"
	 * @package UmiCms\System\Data\Object\Property\Value
	 */
	class DomainIdList extends \umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$tableName = $this->getTableName();

			$query = <<<SQL
SELECT `obj_id`, `field_id`, `domain_id` FROM `$tableName` 
WHERE `obj_id` = $objectId AND `field_id` = $fieldId
SQL;

			$result = $this->getConnection()
				->queryResult($query);
			$result->setFetchType(\IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return [];
			}

			$idList = [];

			foreach ($result as $row) {
				$idList[] = (int) $row['domain_id'];
			}

			return $idList;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();

			$domainIdList = (array) $this->value;
			$domainIdList = $this->filterDomainIdList($domainIdList);

			if (isEmptyArray($domainIdList)) {
				return true;
			}

			$tableName = $this->getTableName();
			$query = <<<SQL
INSERT INTO `$tableName` (`obj_id`, `field_id`, `domain_id`) VALUES
SQL;
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();

			foreach ($domainIdList as $domainId) {
				$domainId = (int) $domainId;
				$query .= sprintf("(%d, %d, %d),", $objectId, $fieldId, $domainId);
			}

			$query = rtrim($query, ',') . ';';
			$this->getConnection()->query($query);

			return true;
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$newDomainIdList = $newValue;
			$newDomainIdList = $this->filterDomainIdList($newDomainIdList);

			$oldDomainIdList = (array) $this->value;
			$oldDomainIdList = $this->filterDomainIdList($oldDomainIdList);

			if (count($newDomainIdList) !== count($oldDomainIdList)) {
				return true;
			}

			foreach ($newDomainIdList as $newDomainId) {
				if (!in_array($newDomainId, $oldDomainIdList)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Фильтрует некорректные значение из массива идентификаторов доменов
		 * @param array $domainIdList массив идентификаторов доменов
		 * @return array
		 */
		private function filterDomainIdList(array $domainIdList) {
			return array_filter($domainIdList, function ($domainId) {
				return is_numeric($domainId);
			});
		}
	}
