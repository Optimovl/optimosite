<?php

	namespace UmiCms\System\Solution;

	/**
	 * Интерфейс реестра решений
	 * @package UmiCms\System\Solution
	 */
	interface iRegistry {

		/**
		 * Добавляет решение
		 * @param string $name имя решения
		 * @param int $domainId идентификатор домена
		 * @return $this
		 */
		public function append($name, $domainId);

		/**
		 * Определяет добавлено ли решение
		 * @param string $name имя решения
		 * @return bool
		 */
		public function isAppended($name);

		/**
		 * Определяет добавлено ли решение в заданный домен
		 * @param string $name имя решения
		 * @param int $id идентификатор домена
		 * @return bool
		 */
		public function isAppendedToDomain($name, $id);

		/**
		 * Удаляет решение с заданного домена
		 * @param int $id идентификатор домена
		 * @return $this
		 */
		public function deleteFromDomain($id);

		/**
		 * Возвращает решение, установленное для домена
		 * @param int $id идентификатор домена
		 * @return string|null
		 */
		public function getByDomain($id);

		/**
		 * Очищает репозиторий
		 * @return $this
		 */
		public function deleteAll();

		/**
		 * Возвращает список добавленных решений
		 * @return array
		 */
		public function getList();
	}