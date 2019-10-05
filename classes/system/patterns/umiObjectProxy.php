<?php

	class umiObjectProxy {

		protected $object;

		protected function __construct(iUmiObject $object) {
			$this->object = $object;
		}

		public function getId() {
			return $this->object->getId();
		}

		public function setName($name) {
			$this->object->setName($name);
		}

		public function getName() {
			return $this->object->getName();
		}

		public function setValue($propName, $value) {
			return $this->object->setValue($propName, $value);
		}

		public function getValue($propName) {
			return $this->object->getValue($propName);
		}

		public function isFilled() {
			return $this->object->isFilled();
		}

		public function getObject() {
			return $this->object;
		}

		public function commit() {
			return $this->object->commit();
		}

		public function delete() {
			$objects = umiObjectsCollection::getInstance();
			return $objects->delObject($this->getId());
		}

		public function __get($prop) {
			switch ($prop) {
				case 'id':
					return $this->getId();
				case 'name':
					return $this->getName();
				default:
					return $this->getValue($prop);
			}
		}

		/**
		 * Проверяет наличие свойства
		 * @param string $prop имя свойства
		 * @return bool
		 */
		public function __isset($prop) {
			switch ($prop) {
				case 'id':
				case 'name': {
					return true;
				}
				default : {
					return ($this->object->getPropByName($prop) instanceof iUmiObjectProperty);
				}
			}
		}

		public function __set($prop, $value) {
			switch ($prop) {
				case 'name':
					$this->setName($value);
					return;

				default:
					$this->setValue($prop, $value);
					return;
			}
		}

		public function __destruct() {
			$this->object->commit();
		}

		/**
		 * Валидирует гуид типа данных замещаемого объекта
		 * @param $typeGUID
		 * @return $this
		 * @throws WrongObjectTypeForProxyConstructionException
		 */
		protected function validateObjectTypeGUID($typeGUID) {
			$objectTypeGUID = $this->getObject()
				->getTypeGUID();

			if ($objectTypeGUID != $typeGUID) {
				$exceptionLabel = getLabel('error-cannot-create-proxy-for-object-with-guid');
				$exceptionMessage = sprintf($exceptionLabel, $objectTypeGUID, get_class($this));
				throw new \WrongObjectTypeForProxyConstructionException($exceptionMessage);
			}

			return $this;
		}
	}

