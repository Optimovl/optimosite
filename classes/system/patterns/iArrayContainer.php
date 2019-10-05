<?php

	namespace UmiCms\System\Patterns;

	/**
	 * Интерфейс контейнера массива
	 * @package UmiCms\System\Patterns
	 */
	interface iArrayContainer extends \iMapContainer {

		/**
		 * Конструктор
		 * @param array $array массив
		 */
		public function __construct(array $array = []);

		/**
		 * Устанавливает значение массива
		 * @param string $key ключ
		 * @param mixed $value значение
		 * @return iArrayContainer
		 */
		public function set($key, $value);

		/**
		 * Удаляет значение(я) массива по ключам(у)
		 * @param string|array $keyList ключ или список ключей
		 * @return iArrayContainer
		 */
		public function del($keyList);

		/**
		 * Удаляет все содержимое массива
		 * @return iArrayContainer
		 */
		public function clear();

		/**
		 * Алиас iSuperGlobalArrayContainer::set()
		 * @param string $key ключ
		 * @param mixed $value значение
		 * @return iArrayContainer
		 */
		public function __set($key, $value);

		/**
		 * Алиас iSuperGlobalArrayContainer::del()
		 * @param string|array $keyList ключ или список ключей
		 * @return iArrayContainer
		 */
		public function __unset($keyList);
	}
