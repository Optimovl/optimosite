<?php
	abstract class admin_robots extends baseModuleAdmin {
        public function onInit() {
            if(cmsController::getInstance()->getCurrentMode() != 'admin')
                return;

            $commonTabs = $this->getCommonTabs();

            if($commonTabs) {
                $commonTabs->add('robots');
            }
        }

        public function robots() {
            $this->setDataType("settings");
            $this->setActionType("modify");

            $domainId = getRequest('domain_id');

            if(!$domainId) {
                $domainId = cmsController::getInstance()->getCurrentDomain()->getId();
            }

            $domainId = intval($domainId);

            $file = sprintf('%s/robots/%d.robots.txt', CURRENT_WORKING_DIR, $domainId);

            $params = Array(
                "robots" => array(
                    "text:robots-content" => ''
                )
            );

            $mode = (string) getRequest('param0');

            if($mode == 'do') {
                $value = trim(getRequest('robots-content'));

                if($value != '') {
                    $value = preg_replace('/%disallow_umi_pages%\s+\n/', '%disallow_umi_pages%', $value);

                    if(!file_exists($file)) {
                        $dir = sprintf('%s/robots/', CURRENT_WORKING_DIR);

                        if(!is_dir($dir))
                            mkdir($dir);
                    }

                    $fp = fopen($file, 'w+');
                    if($fp) {
                        fputs($fp, $value);
                        fclose($fp);
                    }
                } else {
                    if(file_exists($file))
                        unlink($file);
                }

                $this->chooseRedirect();
            }

            $host = domainsCollection::getInstance()->getDomain($domainId)->getHost();

            $content = '';

            if(file_exists($file)) {
                $content = file_get_contents($file);

                $content = str_replace('%disallow_umi_pages%', '%disallow_umi_pages%'. PHP_EOL, $content);
            }

            $params['robots']['text:robots-content'] = $content;

            $data = $this->prepareData($params, 'settings');

            $this->setData($data);

            return $this->doData();
        }
	}
?>
