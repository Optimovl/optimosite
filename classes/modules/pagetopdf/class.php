<?php
class pagetopdf extends def_module {

    public function __construct() {
        parent::__construct();

       	$this->loadCommonExtension();
        if(cmsController::getInstance()->getCurrentMode() == "admin") {
            $this->__loadLib("__admin.php");
            $this->__implement("__pagetopdf_adm");

	        $this->loadAdminExtension();
        }

        $this->loadSiteExtension();

    }

	public function getLinkPDF($pageId, $size='A4', $orientation='portrait') {
		if (!$pageId && $pageId !== 0) {
			throw new publicException(getLabel('error-page-does-not-exist'));
		}

		$hierarchy = umiHierarchy::getInstance();
		$element = $hierarchy->getElement($pageId);

		if(!$element instanceof iUmiHierarchyElement) {
			throw new publicException(getLabel('error-page-does-not-exist'));
		}
		$orientation_a=array('portrait', 'landscape');
		$orientation=in_array ($orientation, $orientation_a)?$orientation:'portrait';

		$size = preg_replace('/[^a-zA-Z0-9-]/', '', $size);
		
		$size=empty($size)?'A4':$size;
		
		$block_arr = array(
				'link' => '/pagetopdf/file/'.$pageId."/".$size."/".$orientation."/".$element->getAltName().".pdf",
		);

		return def_module::parseTemplate('', $block_arr, $pageId);
	}

	public function file($pageId, $size, $orientation, $file) {


		if (!$pageId && $pageId !== 0) {
			throw new publicException(getLabel('error-page-does-not-exist'));
		}

		$hierarchy = umiHierarchy::getInstance();
		$element = $hierarchy->getElement($pageId);

		if(!$element instanceof iUmiHierarchyElement) {
			throw new publicException(getLabel('error-page-does-not-exist'));
		}
		$orientation_a=array('portrait', 'landscape');
		$orientation=in_array ($orientation, $orientation_a)?$orientation:'portrait';

		$size = preg_replace('/[^a-zA-Z0-9-]/', '', $size);
		
		$size=empty($size)?'A4':$size;
		
		$uri = "uobject://".$element->getObjectId()."/?transform=sys-tpls/pagetopdf.xsl";
		$html=file_get_contents($uri);
		$html=str_replace('src="/', 'src="./', $html);
		$this->HTMLtoPDF($html, $size, $orientation, $file);
		exit;
	}
	
	static function HTMLtoPDF($html, $size, $orientation, $file){
		header('HTTP/1.1 200 OK');
		header("Cache-Control: public, must-revalidate");
		header("Pragma: no-cache");
		header("Content-type: application/force-download");
		header('Accept-Ranges: bytes');
		header("Content-Encoding: None");
		header("Vary:");
		header('Content-Transfer-Encoding: Binary');

		require_once("dompdf/dompdf_config.inc.php");

		$dompdf = new DOMPDF();
	
		$dompdf->set_paper($size, $orientation);
		$dompdf->load_html($html);
		$dompdf->render();
		$dompdf->stream($file);
		exit;
	}
};
?>
