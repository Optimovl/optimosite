<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Класс удаления значений реестра.
	 * Класс игнорирует удаление корневых ключей (@see Registry::$blackList), так как это может привести к полной
	 * или частичной неработоспособности системы.
	 * Ключи, соответствующие записям об установленных модулях или расширениях игнорируются, для удаления модулей или
	 * расширений необходимо воспользоваться существующими методами
	 * (@see config::del_module(), \UmiCms\System\Extension\Registry::delete())
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Registry extends Demolisher {

		/** @var \iRegedit $registry реестр */
		private $registry;

		/** @var array $keyBlackList список ключей, которые нельзя удалять */
		private $keyBlackList = [
			self::MODULES_PATH,
			self::EXTENSION_PATH,
			'settings',
			'umiMessages'
		];

		/** @var array $keyPartBlackList список частей ключей, которые нельзя удалять */
		private $keyPartBlackList = [
			'default_method',
			'default_method_admin',
			'config',
			'per_page'
		];

		/** @const string MODULES_PATH ключ модулей */
		const MODULES_PATH = 'modules';

		/** @const string EXTENSION_PATH ключ расширений */
		const EXTENSION_PATH = 'extensions';

		/**
		 * Конструктор
		 * @param \iRegedit $registry реестр
		 */
		public function __construct(\iRegedit $registry) {
			$this->registry = $registry;
		}

		/** @inheritdoc */
		protected function execute() {
			$registry = $this->getRegistry();

			foreach ($this->getPathList() as $path) {
				if (!$registry->contains($path) || $this->isForbidden($path)) {
					$this->pushLog(sprintf('Registry path "%s" was ignored', $path));
					continue;
				}

				$registry->delete($path);
				$this->pushLog(sprintf('Registry path "%s" was deleted', $path));
			}
		}

		/**
		 * Определяет заблокировано ли удаление ключа
		 * @param string $path проверяемый ключ
		 * @return bool
		 */
		private function isForbidden($path) {
			$path = trim($path, '/');

			if (in_array($path, $this->keyBlackList)) {
				return true;
			}

			foreach ($this->keyPartBlackList as $keyPart) {
				if (contains($path, $keyPart)) {
					return true;
				}
			}

			if (!contains($path, self::MODULES_PATH) && !contains($path, self::EXTENSION_PATH)) {
				return false;
			}

			$parts = explode('/', $path);

			if (umiCount($parts) > 2) {
				return false;
			}

			if ($this->getRegistry()->contains($path)) {
				return true;
			}

			return false;
		}

		/**
		 * Возвращает список удаляемых ключей реестра
		 * @return string[]
		 */
		private function getPathList() {
			return $this->getNodeValueList('/umidump/registry/key/@path');
		}

		/**
		 * Возвращает реестр
		 * @return \iRegedit
		 */
		private function getRegistry() {
			return $this->registry;
		}
	}
