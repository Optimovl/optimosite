<?php

	namespace UmiCms\System\Solution;

	use UmiCms\System\Registry\Part;

	/**
	 * Реестр решений.
	 * Не учитывает решения, установленные вручную.
	 * @package UmiCms\System\Solution
	 */
	class Registry extends Part implements iRegistry {

		/** @const string PATH_PREFIX префикс пути для ключей */
		const PATH_PREFIX = '//solutions';

		/** @inheritdoc */
		public function __construct(\iRegedit $storage) {
			parent::__construct($storage);
			parent::setPathPrefix(self::PATH_PREFIX);
		}

		/** @inheritdoc */
		public function append($name, $domainId) {
			return $this->set($domainId, $name);
		}

		/** @inheritdoc */
		public function isAppended($name) {
			return in_array($name, $this->getList());
		}

		/** @inheritdoc */
		public function isAppendedToDomain($name, $domainId) {
			if (!$this->contains($domainId)) {
				return false;
			}

			return $this->get($domainId) == $name;
		}

		/** @inheritdoc */
		public function deleteFromDomain($id) {
			return $this->delete($id);
		}

		/** @inheritdoc */
		public function getByDomain($id) {
			return $this->get($id);
		}

		/** @inheritdoc */
		public function deleteAll() {
			foreach (parent::getList() as $key) {
				$this->delete($key);
			}

			return $this;
		}

		/** @inheritdoc */
		public function getList() {
			return array_filter(parent::getList(), function($solutionName) {
				return !is_numeric($solutionName);
			});
		}

		/** @inheritdoc */
		public function setPathPrefix($prefix) {
			return $this;
		}
	}