<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Дата"
	 */
	class umiObjectPropertyDate extends umiObjectProperty {

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
					$res[] = new umiDate((int) $val);
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

				$res[] = new umiDate((int) $val);
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

				$val = is_object($val) ? (int) $val->timestamp : (int) $val;
				if (!$val) {
					continue;
				}

				$tableName = $this->getTableName();
				$sql =
					"INSERT INTO {$tableName} (obj_id, field_id, int_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				$connection->query($sql);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;
			$newValue = array_filter(
				$newValue,
				function ($value) {
					$timestamp = ($value instanceof umiDate) ? $value->getDateTimeStamp() : (int) $value;
					return $timestamp > 0;
				}
			);

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
				default : {
					$oldValue = array_shift($oldValue);
					$oldValue = ($oldValue instanceof umiDate) ? $oldValue->getDateTimeStamp() : (int) $oldValue;

					$newValue = array_shift($newValue);
					$newValue = ($newValue instanceof umiDate) ? $newValue->getDateTimeStamp() : (int) $newValue;

					return $oldValue !== $newValue;
				}
			}
		}
	}
