<?php

	namespace UmiCms\System\Registry;

	/**
	 * Интерфейс реестра, являющегося частью стандартного реестра UMI.CMS
	 * @package UmiCms\System\Regedit
	 */
	interface iPart {

		/**
		 * Конструктор
		 * @param \iRegedit $storage хранилище
		 */
		public function __construct(\iRegedit $storage);

		/**
		 * Устанавливает префикс пути для ключей
		 * @param string $prefix префикс пути для ключей
		 * @return $this
		 */
		public function setPathPrefix($prefix);

		/**
		 * Возвращает значение
		 * @param string $key ключ
		 * @return string|null
		 */
		public function get($key);

		/**
		 * Устанавливает значение
		 * @param string $key ключ
		 * @param string $value значение
		 * @return $this
		 */
		public function set($key, $value);

		/**
		 * Возвращает список значений
		 * @return string[]
		 */
		public function getList();

		/**
		 * Определяет существует ли значение
		 * @param string $key ключ
		 * @return bool
		 */
		public function contains($key);

		/**
		 * Удаляет значение
		 * @param string $key ключ
		 * @return $this
		 */
		public function delete($key);
	}
