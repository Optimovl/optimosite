<?php
	namespace UmiCms\System\Cache\Statical\Key\Validator;

	use UmiCms\System\Cache\Key\Validator\Factory as BaseFactory;

	/**
	 * Класс фабрики валидаторов ключей статического кеша
	 * @package UmiCms\System\Cache\Statical\Key\Validator
	 */
	class Factory extends BaseFactory implements iFactory {

		/** @inheritdoc */
		protected function getDefaultKeyValidatorName() {
			return (string) $this->getConfiguration()
				->get('cache', 'static.key-validator');
		}
	}