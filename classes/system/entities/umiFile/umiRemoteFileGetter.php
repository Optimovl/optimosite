<?php

	/** Класс для получения содержимого удаленного файла любыми доступными средствами */
	class umiRemoteFileGetter {

		/** Размер "пакета" */
		const READ_PIECE_SIZE = 4096;

		/**
		 * Возвращает содержимое удаленного файла или записывает его на диск
		 * @param string $remoteName URL удаленного файла
		 * @param bool|string $localName Путь для сохранения содержимого
		 * @param array|bool $addHeaders Дополнительные HTTP-заголовки
		 * @param array|bool $postVars Переменные, передаваемые методом POST
		 * @param boolean $returnHeaders Возвращать заголовки ответа
		 * @param bool|string $method Метод запроса
		 * @param bool|int $timeout Количество секунд ожидания при попытке соединения
		 * @return string|umiFile
		 * @throws Exception
		 * @throws umiRemoteFileGetterException
		 */
		public static function get(
			$remoteName,
			$localName = false,
			$addHeaders = false,
			$postVars = false,
			$returnHeaders = false,
			$method = false,
			$timeout = false
		) {
			$resultException = null;

			if (!contains($remoteName, '://')) {
				$content = @file_get_contents($remoteName);
				if ($content === false) {
					throw new umiRemoteFileGetterException('Local file does not exist or can not be opened');
				}

				if ($localName === false) {
					return $content;
				}

				@file_put_contents($localName, $content);
				return true;
			}

			try {
				return self::curlGet($remoteName, $localName, $addHeaders, $postVars, $returnHeaders, $method, $timeout);
			} catch (umiRemoteFileGetterException $e) {
				$resultException = $e;
			}

			if ($resultException != null) {
				throw $resultException;
			}

			return null;
		}

		private static function curlGet(
			$remoteName,
			$localName,
			$addHeaders = false,
			$postVars = false,
			$returnHeaders = false,
			$method = false,
			$timeout = false
		) {
			if (!function_exists('curl_init')) {
				throw new umiRemoteFileGetterException('CURL not supported');
			}

			$ch = curl_init($remoteName);

			if (!$ch) {
				throw new umiRemoteFileGetterException('CURL init failed');
			}

			$fp = null;

			if ($localName === false) {
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			} else {
				$fp = fopen($localName, 'w');

				if (!$fp) {
					throw new umiRemoteFileGetterException('Can not open target file');
				}

				curl_setopt($ch, CURLOPT_FILE, $fp);
			}

			if ($returnHeaders) {
				curl_setopt($ch, CURLOPT_HEADER, 1);
			}

			if (!empty($postVars) && is_array($postVars)) {
				curl_setopt($ch, CURLOPT_POST, umiCount($postVars));
				$content = is_array($postVars) ? http_build_query($postVars, '', '&') : $postVars;
				curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
			}

			if (is_array($addHeaders)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, explode("\r\n", self::buildHeaderString($addHeaders)));
			}

			if ($method && $method != 'GET' && $method != 'POST') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			}

			if ($timeout) {
				curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
			}

			$result = curl_exec($ch);

			$errorMessage = curl_error($ch);

			if ($errorMessage) {
				throw new umiRemoteFileGetterException('Curl error - ' . $errorMessage);
			}

			curl_close($ch);

			if ($localName === false) {
				return $result;
			}

			fclose($fp);
			return new umiFile($localName);
		}

		private static function buildHeaderString($addHeaders) {
			$headerLines = [];

			if (!empty($addHeaders)) {
				foreach ($addHeaders as $name => $value) {
					$headerLines[] = $name . ': ' . $value;
				}
			}

			return empty($headerLines) ? '' : (implode("\r\n", $headerLines) . "\r\n");
		}
	}

