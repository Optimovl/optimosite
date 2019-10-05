<?php
	namespace UmiCms\System\Trade\Offer\Price;

	use UmiCms\System\Orm\Entity;

	/**
	 * Класс типа цены
	 * @package UmiCms\System\Trade\Offer\Price
	 */
	class Type extends Entity implements iType {

		/** @var string|null $name название */
		protected $name;

		/** @var string|null $title заголовок */
		protected $title;

		/** @var bool $isDefault группа является группой по умолчанию */
		protected $isDefault = false;

		/** @inheritdoc */
		public function getName() {
			return $this->name;
		}

		/** @inheritdoc */
		public function setName($name) {
			if (!is_string($name) || isEmptyString($name)) {
				throw new \ErrorException('Incorrect price type name given');
			}

			return $this->setDifferentValue('name', $name);
		}

		/** @inheritdoc */
		public function getTitle() {
			return $this->title;
		}

		/** @inheritdoc */
		public function setTitle($title) {
			if (!is_string($title) || isEmptyString($title)) {
				throw new \ErrorException('Incorrect price type title given');
			}

			return $this->setDifferentValue('title', $title);
		}

		/** @inheritdoc */
		public function isDefault() {
			return $this->isDefault;
		}

		/** @inheritdoc */
		public function setDefault($flag = true) {
			if (!is_bool($flag)) {
				throw new \ErrorException('Incorrect price type default flag given');
			}

			return $this->setDifferentValue('isDefault', $flag);
		}

		/** @inheritdoc */
		public function getPriceViewType() {
			return 'number';
		}

		/** @inheritdoc */
		public function getPriceTitle() {
			return getLabel('label-trade-offer-price', false, $this->getTitle());
		}
	}