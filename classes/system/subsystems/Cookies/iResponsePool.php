<?php

	namespace UmiCms\System\Cookies;

	/**
	 * Интерфейс списка кук, которые требуется отправить клиенту
	 * @package UmiCms\System\Cookies
	 */
	interface iResponsePool {

		/**
		 * Добавляет куку в список
		 * @param iCookie $cookie
		 * @return iResponsePool
		 */
		public function push(iCookie $cookie);

		/**
		 * Извлекает куку из списка
		 * @param string $name имя куки
		 * @return iCookie|null
		 */
		public function pull($name);

		/**
		 * Определяет есть ли кука в списке
		 * @param string $name имя куки
		 * @return bool
		 */
		public function isExists($name);

		/**
		 * Возвращает куку из списка по имени
		 * @param string $name имя куки
		 * @return iCookie|null
		 */
		public function get($name);

		/**
		 * Возвращает список кук
		 * @return iCookie[]
		 */
		public function getList();

		/**
		 * Удаляет куку из списка
		 * @param string $name имя куки
		 * @return iResponsePool
		 */
		public function remove($name);

		/**
		 * Очищает список кук
		 * @return iResponsePool
		 */
		public function clear();
	}
