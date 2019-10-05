<?php

	namespace UmiCms\Classes\System\Utils\Captcha\Strategies;

	use UmiCms\Service;

	/**
	 * Абстрактный класс стратегии работы с капчей.
	 * Все стратегии работают с текущим сайтом (текущий домен + языковая версия).
	 */
	abstract class CaptchaStrategy {

		/**
		 * Генерирует код вызова капчи
		 * @param string $template = "default" шаблон для генерации кода капчи
		 * @param string $inputId = "sys_captcha" id инпута для капчи
		 * @param string $captchaHash = "" md5-хеш кода, который будет выведен на картинке
		 * @param string $captchaId идентификатор CAPTCHA
		 * @return array|string результат обработки в зависимости от текущего шаблонизатора
		 */
		abstract public function generate($template, $inputId, $captchaHash, $captchaId);

		/**
		 * Проверяет валидность капчи
		 * @return bool
		 */
		abstract public function isValid();

		/**
		 * Возвращает строковой идентификатор стратегии капчи
		 * @return string
		 */
		abstract public function getName();

		/**
		 * Проверяет необходимость вывода капчи
		 * @return bool
		 */
		public function isRequired() {
			return Service::Auth()->isLoginAsGuest();
		}

		/**
		 * Получает объект отрисовки капчи
		 * @return \captchaDrawer объект отрисовки капчи
		 * @throws \coreException
		 */
		public function getDrawer() {
			return $this->findDrawer('null');
		}

		/**
		 * Находит класс отрисовки капчи и возвращает экземпляр
		 * @param string $name название файла с классом
		 * @return mixed
		 * @throws \coreException
		 */
		protected function findDrawer($name) {
			$className = $name . 'CaptchaDrawer';

			if (class_exists($className)) {
				return new $className();
			}

			$path = CURRENT_WORKING_DIR . '/classes/system/utils/captcha/drawers/' . $name . '.php';

			if (!is_file($path)) {
				throw new \coreException("Captcha image drawer named \"{$name}\" not found");
			}

			require_once $path;

			if (!class_exists($className)) {
				throw new \coreException("Class \"{$className}\" not found in \"{$path}\"");
			}

			return new $className();
		}
	}
