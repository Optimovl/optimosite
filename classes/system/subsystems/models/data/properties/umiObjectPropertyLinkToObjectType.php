<?php

	/**
	 * Этот класс служит для управления полем объекта
	 * Обрабатывает тип поля "Ссылка на тип данных".
	 */
	class umiObjectPropertyLinkToObjectType extends umiObjectPropertyInt {

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

			if ($newValue > 0 && !umiObjectTypesCollection::getInstance()->getType($newValue)) {
				return false;
			}

			return $oldValue !== $newValue;
		}
	}
