<?php

	use UmiCms\Service;

	/**
	 * Этот класс служит для управления полем объекта
	 * Обрабатывает тип поля "Текст".
	 */
	class umiObjectPropertyText extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$data = $this->getPropData();
			$emojiTranslator = Service::EmojiTranslator();

			if ($data) {
				foreach ($data['text_val'] as $val) {
					if ($val === null) {
						continue;
					}
					$res[] = (string) $emojiTranslator->fromShortNameToUnicode($val);
				}
				return $res;
			}

			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT text_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}' LIMIT 1";
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
				if ($val == '<p />' || $val == '&nbsp;') {
					$val = '';
				}

				$val = $emojiTranslator->fromUnicodeToShortName($val);
				$val = self::filterInputString($val);
				$sql = "INSERT INTO {$tableName} (
							obj_id, field_id, text_val
						) VALUES(
							'{$this->object_id}', '{$this->field_id}', '{$val}'
						)";
				$connection->query($sql);
			}
		}

		public function __wakeup() {
			foreach ($this->value as $i => $v) {
				if (is_string($v)) {
					$this->value[$i] = str_replace('&#037;', '%', $v);
				}
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
				$newValue = ($newValue === '<p />' || $newValue === '&nbsp;') ? '' : $newValue;
				$newValue = (string) $newValue[0];
			} else {
				$newValue = '';
			}

			return $oldValue !== $newValue;
		}
	}
