<?php
	namespace UmiCms\System\Trade\Stock\Balance\Attribute;

	use UmiCms\System\Orm\Entity\iMutator;
	use UmiCms\System\Orm\Entity\Attribute\Mutator as AbstractMutator;

	/**
	 * Класс мутатора атрибутов складского остатка
	 * @package UmiCms\System\Trade\Stock\Balance\Attribute;
	 */
	class Mutator extends AbstractMutator implements iMutator {}