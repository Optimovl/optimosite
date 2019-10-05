<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Выпадающий список", т.е. свойства с использованием справочников.
	 */
	class umiObjectPropertyRelation extends umiObjectProperty {

		/** @const разделитель идентификторов в строке */
		const DELIMITER_ID = ',';

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$tableName = $this->getTableName();
			$data = $this->getPropData();

			if ($data) {
				foreach ($data['rel_val'] as $val) {
					if ($val === null) {
						continue;
					}
					$res[] = $val;
				}
				return $res;
			}

			if ($this->getIsMultiple()) {
				$sql = "SELECT rel_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}'";
			} else {
				$sql = "SELECT rel_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}' LIMIT 1";
			}

			$connection = $this->getConnection();
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$val = array_shift($row);

				if ($val === null) {
					continue;
				}

				$res[] = $val;
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();

			if ($this->value === null) {
				return;
			}

			$tmp = [];

			foreach ($this->value as $val) {
				if (!$val) {
					continue;
				}

				if (is_string($val) && contains($val, '|')) {
					$tmp1 = explode('|', $val);
					foreach ($tmp1 as $v) {
						$v = trim($v);
						if ($v) {
							$tmp[] = $v;
						}
						unset($v);
					}
					unset($tmp1);

					$this->getField()->setFieldTypeId(
						umiFieldTypesCollection::getInstance()->getFieldTypeByDataType('relation', 1)->getId()
					);
				} else {
					$tmp[] = $val;
				}
			}

			$this->value = $tmp;
			unset($tmp);
			$filteredValueList = [];

			foreach ($this->value as $index => $value) {
				if ($value) {
					$value = $this->prepareRelationValue($value);
				}

				if ($value) {
					$filteredValueList[] = $value;
				}
			}

			if (isEmptyArray($filteredValueList)) {
				return;
			}

			$tableName = $this->getTableName();
			$query = <<<SQL
INSERT INTO `$tableName` (`obj_id`, `field_id`, `rel_val`) VALUES
SQL;
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();

			foreach ($filteredValueList as $filteredValue) {
				$filteredValue = (int) $filteredValue;
				$query .= sprintf("(%d, %d, %d),", $objectId, $fieldId, $filteredValue);
			}

			$query = rtrim($query, ',') . ';';
			$this->getConnection()->query($query);
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;
			$oldValue = $this->normaliseValue($oldValue);
			$newValue = $this->normaliseValue($newValue);

			if (umiCount($oldValue) !== umiCount($newValue)) {
				return true;
			}

			if (!$this->getIsMultiple()) {
				if (isset($oldValue[0])) {
					$oldValue = $oldValue[0];
				} else {
					$oldValue = null;
				}

				if (isset($newValue[0])) {
					$newValue = $newValue[0];
				} else {
					$newValue = null;
				}

				return $oldValue !== $newValue;
			}

			foreach ($newValue as $newValueRel) {
				if (!in_array($newValueRel, $oldValue)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Приводит значение поля типа "выпадающий список" к определенному формату, для сравнения.
		 * Возвращает результат форматирования.
		 * @param array $values значение поля типа "выпадающий список"
		 * @return array
		 */
		private function normaliseValue(array $values) {
			if (umiCount($values) == 0) {
				return $values;
			}

			$normalisedValues = [];

			foreach ($values as $value) {
				switch (true) {
					case $value instanceof iUmiEntinty: {
						$normalisedValues[] = (int) $value->getId();
						break;
					}
					case is_numeric($value) && (int) $value > 0 : {
						$normalisedValues[] = (int) $value;
						break;
					}
					case is_string($value) && !empty($value): {
						$normalisedValues[] = (string) $value;
						break;
					}
				}
			}

			return $normalisedValues;
		}
	}
