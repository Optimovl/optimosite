<?php
class FormsliteAdmin {

		use baseModuleAdmin;
		/**
		 * @var news $module
		 */
		public $module;
		
	public function templates() {
			$this->setDataType("list");
			$this->setActionType("view");
			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$limit = getRequest('per_page_limit');
			$curr_page = (int) getRequest("p");
			$offset =  $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('formslite', 'template');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

	public function template_add() {
			$mode      = (string) getRequest('param0');
			$inputData = Array('type' => 'template');
			if($mode == 'do') {
				$data=getRequest('data');
				$oTemplate = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect('/admin/formslite/template_edit/'.$oTemplate->getId().'/');
			}
			$this->setDataType("form");
			$this->setActionType("create");
			$data = $this->prepareData($inputData, "object");
			$this->setData($data);
			return $this->doData();
		}
	public function template_edit() {
			$object = $this->expectObject("param0");
			$mode = (string) getRequest('param1');
			if($mode == "do") {
				$this->saveEditedObjectData($object);
				$this->chooseRedirect();
			}
			$this->setDataType("form");
			$this->setActionType("modify");
			$data = $this->prepareData($object, "object");
			$this->setData($data);
			return $this->doData();
		}
    public function template_delete() {
			$iObjectId = (int)getRequest('param0');
			umiObjectsCollection::getInstance()->delObject($iObjectId);
			$this->chooseRedirect('/admin/formslite/templates/');
		}
	public function del() {
			$objects = getRequest('element');
			if(!is_array($objects) && $objects) $objects = array($objects);
			if(is_array($objects) && !empty($objects)) {
				$collection = umiObjectsCollection::getInstance();
				foreach($objects as $objectId) {
					$collection->delObject($objectId);
				}
			}
		}

	public function docs(){
		$this->setDataType("form");
		$this->setActionType("modify");
        if($this->module->ifNotXmlMode()) {
		    return $this->doData();
		}
	}

	public function UniqueFormName($form){
		$sel = new selector('objects');
		$sel->types('object-type')->name('formslite', 'template');
		$sel->where('form')->equals($form);

		return $sel->length>0?true:false;
	}

	public function config() {
			$umiRegistry = regedit::getInstance();
			$objectTypesColl = umiObjectTypesCollection::getInstance();

			$params = array(
				'config' => array(
					'string:email_to' => null,
					'string:name_to' => null
				)
			);

			$mode = getRequest('param0');

			if ($mode == 'do') {
				$params = $this->expectParams($params);
				$umiRegistry->setVar('//modules/formslite/email_to',   $params['config']['string:email_to']);
				$umiRegistry->setVar('//modules/formslite/name_to',   $params['config']['string:name_to']);
				$this->chooseRedirect();
			}

			$params['config']['string:email_to']['value'] = $umiRegistry->getVal('//modules/formslite/email_to');
            $params['config']['string:name_to']['value'] = $umiRegistry->getVal('//modules/formslite/name_to');

			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');
			$this->setData($data);
			return $this->doData();
		}


	public function getDatasetConfiguration($param = '') {
			$objectTypes = umiObjectTypesCollection::getInstance();
			switch($param) {
				case 'templates':
					$loadMethod = 'templates';
					$delMethod  = 'del';
					$typeId		= $objectTypes->getBaseType('formslite', 'template');
					$defaults	= '';
					break;
			}
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'formslite', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 				  'module'=>'formslite', '#__name'=>$delMethod, 'aliases' => 'tree_delete_element,delete,del')),
					'types' => array(
						array('common' => 'true', 'id' => $typeId)
					),
					'stoplist' => array('form_id', 'email_from', 'template', 'subject'),
					'default' => $defaults
			);
		}

}
?>
