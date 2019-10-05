<?php

	/** Трейт работника с реестром системы */
	trait tUmiRegistryInjector {

		/** @var \iRegedit|null $registry экземпляр класса для работы с реестром */
		private $registry;

		/**
		 * Устанавливает экземпляр класса для работы с реестром
		 * @param \iRegedit $registry экземпляр класса для работы с реестром
		 * @return \iUmiRegistryInjector|tUmiRegistryInjector
		 */
		public function setRegistry(\iRegedit $registry) {
			$this->registry = $registry;
			return $this;
		}

		/**
		 * Возвращает экземпляр класса для работы с реестром
		 * @return \iRegedit
		 * @throws \Exception
		 */
		public function getRegistry() {
			if (!$this->registry instanceof \iRegedit) {
				throw new \RequiredPropertyHasNoValueException('You should inject iRegedit first');
			}

			return $this->registry;
		}
	}
