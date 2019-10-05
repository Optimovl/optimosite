<?php

	/** Трейт работника с директориями */
	trait tUmiDirectoriesInjector {

		/** @var \iUmiDirectory $directoriesHandler экземпляр класса обработчика директорий */
		private $directoriesHandler;

		/**
		 * Устанавливает экземпляр обработчика директорий
		 * @param \iUmiDirectory $directoriesHandler экземпляр обработчика директорий
		 * @return $this
		 */
		public function setDirectoriesHandler(\iUmiDirectory $directoriesHandler) {
			$this->directoriesHandler = $directoriesHandler;
			return $this;
		}

		/**
		 * Возвращает экземпляр обработчика директорий
		 * @return \iUmiDirectory
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getDirectoriesHandler() {
			if (!$this->directoriesHandler instanceof \iUmiDirectory) {
				throw new \RequiredPropertyHasNoValueException('You should inject \iUmiDirectory first');
			}

			return $this->directoriesHandler;
		}
	}
