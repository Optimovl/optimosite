<?php

	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;

	use UmiCms\System\Orm\Entity\iExchange;
	use UmiCms\System\Import\UmiDump\Demolisher;
	use UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\iFactory as SourceIdBinderFactory;

	/**
	 * Класс удаления особых сущностей (редиректов, сущностей модуля "Онлай-запись", шаблонов писем etc).
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Type
	 */
	class Entity extends Demolisher {

		/**
		 * @var SourceIdBinderFactory $entitySourceIdBinderFactory экземпляр класса управления связями между
		 * идентификаторами особых сущностей
		 */
		private $entitySourceIdBinderFactory;

		/** @var \iServiceContainer $serviceContainer контейнер сервисов */
		private $serviceContainer;

		/** @var \iCmsController $cmsController cms контроллер */
		private $cmsController;

		/**
		 * Конструктор
		 * @param SourceIdBinderFactory $entitySourceIdBinderFactory экземпляр класса управления связями между
		 * идентификаторами особых сущностей
		 * @param \iServiceContainer $serviceContainer контейнер сервисов
		 * @param \iCmsController $cmsController cms контроллер
		 */
		public function __construct(
			SourceIdBinderFactory $entitySourceIdBinderFactory,
			\iServiceContainer $serviceContainer,
			\iCmsController $cmsController
		) {
			$this->entitySourceIdBinderFactory = $entitySourceIdBinderFactory;
			$this->serviceContainer = $serviceContainer;
			$this->cmsController = $cmsController;
		}

		/** @inheritdoc */
		protected function execute() {
			$serviceContainer = $this->getServiceContainer();
			$sourceId = $this->getSourceId();
			$entitySourceIdBinder = $this->getEntitySourceIdBinderFactory()
				->create($sourceId);

			foreach ($this->getEntityExtIdTree() as $serviceName => $entityExtIdList) {
				/** @var iExchange|\iUmiCollection|\iUmiConstantMapInjector $service */
				$service = $serviceContainer->get($serviceName);

				if ($service instanceof iExchange) {
					$service->demolishByExternalIdList($entityExtIdList, $entitySourceIdBinder);
					continue;
				}

				$table = $service->getMap()->get('EXCHANGE_RELATION_TABLE_NAME');

				foreach ($entityExtIdList as $entityExtId) {
					$entityId = $entitySourceIdBinder->getInternalId($entityExtId, $table);

					if ($entityId === null || $entitySourceIdBinder->isRelatedToAnotherSource($entityExtId, $table)) {
						$this->pushLog(sprintf('Entity "%s" of "%s" was ignored', $entityExtId, $serviceName));
						continue;
					}

					$service->delete(['id' => $entityId]);
					$this->pushLog(sprintf('Entity "%s" of "%s" was deleted', $entityId, $serviceName));
				}
			}
		}

		/**
		 * Возвращает список внешних идентификаторов особых сущностей, сгруппированыный по имени сервиса
		 * @return array
		 *
		 * [
		 *      serviceName => [
		 *          entityExtId
		 *      ]
		 * ]
		 */
		private function getEntityExtIdTree() {
			$result = [];
			$cmsController = $this->getCmsController();

			/** @var \DOMElement $entity */
			foreach ($this->parse('/umidump/entities/entity') as $entity) {
				$id = $entity->getAttribute('id');
				$service = $entity->getAttribute('service');

				if (!$id || !$service) {
					continue;
				}

				$result[$service][] = $id;
				$module = (string) $entity->getAttribute('module');

				if (empty($module)) {
					continue;
				}

				$cmsController->getModule($module);
			}

			return $result;
		}

		/**
		 * Возвращает экземпляр класса управления связями между идентификаторами особых сущностей
		 * @return SourceIdBinderFactory
		 */
		private function getEntitySourceIdBinderFactory() {
			return $this->entitySourceIdBinderFactory;
		}

		/**
		 * Возвращает контейнер сервисов
		 * @return \iServiceContainer
		 */
		private function getServiceContainer() {
			return $this->serviceContainer;
		}

		/**
		 * Возвращает cms контроллер
		 * @return \iCmsController
		 */
		private function getCmsController() {
			return $this->cmsController;
		}
	}
