<?php
class formslite extends def_module {

    public function __construct() {
        parent::__construct();

        if(cmsController::getInstance()->getCurrentMode() == "admin") {
            $this->__loadLib("admin.php");
            $this->__implement("FormsliteAdmin");
            $commonTabs = $this->getCommonTabs();
	        if($commonTabs) {
	            $commonTabs->add("templates", array("template_edit", "template_add"));
	            $commonTabs->add('docs');
	        }
	        $this->loadAdminExtension();
	        $this->__loadLib("customAdmin.php");
			$this->__implement("FormsliteCustomAdmin", true);
        }else{
        	$this->__loadLib("macros.php");
			$this->__implement("FormsliteMacros");

			$this->loadSiteExtension();

			$this->__loadLib("customMacros.php");
			$this->__implement("FormsliteCustomMacros", true);
        }

        $this->loadCommonExtension();
		$this->loadTemplateCustoms();

        $this->__loadLib("customCommon.php");
		$this->__implement("FormsliteCustomCommon", true);
    }

    

    public function getObjectEditLink($objectId, $type = false) {
		$object = umiObjectsCollection::getInstance()->getObject($objectId);
		$oType   = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());
		return $this->pre_lang . "/admin/formslite/template_edit/" . $objectId . "/";
	}
};
?>
