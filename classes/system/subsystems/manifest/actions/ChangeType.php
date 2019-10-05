<?php

	namespace UmiCms\Manifest\Migrate\Field;

	use UmiCms\Service;
	use UmiCms\System\Data\Field\Type\iMigration;

	/**
	 * Класс команды изменения типов полей
	 * @package UmiCms\Manifest\Migrate\Field
	 */
	class ChangeTypeAction extends \Action {

		/**
		 * @inheritdoc
		 * @throws \RuntimeException
		 */
		public function execute() {
			$targetList = $this->getParam('target');

			if (!is_array($targetList)) {
				throw new \RuntimeException('Incorrect target list given: ' . var_export($targetList, true));
			}

			$migration = $this->getMigration();

			foreach ($targetList as $target) {
				if (!is_array($target)) {
					throw new \RuntimeException('Incorrect target given: ' . var_export($target, true));
				}

				$migration->migrate($target);
			}

			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			$targetList = $this->getParam('target');

			if (!is_array($targetList)) {
				return $this;
			}

			$migration = $this->getMigration();

			foreach ($targetList as $target) {
				if (!is_array($target)) {
					continue;
				}

				try {
					$migration->rollback($target);
				} catch (\Exception $exception) {
					continue;
				}
			}

			return $this;
		}

		/**
		 * Возвращает миграциию типов полей
		 * @return iMigration
		 */
		private function getMigration() {
			return Service::get('FieldTypeMigration');
		}
	}