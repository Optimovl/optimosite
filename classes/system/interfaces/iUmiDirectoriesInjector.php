<?php

	/** Интерфейс работника с директориями */
	interface iUmiDirectoriesInjector {

		/**
		 * Устанавливает экземпляр обработчика директорий
		 * @param \iUmiDirectory $directoriesHandler экземпляр обработчика директорий
		 * @return iUmiDirectoriesInjector
		 */
		public function setDirectoriesHandler(\iUmiDirectory $directoriesHandler);

		/**
		 * Возвращает экземпляр обработчика директорий
		 * @return \iUmiDirectory
		 * @throws \RequiredPropertyHasNoValueException
		 */
		public function getDirectoriesHandler();
	}
