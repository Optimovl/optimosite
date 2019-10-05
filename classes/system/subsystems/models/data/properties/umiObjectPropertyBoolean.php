<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Кнопка-флажок" (булевый тип)
	 */
	class umiObjectPropertyBoolean extends umiObjectProperty {

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

			foreach ($this->value as $val) {
				if (!$val) {
					continue;
				}

				$val = (int) $this->boolval($val, true);
				$tableName = $this->getTableName();
				$sql = <<<SQL
INSERT INTO {$tableName} (obj_id, field_id, int_val)
VALUES ('{$this->object_id}', '{$this->field_id}', '{$val}')
SQL;
				$connection->query($sql);
			}
		}

		/**
		 * TODO PHPDoc
		 * Возврашает булевое значение входного параметра $in, распознавая, в том числе
		 * Yes, no, 'false' и т.д.
		 * @param mixed $in проверяемое значение
		 * @param bool $strict
		 * @return boolean
		 */
		protected function boolval($in, $strict = false) {
			$out = null;
			// if not strict, we only have to check if something is false
			if (in_array($in, ['false', 'False', 'FALSE', 'no', 'No', 'n', 'N', '0', 'off', 'Off', 'OFF', false, 0, null], true)) {
				$out = false;
			} else {
				if ($strict) {
					// if strict, check the equivalent true values
					if (in_array($in, ['true', 'True', 'TRUE', 'yes', 'Yes', 'y', 'Y', '1', 'on', 'On', 'ON', true, 1], true)) {
						$out = true;
					}
				} else {
					// not strict? let the regular php bool check figure it out (will largely default to true)
					$out = ($in ? true : false);
				}
			}

			return $out;
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			if (isset($oldValue[0])) {
				$oldValue = $this->boolval($oldValue[0]);
			} else {
				$oldValue = false;
			}

			if (isset($newValue[0])) {
				$newValue = $this->boolval($newValue[0]);
			} else {
				$newValue = false;
			}

			return $oldValue !== $newValue;
		}
	}
