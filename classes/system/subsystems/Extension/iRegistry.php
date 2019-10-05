<?php

	namespace UmiCms\System\Extension;

	use UmiCms\System\Registry\iPart;

	/**
	 * Интерфейс реестра расширений
	 * @package UmiCms\System\Extension
	 */
	interface iRegistry extends iPart {

		/**
		 * Добавляет расширение в реестр
		 * @param string $name имя расширения
		 * @return $this
		 */
		public function append($name);
	}
