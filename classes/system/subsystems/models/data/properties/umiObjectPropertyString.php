<?php

	use UmiCms\Service;

	/**
	 * Этот класс служит для управления полем объекта
	 * Обрабатывает тип поля "Строка".
	 */
	class umiObjectPropertyString extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$data = $this->getPropData();
			$emojiTranslator = Service::EmojiTranslator();

			if ($data) {
				foreach ($data['varchar_val'] as $val) {
					if ($val === null) {
						continue;
					}
					$res[] = (string) $emojiTranslator->fromShortNameToUnicode($val);
				}
				return $res;
			}

			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT varchar_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$val = array_shift($row);

				if ($val === null) {
					continue;
				}

				$res[] = (string) $emojiTranslator->fromShortNameToUnicode($val);
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();
			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$emojiTranslator = Service::EmojiTranslator();

			foreach ($this->value as $val) {
				if ($val === '' || $val === null) {
					continue;
				}

				$val = $emojiTranslator->fromUnicodeToShortName($val);
				$val = self::filterInputString($val);
				$sql = <<<SQL
INSERT INTO {$tableName} (obj_id, field_id, varchar_val)
VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')
SQL;
				$connection->query($sql);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			if (isset($oldValue[0])) {
				$oldValue = (string) $oldValue[0];
			} else {
				$oldValue = '';
			}

			if (isset($newValue[0])) {
				$newValue = (string) $newValue[0];
			} else {
				$newValue = '';
			}

			return $oldValue !== $newValue;
		}
	}
