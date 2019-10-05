<?php

	use UmiCms\Service;

	/** Тип экспорта заказов в формате CommerceML */
	class ordersCommerceMLExporter extends umiExporter {

		/**
		 * @var string Кастомный xsl-шаблон, используемый для преобразования
		 * при экспорте заказов (имя файла шаблона без расширения)
		 */
		private $customXslTemplate;

		/**
		 * Устанавливает кастомный xsl-шаблон экспорта
		 * @param string $fileName Имя файла шаблона
		 */
		public function setCustomXslTemplate($fileName) {
			if (preg_match('/[a-z0-9_\-\\/]+/i', $fileName)) {
				$this->customXslTemplate = trim($fileName);
			}
		}

		/** @inheritdoc */
		public function setOutputBuffer() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->charset('windows-1251');
			$buffer->contentType('text/xml');
			return $buffer;
		}

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			$orders = $this->getOrders();
			$umiDump = $this->getUmiDump($orders, 'CommerceML2');

			$templateFileName = is_string($this->customXslTemplate) ? $this->customXslTemplate : $this->type;
			$template = CURRENT_WORKING_DIR . '/xsl/export/' . $templateFileName . '.xsl';
			if (!is_file($template)) {
				throw new publicException("Can't load xsl template file {$template}");
			}

			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = XML_FORMAT_OUTPUT;
			$doc->loadXML($umiDump);

			$templater = umiTemplater::create('XSLT', $template);
			$result = $templater->parse($doc);
			$result = str_replace(
				'<?xml version="1.0" encoding="utf-8"?>',
				'<?xml version="1.0" encoding="windows-1251"?>',
				$result
			);
			$result = mb_convert_encoding($result, 'CP1251', 'UTF-8');

			return $result;
		}

		/**
		 * Возвращает заказы, которые нужно экспортировать
		 * @return iUmiObject[]
		 */
		private function getOrders() {
			$orders = new selector('objects');
			$orders->types('object-type')->name('emarket', 'order');
			$orders->where('number')->more(0);
			$orders->where('customer_id')->more(0);
			$orders->where('order_date')->more(0);
			$orders->where('total_amount')->more(0);
			$orders->where('need_export')->equals(1);
			$orders->order('order_date')->asc();

			$umiConfig = mainConfiguration::getInstance();
			if ($umiConfig->get('modules', 'exchange.commerceML.ordersByDomains')) {
				$currentDomainId = Service::DomainDetector()->detectId();
				$orders->where('domain_id')->equals($currentDomainId);
			}

			$limit = $umiConfig->get('modules', 'exchange.commerceML.ordersLimit');
			if ($limit) {
				$orders->limit(0, $limit);
			}

			return $orders->result();
		}

		/**
		 * Возвращает экспортированные заказы в формате UMIDUMP
		 * @param iUmiObject[] $orders заказы
		 * @param bool|string $sourceName название источника экспорта
		 * @return string
		 */
		protected function getUmiDump($orders, $sourceName = false) {
			if (!$sourceName) {
				$sourceName = $this->getSourceName();
			}

			$exporter = $this->createXmlExporter($sourceName);
			$exporter->addObjects($orders);
			$exporter->setIgnoreRelations();
			$result = $exporter->execute();

			return $result->saveXML();
		}
	}
