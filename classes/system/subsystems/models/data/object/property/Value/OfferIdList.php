<?php
	namespace UmiCms\System\Data\Object\Property\Value;

	/**
	 * Класс значения поля типа "Ссылка на список торговых предложений"
	 * @package UmiCms\System\Data\Object\Property\Value
	 */
	class OfferIdList extends \umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$tableName = $this->getTableName();

			$query = <<<SQL
SELECT `offer_id` FROM `$tableName` 
WHERE `obj_id` = $objectId AND `field_id` = $fieldId
SQL;
			$result = $this->getConnection()
				->queryResult($query)
				->setFetchAssoc();

			if ($result->length() == 0) {
				return [];
			}

			$idList = [];

			foreach ($result as $row) {
				$idList[] = (int) $row['offer_id'];
			}

			return $idList;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$offerIdList = (array) $this->value;
			$offerIdList = $this->filterOfferIdList($offerIdList);

			$this->deleteCurrentRows();

			if (isEmptyArray($offerIdList)) {
				return $this;
			}

			$tableName = $this->getTableName();
			$query = <<<SQL
INSERT INTO `$tableName` (`obj_id`, `field_id`, `offer_id`) VALUES
SQL;
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();

			foreach ($offerIdList as $offerId) {
				$offerId = (int) $offerId;
				$query .= sprintf('(%d, %d, %d),', $objectId, $fieldId, $offerId);
			}

			$query = rtrim($query, ',') . ';';
			$this->getConnection()
				->query($query);
			return $this;
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$newOfferIdList = $newValue;
			$newOfferIdList = $this->filterOfferIdList($newOfferIdList);

			$oldOfferIdList = (array) $this->value;
			$oldOfferIdList = $this->filterOfferIdList($oldOfferIdList);

			if (count($newOfferIdList) !== count($oldOfferIdList)) {
				return true;
			}

			foreach ($newOfferIdList as $newOfferId) {
				if (!in_array($newOfferId, $oldOfferIdList)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Фильтрует некорректные значения из массива идентификаторов торговых предложений
		 * @param array $offerIdList массив идентификаторов торговых предложений
		 * @return array
		 */
		private function filterOfferIdList(array $offerIdList) {
			return array_filter($offerIdList, function ($offerId) {
				return is_numeric($offerId);
			});
		}
	}