<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Число с точкой"
	 */
	class umiObjectPropertyFloat extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$data = $this->getPropData();

			if ($data) {
				foreach ($data['float_val'] as $val) {
					if ($val === null) {
						continue;
					}
					$res[] = (float) $val;
				}
				return $res;
			}

			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT float_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$val = array_shift($row);

				if ($val === null) {
					continue;
				}

				$res[] = (float) $val;
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();
			$connection = $this->getConnection();

			foreach ($this->value as $val) {
				if ($val === false || $val === '') {
					continue;
				}

				if (!contains('.', $val)) {
					$val = str_replace(',', '.', $val);
				}

				$val = (float) $val;
				$tableName = $this->getTableName();
				$sql = <<<SQL
INSERT INTO {$tableName} (obj_id, field_id, float_val)
VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')
SQL;
				$connection->query($sql);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			if (isset($oldValue[0])) {
				$oldValue = (float) $oldValue[0];
			} else {
				$oldValue = 0;
			}

			if (isset($newValue[0])) {
				$newValue = str_replace(',', '.', $newValue[0]);
				$newValue = (float) $newValue;
			} else {
				$newValue = 0;
			}

			return $oldValue !== $newValue;
		}
	}
