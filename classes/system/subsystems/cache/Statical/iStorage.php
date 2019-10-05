<?php

	namespace UmiCms\System\Cache\Statical;

	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;

	/**
	 * Интерфейс хранилища статического кеша
	 * @package UmiCms\System\Cache\Statical
	 */
	interface iStorage {

		/**
		 * Конструктор
		 * @param \iConfiguration $config конфигурация
		 * @param FileFactory $fileFactory фабрика файлов
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 */
		public function __construct(\iConfiguration $config, FileFactory $fileFactory, DirectoryFactory $directoryFactory);

		/**
		 * Сохраняет содержимое страницы в кеш
		 * @param string $path адрес страницы
		 * @param string $content содержимое страницы
		 * @return bool
		 */
		public function save($path, $content);

		/**
		 * Загружает содержимое страниц из кеша
		 * @param string $path адрес страницы
		 * @return bool|string
		 */
		public function load($path);

		/**
		 * Удаляет содержимое страницы из кеша
		 * @param string $path адрес страницы
		 * @return bool
		 */
		public function delete($path);

		/**
		 * Удаляет содержимое страницы из кеша вместе с содержимым страниц с сходным адресом.
		 * Сходный адрес - тот же адрес, но с другим значением http query.
		 * @param string $path адрес страницы
		 * @return bool
		 */
		public function deleteForEveryQuery($path);

		/**
		 * Очищает хранилище
		 * @return bool
		 */
		public function flush();

		/**
		 * Возвращает время жизни кеша
		 * @return int
		 */
		public function getTimeToLive();
	}
