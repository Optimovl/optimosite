<?php

	use UmiCms\Service;

	/**
	 * Этот класс служит для управления полем объекта
	 * Обрабатывает тип поля "WYSIWYG".
	 */
	class umiObjectPropertyWYSIWYG extends umiObjectPropertyText {

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

					if (str_replace('&nbsp;', '', trim($val)) == '') {
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

				if (str_replace('&nbsp;', '', trim($val)) == '') {
					continue;
				}

				$res[] = (string) $emojiTranslator->fromShortNameToUnicode($val);
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			foreach ($this->value as $i => $value) {
				$value = str_replace(['&lt;!--', '--&gt;'], ['<!--', '-->'], $value);
				$value = preg_replace('/<!--\[if(.*?)>(.*?)<!(-*)\[endif\][\s]*-->/mis', '', $value);
				$this->value[$i] = $value;
			}
			parent::saveValue();
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
				$newValue = str_replace(['&lt;!--', '--&gt;'], ['<!--', '-->'], $newValue);
				$newValue = preg_replace('/<!--\[if(.*?)>(.*?)<!(-*)\[endif\][\s]*-->/mis', '', $newValue);
				$newValue = ($newValue === '<p />' || $newValue === '&nbsp;') ? '' : $newValue;
			} else {
				$newValue = '';
			}

			return $oldValue !== $newValue;
		}
	}
