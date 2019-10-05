<?php

	namespace UmiCms\System\MailTemplates;

	use iUmiObject;
	use MailTemplate;

	/** Парсер шаблона письма */
	interface iParser {

		/**
		 * Конструктор
		 * @param MailTemplate $template Шаблон
		 */
		public function __construct(MailTemplate $template);

		/**
		 * Возвращает обработанное содержимое шаблона, такое, что в нем (содержимом)
		 * заменены вставки идентификаторов полей на конкретные значения.
		 * В шаблоне могут содержаться вложенные шаблоны.
		 *
		 * @param array $params (многомерный) массив идентификаторов полей и их значений
		 *
		 * Вид массива: [
		 *   // переменная для основного шаблона
		 *   'status' => 'test status',
		 *
		 *   // переменные могут быть обрамлены знаком процента
		 *   '%order_number%' => 1,
		 *
		 *   // переменные для вложенных шаблонов расположены в массиве
		 *   '%items%' => [
		 *     0 => [
		 *       '%link%' => 'test link 1',
		 *       '%name%' => 'test name 1',
		 *     ],
		 *     1 => [
		 *       '%link%' => 'test link 2',
		 *       '%name%' => 'test name 2',
		 *     ]
		 *   ]
		 * ]
		 *
		 * @param iUmiObject[] $objectList Объекты, из которых могут подставляться значения в шаблон
		 * @return mixed
		 */
		public function parse(array $params = [], array $objectList = []);
	}
