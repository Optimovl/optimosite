<?php

	use UmiCms\Service;

	/**
	 * Class core
	 * TODO PHPDoc
	 */
	class core {

		/**
		 * @param $method_name
		 * @param $args
		 * @return mixed
		 */
		public function cms_callMethod($method_name, $args) {
			return call_user_func_array([$this, $method_name], $args);
		}

		/**
		 * @param $method_name
		 * @return bool
		 */
		public function isMethodExists($method_name) {
			return method_exists($this, $method_name);
		}

		/**
		 * @param $method
		 * @param $args
		 * @throws publicException
		 */
		public function __call($method, $args) {
			throw new publicException('Method ' . get_class($this) . '::' . $method . " doesn't exist");
		}

		/**
		 * @param string $template
		 * @param bool $isFull
		 * @param int $offsetLeft
		 * @param int $offsetRight
		 * @return mixed
		 */
		public function navibar($template = 'default', $isFull = true, $offsetLeft = 0, $offsetRight = 0) {
			if (!$template) {
				$template = 'default';
			}
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();

			list(
				$tpl_block, $tpl_block_empty, $tpl_item, $tpl_item_a, $tpl_quant
				) = def_module::loadTemplates(
				'content/navibar/' . $template,
				'navibar',
				'navibar_empty',
				'element',
				'element_active',
				'quantificator'
			);

			$parents = $hierarchy->getAllParents($currentElementId);
			$parents[] = $currentElementId;

			$items = [];
			foreach ($parents as $elementId) {
				if (!$elementId) {
					continue;
				}

				$element = $hierarchy->getElement($elementId);
				if ($element instanceof iUmiHierarchyElement) {
					$items[] = $element;
				}
			}

			$sz = umiCount($items) - $offsetRight;
			$items_arr = [];
			for ($i = (int) $offsetLeft; $i < $sz; $i++) {
				$element = $items[$i];
				$tpl_item_current = (!$isFull && $i == ($sz - 1)) ? $tpl_item_a : $tpl_item;

				$item_arr = def_module::parseTemplate(
					$tpl_item_current,
					[
						'attribute:id' => $element->id,
						'attribute:link' => $element->link,
						'xlink:href' => 'upage://' . $element->id,
						'node:text' => $element->name,
					],
					$element->id
				);

				if (is_string($item_arr) && ($i != ($sz - 1))) {
					$item_arr .= $tpl_quant;
				}

				$items_arr[] = $item_arr;
			}

			if ($sz == 0) {
				$tpl_block = $tpl_block_empty;
			}

			return def_module::parseTemplate($tpl_block, [
				'items' => ['nodes:item' => $items_arr],
				'void:elements' => $items_arr,
			]);
		}

		/**
		 * @param string $template
		 * @return string
		 */
		public function insertCut($template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			$pages = getRequest('cut_pages');
			if ($pages > 1) {
				return "%system numpages('{$pages}', '1', '{$template}', 'cut')%";
			}
		}

		/** @return string */
		public function curr_module() {
			$cmsController = cmsController::getInstance();
			$module = $cmsController->getCurrentModule();
			$method = $cmsController->getCurrentMethod();

			if ($module == 'config' && $method == 'mainpage') {
				return '';
			}

			return $module;
		}

		/**
		 * @param $typeId
		 * @return array
		 */
		public function getTypeEditLinkXml($typeId) {
			if (system_is_allowed('data', 'type_edit')) {
				$objectTypes = umiObjectTypesCollection::getInstance();
				$type = $objectTypes->getType($typeId);

				if ($type) {
					return [
						'type' => [
							'node:name' => $type->getName(),
							'attribute:link' => $this->pre_lang . "/admin/data/type_edit/{$typeId}/",
						],
					];
				}
			}
		}

		/**
		 * @param bool $filename
		 * @return array|bool
		 * @throws coreException
		 */
		public function importSkinXsl($filename = false) {
			static $emptyResult = false;

			$cmsController = cmsController::getInstance();
			$systemAdminTemplatesSource = CURRENT_WORKING_DIR . '/styles/skins/' . system_get_skinName();
			$customAdminTemplatesSource = $cmsController->getResourcesDirectory() . 'admin';
			$moduleName = $cmsController->getCurrentModule();

			if ($filename) {
				$templateLocalPath = '/data/modules/' . $moduleName . '/' . $filename;
			} else {
				$module = $cmsController->getModule($moduleName);
				$dataType = ($module instanceof def_module) ? $module->dataType : null;
				$actionType = ($module instanceof def_module) ? $module->actionType : null;

				if ($actionType == 'create') {
					$actionType = 'modify';
				}

				$templateLocalPath = '/data/' . $dataType . '.' . $actionType . '.xsl';
			}

			$customTemplatePath = $customAdminTemplatesSource . str_replace('custom', '', $templateLocalPath);
			$systemTemplatePath = $systemAdminTemplatesSource . $templateLocalPath;

			$path = !file_exists($customTemplatePath) ? $systemTemplatePath : $customTemplatePath;

			$emptyResultTemplatePath = $systemAdminTemplatesSource . '/empty.xsl';

			if (!$emptyResult) {
				if (!file_exists($emptyResultTemplatePath)) {
					throw new coreException("Empty template is required. Not found in '{$emptyResultTemplatePath}'");
				}
				$emptyResult = ['plain:result' => file_get_contents($emptyResultTemplatePath)];
			}

			if (checkFileForReading($path, ['xsl'])) {
				$result = file_get_contents($path);
				return ['plain:result' => $result];
			}

			return $emptyResult;
		}

		/**
		 * Импорт xsl-файлов расширяющих административную панель.
		 * @param string|null $mode
		 * @return array
		 * @throws coreException
		 */
		public function importExtSkinXsl($mode = null) {
			$cmsController = cmsController::getInstance();

			$skin = system_get_skinName();
			$moduleName = $cmsController->getCurrentModule();

			if ($mode !== null) {
				$path = CURRENT_WORKING_DIR . "/styles/skins/{$skin}/data/modules/{$moduleName}/ext/";
				if (file_exists($path)) {
					$result = '';
					$files = glob($path . "{$mode}.*.xsl");
					if (is_array($files)) {
						foreach ($files as $filename) {
							$template = str_replace($path, '', $filename);
							$result .= "<xsl:include href='udata://core/importExtFileXsl/{$template}'/>";
						}
					}
					return [
						'plain:result' => $this->getIncludesTemplate($result),
					];
				}
			}

			$pathEmpty = CURRENT_WORKING_DIR . "/styles/skins/{$skin}/empty.xsl";
			if (!file_exists($pathEmpty)) {
				throw new coreException("Empty template is required. Not found in '{$pathEmpty}'");
			}

			return [
				'plain:result' => file_get_contents($pathEmpty),
			];
		}

		/**
		 * Загрузка файла расширяющего дизайн админки
		 * @param bool $filename
		 * @return array
		 */
		public function importExtFileXsl($filename = false) {
			$cmsController = cmsController::getInstance();

			$skin = system_get_skinName();
			$moduleName = $cmsController->getCurrentModule();

			$path = CURRENT_WORKING_DIR . '/styles/skins/' . $skin . '/data/modules/' . $moduleName . '/ext/' . $filename;

			return [
				'plain:result' => file_get_contents($path),
			];
		}

		/**
		 * Возвращает шаблон по которому производится подключение внешних расширений.
		 * @param string $result список include файлов
		 * @throws coreException в случае если файл шаблона отсутствует
		 * @return string
		 */
		public function getIncludesTemplate($result) {
			$skin = system_get_skinName();
			$pathIncludesXsl = CURRENT_WORKING_DIR . "/styles/skins/{$skin}/ext.xsl";

			if (!file_exists($pathIncludesXsl)) {
				throw new coreException("Ext template is required. Not found in '{$pathIncludesXsl}'");
			}

			return str_replace('<!--includes-->', $result, file_get_contents($pathIncludesXsl));
		}

		/** @return mixed|string */
		public function header() {
			$controller = cmsController::getInstance();

			if ($controller->headerLabel) {
				$label = $controller->headerLabel;
			} else {
				$module = $controller->getCurrentModule();
				$method = $controller->getCurrentMethod();

				$label = "header-{$module}-{$method}";
			}

			return getLabel($label);
		}

		/**
		 * Включено ли кеширование.
		 * Используется в административной панели
		 * @return int
		 */
		public function cacheIsEnabled() {
			$isStaticCache = Service::StaticCache()->isEnabled();
			$isDynamicCache = Service::CacheFrontend()->isCacheEnabled();
			$isBrowserCache = Service::BrowserCache()->isEnabled();

			return (int) ($isStaticCache || $isDynamicCache || $isBrowserCache);
		}

		/** @return bool|string */
		public function contextManualUrl() {
			$cmsController = cmsController::getInstance();
			$moduleName = $cmsController->getCurrentModule();
			$methodName = $cmsController->getCurrentMethod();
			$langPrefix = ulangStream::getLangPrefix();

			$subMethod = false;

			$module = $cmsController->getModule($moduleName);
			if ($module instanceof def_module) {
				if (isset($module->data['object']['attribute:id'])) {
					$objectId = $module->data['object']['attribute:id'];
					$object = umiObjectsCollection::getInstance()->getObject($objectId);
					if ($object instanceof iUmiObject) {

						$objectTypeId = $object->getTypeId();
						$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
						$hierarchyTypeId = $objectType->getHierarchyTypeId();
						if ($hierarchyTypeId) {
							$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
							$subMethod = $hierarchyType->getExt();
						}
					}
				}

				if (!$subMethod && isset($module->data['page']['attribute:id'])) {
					$elementId = $module->data['page']['attribute:id'];
					$element = umiHierarchy::getInstance()->getElement($elementId);
					if ($element instanceof iUmiHierarchyElement) {
						$subMethod = $element->getMethod();
					}
				}

				if (!$subMethod && isset($module->data['page']['attribute:type-id'])) {
					$objectTypeId = $module->data['page']['attribute:type-id'];
					$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
					if ($objectType instanceof iUmiObjectType) {
						$hierarchyTypeId = $objectType->getHierarchyTypeId();
						$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
						if ($hierarchyType instanceof iUmiHierarchyType) {
							$subMethod = $hierarchyType->getExt();
						}
					}
				}

				if (!$subMethod && isset($module->data['object']['attribute:type-id'])) {
					$objectTypeId = $module->data['object']['attribute:type-id'];
					$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
					if ($objectType instanceof iUmiObjectType) {
						$hierarchyTypeId = $objectType->getHierarchyTypeId();
						$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
						if ($hierarchyType instanceof iUmiHierarchyType) {
							$subMethod = $hierarchyType->getExt();
						}
					}
				}
			}

			$path = false;
			if ($subMethod) {

				$tryPath = './man/' . $langPrefix . '/' . $moduleName . '/' . $methodName . '.' . $subMethod . '.html';
				if (is_file($tryPath)) {
					$path = $tryPath;
				}
			}

			if (!$path) {
				$tryPath = './man/' . $langPrefix . '/' . $moduleName . '/' . $methodName . '.html';
				if (is_file($tryPath)) {
					$path = $tryPath;
				} else {
					$path = './man/' . $langPrefix . '/dummy.html';
				}
			}

			if (!$path) {
				$path = '';
			}

			return $path;
		}

		/** @return array */
		public function getDomainsList() {
			$domains = Service::DomainCollection();
			$permissions = permissionsCollection::getInstance();

			$auth = Service::Auth();
			$userId = $auth->getUserId();
			$result = $domains->getList();

			foreach ($result as $id => $domain) {
				if ($permissions->isAllowedDomain($userId, $domain->getId()) == 0) {
					unset($result[$id]);
				}
			}

			return ['domains' => ['nodes:domain' => $result]];
		}

		/** @return array|bool */
		public function contextManual() {
			$path = $this->contextManualUrl();
			return $path ? ['plain:result' => file_get_contents($path)] : false;
		}

		/**
		 * Обертка для получение ссылок на добавление
		 * @param $module - имя модуля
		 * @param $id - id объекта ссылки которого надо получить
		 * @param $type - тип зависит от модуля
		 * @return bool|array
		 */
		public function getEditLinkWrapper($module, $id = 0, $type = false) {
			if ($id == 0 && $module == 'content') {
				return [
					'nodes:item' => [
						[
							'attribute:add' => '/admin/content/add/0/page',
						],
					],
				];
			}

			if (Service::Request()->isNotAdmin()) {
				return false;
			}

			/** @var catalog|users|news|faq|forum|emarket|blogs20 $selectedModule */
			$selectedModule = cmsController::getInstance()
				->getModule($module);

			if (!$selectedModule instanceof def_module) {
				return false;
			}

			if (!is_callable([$selectedModule, 'getEditLink'])) {
				return false;
			}

			$res = $selectedModule->getEditLink($id, $type);
			if (umiCount($res) == 2) {
				return [
					'nodes:item' => [
						[
							'attribute:add' => $res[0],
							'attribute:edit' => $res[1],
						],
					],
				];
			}

			if (umiCount($res) != 1) {
				return false;
			}

			if (contains($res[0], 'edit')) {
				return [
					'nodes:item' => [
						[
							'attribute:add' => $res[0],
						],
					],
				];
			}

			return false;
		}

		/** @deprecated */
		public function insertPopup($text = '', $src = '') {
			$res = $text;

			$path = (mb_substr($src, 0, 1) == '/') ? '.' . $src : $src;
			if (file_exists($path)) {
				$isz = getimagesize($path);
				if (is_array($isz)) {
					list($width, $height) = $isz;
					$res =
						"<a href=\"$src\" onclick=\"javascript: return gen_popup('$src', '$width', '$height');\" class=\"umi_popup\">" .
						$text . '</a>';
				}
			}
			return $res;
		}

		/** @deprecated */
		public function insertThumb($src1 = '', $src2 = '', $alt = '') {
			$path2 = (mb_substr($src2, 0, 1) == '/') ? '.' . $src2 : $src2;

			$thumb = "<img src=\"$src1\" border=\"0\" class=\"umi_thumb\" alt=\"{$alt}\" title=\"{$alt}\" />";

			if (file_exists($path2)) {
				$isz = getimagesize($path2);
				if (is_array($isz)) {
					list($width, $height) = $isz;
					$res = "<a href=\"$src2\" onclick=\"javascript: return gen_popup('$src2', '$width', '$height');\">" .
						$thumb . '</a>';
				}
			}
			return $res;
		}

	}
