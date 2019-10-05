<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Число"
	 */
	class umiObjectPropertyInt extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$data = $this->getPropData();

			if ($data) {
				foreach ($data['int_val'] as $val) {
					if ($val === null) {
						continue;
					}
					$res[] = (int) $val;
				}
				return $res;
			}

			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT int_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$val = array_shift($row);

				if ($val === null) {
					continue;
				}

				$res[] = (int) $val;
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();
			$connection = $this->getConnection();
			$tableName = $this->getTableName();

			foreach ($this->value as $val) {
				if ($val === false || $val === '') {
					continue;
				}
				$val = (int) $val;
				$sql = <<<SQL
INSERT INTO {$tableName} (obj_id, field_id, int_val)
VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')
SQL;
				$connection->query($sql);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			if (isset($oldValue[0])) {
				$oldValue = (int) $oldValue[0];
			} else {
				$oldValue = null;
			}

			if (isset($newValue[0])) {
				$newValue = (int) $newValue[0];
			} else {
				$newValue = null;
			}

			return $oldValue !== $newValue;
		}
	}
