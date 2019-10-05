<?php
	namespace UmiCms\System\Trade\Offer\Price\Currency;

	/**
	 * Абстрактный класс любимой валюты
	 * @package UmiCms\System\Trade\Offer\Price\Currency
	 */
	abstract class Favorite implements iFavorite {

		/** @inheritdoc */
		abstract public function getId();

		/** @inheritdoc */
		abstract public function setId($id);
	}