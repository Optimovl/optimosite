<?php

	namespace UmiCms\System\Data\Object\Property\Value\ImgFile;

	use UmiCms\System\Data\Object\Property\Value\iMigration;
	use UmiCms\System\Data\Object\Property\Value\Migration as AbstractMigration;

	/**
	 * Класс миграции значений полей типа "Изображение" в хранилище для полей типа "Набор изображений"
	 * @package UmiCms\System\Data\Object\Property\Value\ImgFile
	 */
	class Migration extends AbstractMigration implements iMigration {

		/** @inheritdoc */
		public function migrate(\iUmiObjectProperty $property, $previousDataType) {
			return $this->moveValues($property, $previousDataType, $property->getDataType());
		}

		/** @inheritdoc */
		public function rollback(\iUmiObjectProperty $property, $previousDataType) {
			return $this->moveValues($property, $property->getDataType(), $previousDataType);
		}

		/** @inheritdoc */
		protected function moveValues(\iUmiObjectProperty $property, $sourceDataType, $targetDataType) {
			$connection = $this->getConnection();
			$format = 'Migrate property value "%s" from "%s" to "%s"';
			$message = sprintf($format, $property->getName(), $sourceDataType, $targetDataType);
			$connection->startTransaction($message);

			try {
				$schema = $this->getSchema();
				$imagesTable = $schema->getImagesTable();
				$defaultTable = $schema->getDefaultTable();

				$sourceColumn = $this->getColumnByDataType($sourceDataType);
				$targetColumn = $this->getColumnByDataType($targetDataType);
				$sourceTable = ($sourceColumn === 'text_val') ? $defaultTable : $imagesTable;
				$targetTable = ($sourceTable === $imagesTable) ? $defaultTable : $imagesTable;

				$this->moveRowsToTarget($property, $sourceTable, $sourceColumn, $targetTable, $targetColumn);
				$this->deleteSourceRows($property, $sourceTable);
			} catch (\databaseException $exception) {
				$connection->rollbackTransaction();
				throw $exception;
			}

			$connection->commitTransaction();
			return $this;
		}
	}