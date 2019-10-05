<?php
/*
 * Module SiteMap.xml ver. 1.0
 *
 * © Copyright Art&web studio «Le net Net», lenetnet.ru, 2014.
*/

abstract class __sitemap_adm extends baseModuleAdmin {
    public function sitemap() {
        
		
		$collection = domainsCollection::getInstance();
		$domain = $collection->getDomainId($_SERVER["HTTP_HOST"]);
		
		$p = getRequest('p');
		$limit=100;
		
		$pages = new selector('pages');
		$pages->limit($p*$limit,$limit);
		$pages->where('domain')->equals($domain);
		$pages->order('name')->desc();
		
		$param1= getRequest("param1");
        $param0 = getRequest("param0");
		

				if(!empty($param0) && $param0 != "do"){
					try{
						$pages->types('hierarchy-type')->id($param0);
					}catch(selectorException $e){
						throw new publicAdminException(getLabel('error_no_type'));
					}
				}        

			$result = $pages->result();

			if($param1 == "do" || $param0 == "do") {
                $sm = getRequest("sm");
				
                $hierarchy = umiHierarchy::getInstance();
                
                foreach($result as $element){
                    if(isset($sm[$element->getId()])){
                        $element->setValue("robots_deny",'');
                    }else{
                        $element->setValue("robots_deny",'1');
                    }
                    
                    $element->commit();
                }
				//echo "Hi";exit;
				
                $this->chooseRedirect();
               
				
			}else{
				
			}
			$this->setDataType("list");
			$this->setActionType("view");
			//$result['total']=455;
			
			$data = $this->prepareData($result, "pages");

			$this->setDataRangeByPerPage($limit, $p);
			
			$this->setData($data,$pages->length);
			
			return $this->doData();
    }
}

?>