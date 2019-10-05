<?php

	/**
	 * Этот класс служит для управления полем объекта
	 * Обрабатывает тип поля "Теги".
	 */
	class umiObjectPropertyTags extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$data = $this->getPropData();

			if ($data) {
				foreach ($data['varchar_val'] as $val) {
					if ($val === null) {
						continue;
					}
					$res[] = (string) $val;
				}
				return $res;
			}

			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT varchar_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$val = array_shift($row);

				if ($val === null) {
					continue;
				}

				$res[] = (string) $val;
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();

			if (umiCount($this->value) == 1) {
				$value = trim($this->value[0], ',');
				$value = preg_replace("/[^A-Za-z0-9А-Яа-яЁё'\-$%_,\s]/u", '', $value);
				$valueList = explode(',', $value);
			} else {
				$valueList = array_map(
					function ($part) {
						return preg_replace("/[^A-Za-z0-9А-Яа-яЁё'\\-\\$%_,\s]?/u", '', $part);
					},
					$this->value
				);
			}

			$trimmedValueList = array_map(function($value) {
				return trim($value);
			}, $valueList);

			$filteredValueList = array_filter($trimmedValueList, function ($trimmedValue) {
				return ($trimmedValue !== '');
			});
			$filteredValueList = array_unique($filteredValueList);

			if (isEmptyArray($filteredValueList)) {
				return;
			}

			$tableName = $this->getTableName();
			$query = <<<SQL
INSERT INTO `$tableName` (`obj_id`, `field_id`, `varchar_val`) VALUES
SQL;
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();

			foreach ($filteredValueList as $filteredValue) {
				$filteredValue = self::filterInputString($filteredValue);
				$query .= sprintf("(%d, %d, '%s'),", $objectId, $fieldId, $filteredValue);
			}

			$query = rtrim($query, ',') . ';';
			$this->getConnection()->query($query);
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;
			$oldValue = array_unique($oldValue);

			$newValue = array_filter(
				$newValue,
				function ($value) {
					$value = (string) $value;
					return !empty($value);
				}
			);
			$newValue = array_unique($newValue);

			switch (true) {
				case empty($oldValue) && empty($newValue) : {
					return false;
				}
				case empty($oldValue) && !empty($newValue) : {
					return true;
				}
				case !empty($oldValue) && empty($newValue) : {
					return true;
				}
				case count($oldValue) !== count($newValue) : {
					return true;
				}
				default : {
					$normalisedOldValue = [];

					foreach ($oldValue as $oldValueTag) {
						$normalisedOldValue[] = (string) $oldValueTag;
					}

					foreach ($newValue as $newValueTag) {
						$normalisedNewValueTag = (string) $newValueTag;

						if (!in_array($normalisedNewValueTag, $normalisedOldValue)) {
							return true;
						}
					}

					return false;
				}
			}
		}
	}
