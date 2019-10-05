<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Счетчик"
	 */
	class umiObjectPropertyCounter extends umiObjectProperty {

		protected $oldValue;

		/** @inheritdoc */
		protected function loadValue() {
			$fieldId = $this->field_id;
			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT cnt FROM $tableName WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$cnt = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$cnt = (int) array_shift($fetchResult);
			}

			$this->oldValue = $cnt;
			return [$cnt];
		}

		/** @inheritdoc */
		protected function saveValue() {
			$value = umiCount($this->value) ? (int) $this->value[0] : 0;
			$lambda = $value - $this->oldValue;
			$connection = $this->getConnection();
			$tableName = $this->getTableName();

			if ((abs($lambda) == 1) && $value !== 0 && $this->oldValue) {
				$sql = <<<SQL
UPDATE $tableName SET cnt = cnt + ({$lambda})
WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'
SQL;
				try {
					$connection->query($sql);
				} catch (databaseException $e) {
					throw $e;
				}
			} else {
				$this->deleteCurrentRows();
				$sql = <<<SQL
INSERT INTO $tableName (obj_id, field_id, cnt)
VALUES('{$this->object_id}', '{$this->field_id}', '{$value}')
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
				$oldValue = 0;
			}

			if (isset($newValue[0])) {
				$newValue = (int) $newValue[0];
			} else {
				$newValue = 0;
			}

			return $oldValue !== $newValue;
		}
	}
