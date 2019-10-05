<?php
class __pagetopdf_adm extends baseModuleAdmin {

	public function docs(){
		$this->setDataType("form");
		$this->setActionType("modify");
        if(getRequest('param0') == "do") {
				if (file_exists(CURRENT_WORKING_DIR.'/.htaccess')) {
    				$content = file_get_contents(CURRENT_WORKING_DIR.'/.htaccess');
    				$install=true;
    				foreach(explode("\n", $content) as $ht_line) {
						$line=trim($ht_line);
						if (preg_match('|(.*?) pagetopdf\.php|i', $line)) {
                           $line='';
                           $install=false;
						}else{
							$ht_array[] = $line;
						}
					}
    				if($install==true){
    					unset($ht_array);
						foreach(explode("\n", $content) as $ht_line2) {
							$line=trim($ht_line2);
							$ht_array[] = $line;
							if (preg_match('|(.*?) index.php\?jsonMode|i', $line)) {
	                           $ht_array[]="RewriteRule ^\/?pagetopdf?\/?file?\/?(.*)\.pdf$ pagetopdf.php?path=$1.pdf&%{QUERY_STRING} [L]";
							}
						}
					}
					$content = implode("\r\n", $ht_array)."\r\n";
					file_put_contents(CURRENT_WORKING_DIR.'/.htaccess', $content);
				}
				$this->redirect(getServer('HTTP_REFERER'));
		}
		if($this->ifNotXmlMode()) {
		    return $this->doData();
		}
	    }

}
?>
