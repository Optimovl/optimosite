<?php

	use UmiCms\Service;

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Файл"
	 */
	class umiObjectPropertyFile extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$isAdminMode = Service::Request()->isAdmin();
			$data = $this->getPropData();

			if ($data) {
				foreach ($data['text_val'] as $val) {
					if ($val === null) {
						continue;
					}

					$val = self::unescapeFilePath($val);
					$file = new umiFile($val);

					if ($file->getIsBroken() && !$isAdminMode) {
						continue;
					}

					$res[] = $file;
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

				$file = new umiFile($val);

				if ($file->getIsBroken() && !$isAdminMode) {
					continue;
				}

				$res[] = $file;
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();

			if ($this->value === null) {
				return;
			}

			$connection = $this->getConnection();

			foreach ($this->value as $val) {
				if (!$val) {
					continue;
				}

				$val = ($val instanceof iUmiFile) ? $val : Service::FileFactory()->createSecure($val);

				if ($val->getIsBroken()) {
					continue;
				}

				$val = $connection->escape('.' . $val->getFilePath(true));
				$tableName = $this->getTableName();
				$sql = <<<SQL
INSERT INTO {$tableName} (obj_id, field_id, text_val)
VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')
SQL;
				$connection->query($sql);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			if (!isset($oldValue[0])) {
				$oldValue = '';
			} elseif ($oldValue[0] instanceof iUmiFile) {
				$oldValue = $oldValue[0]->getFilePath();
			} else {
				$oldValue = $oldValue[0];
			}

			if (!isset($newValue[0])) {
				$newValue = '';
			} elseif ($newValue[0] instanceof iUmiFile) {
				$newValue = $newValue[0]->getFilePath();
			} else {
				$newValue = $newValue[0];
			}

			return $oldValue !== $newValue;
		}
	}
