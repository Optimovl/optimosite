<?php

	namespace UmiCms\System\Utils;

	/**
	 * Интерфейс URL
	 * @package UmiCms\System
	 */
	interface iUrl {

		/** @const string QUERY_IDENTIFIER идентификатор запроса url */
		const QUERY_IDENTIFIER = '?';

		/** @const string FRAGMENT_IDENTIFIER идентификатор фрагмента url */
		const FRAGMENT_IDENTIFIER = '#';

		/** @const string SCHEME_SUFFIX суффикс схемы  */
		const SCHEME_SUFFIX = '://';

		/** @const string COLON знак двоеточия */
		const COLON = ':';

		/** @const string PASSWORD_SUFFIX суффикс пароля */
		const PASSWORD_SUFFIX = '@';

		/**
		 * Возвращает схему
		 * @return string
		 */
		public function getScheme();

		/**
		 * Изменяет схему
		 * @param string $scheme схема
		 * @return $this
		 */
		public function setScheme($scheme);

		/**
		 * Возвращает хост
		 * @return string
		 */
		public function getHost();

		/**
		 * Изменяет хост
		 * @param string $host хост
		 * @return $this
		 */
		public function setHost($host);

		/**
		 * Возвращает порт
		 * @return int
		 */
		public function getPort();

		/**
		 * Изменяет порт
		 * @param int $port порт
		 * @return $this
		 */
		public function setPort($port);

		/**
		 * Возвращает пользователя
		 * @return string
		 */
		public function getUser();

		/**
		 * Изменяет пользователя
		 * @param string $user пользователь
		 * @return $this
		 */
		public function setUser($user);

		/**
		 * Возвращает пароль
		 * @return string
		 */
		public function getPass();

		/**
		 * Изменяет пароль
		 * @param string $pass пароль
		 * @return $this
		 */
		public function setPass($pass);

		/**
		 * Возвращает путь
		 * @return string
		 */
		public function getPath();

		/**
		 * Изменяеи путь
		 * @param string $path путь
		 * @return $this
		 */
		public function setPath($path);

		/**
		 * Возвращает запрос
		 * @return string
		 */
		public function getQuery();

		/**
		 * Изменяет запрос
		 * @param string $query
		 * @return $this
		 */
		public function setQuery($query);

		/**
		 * Возвращает запрос в виде ассоциативного массива
		 * @return array
		 */
		public function getQueryAsList();

		/**
		 * Возвращает фрагмент
		 * @return string
		 */
		public function getFragment();

		/**
		 * Изменяет фрагмент
		 * @param string $fragment
		 * @return $this
		 */
		public function setFragment($fragment);

		/**
		 * Возвращает адрес
		 * @return string
		 */
		public function getUrl();

		/**
		 * Возвращает адрес
		 * @return string
		 */
		public function __toString();

		/**
		 * Сливает текущий url с переданным и возвращает новый url
		 * @param iUrl $target
		 * @return $this
		 */
		public function merge(iUrl $target);
	}