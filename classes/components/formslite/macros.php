<?php
	/**
	 * Класс макросов, то есть методов, доступных в шаблоне
	 */
	class FormsliteMacros {
		/**
		 * @var news $module
		 */
		public $module;

		public function send() {
			$umiRegistry = regedit::getInstance();
	        $templater = cmsController::getInstance()->getCurrentTemplater();
			$oTypes      = umiObjectTypesCollection::getInstance();

			$system_form = getRequest('system_form');
			$iTime       = new umiDate( time() );

			$aRecipients['email'] = $umiRegistry->getVal('//modules/formslite/email_to');
	  		$aRecipients['name'] = $umiRegistry->getVal('//modules/formslite/name_to');

	  		if(empty($aRecipients['email'])) {
				$this->module->errorNewMessage(getLabel('error-no_recipients'));
			}

			//-------------------------------------------------------------------

			$aMessage           = $this->formatMessage($system_form, true);

			//--------------------------------------------------------------------
			// Make an e-mail
			$oMail = new umiMail();
			//--------------------------------------------------------------------
			$oMail->addRecipient( trim($aRecipients['email']), $aRecipients['name'] );

			$oMail->setFrom($aMessage['email_from'], $aMessage['name_from']);
			$oMail->setSubject($aMessage['subject']);
			$oMail->setContent($aMessage['template']);
			$oMail->commit();
			$oMail->send();
			//--------------------------------------------------------------------

			// Redirecting
			$sRedirect = getRequest('ref_onsuccess');
			if($sRedirect) $this->module->redirect($sRedirect);
			//--------------------------------------------------------------------

			// If we're still here
			if(isset($_SERVER['HTTP_REFERER'])) $this->module->redirect($_SERVER['HTTP_REFERER']);
			return '';
		}

		public function posted($template = false) {
			$templater = cmsController::getInstance()->getCurrentTemplater();
			$template = $template ? $template : (string) getRequest('template');
			$template = $template ? $template : (string) getRequest('param0');
	        //print_r($template);exit;
			$res = false;
			if ($template) {

				$sel = new selector('objects');
				$sel->types('object-type')->name('formslite', 'template');
				$sel->where('form')->equals($template);
				$sel->limit(0, 1);

				if ($sel->result) {
					$oTemplate = $sel->result[0];
					$res = $oTemplate->getValue('message');
				}

				if (!$res && !def_module::isXSLTResultMode()) {
					try {
						list($template) = $this->loadTemplates("./tpls/formslite/".$template, "posted");
						$res = $template;
					} catch (publicException $e) {}
				}
			}

			if ( !$res ) {
				$res = "%formslite_thank_you%";
			}

			return $templater->putLangs($res);
		}

		public function formatMessage($_FormId, $_bProcessAll = false) {
			$oObjects    = umiObjectsCollection::getInstance();
			$iTplTypeId  = umiObjectTypesCollection::getInstance()->getBaseType('formslite', 'template');
			$sMsgBody    = array('email_from' => '', 'template'=>'', 'subject'=>'', 'name_from' => '', 'message' => '');
			$oTplType    = umiObjectTypesCollection::getInstance()->getType($iTplTypeId);
			//------------------------------------------------------------------------------
			$oSelection  = new umiSelection;
			$oSelection->addObjectType( $iTplTypeId );
			$oSelection->addPropertyFilterEqual($oTplType->getFieldId('form'), $_FormId);
			$oSelection->setPropertyFilter();
			$aTemplates  = umiSelectionsParser::runSelection($oSelection);
			$oTemplate   = empty($aTemplates) ? false : $oObjects->getObject( $aTemplates[0] );
			//------------------------------------------------------------------------------

			if(!$oTemplate) {
				$sTmp = '';
				foreach($_REQUEST as $key=>$val) {
					$sTmp .= $key.": ".$val."<br />\n";
				}
				$sMsgBody['template'] = $sTmp;
			} else {
				$sMsgBody = array();
				$aFields  = umiObjectTypesCollection::getInstance()->getType( $oTemplate->getTypeId() )->getAllFields();
				foreach($aFields as $oField) {
					$aMarks     = array();
					$sFieldName = $oField->getName();

					$sTemplate  = str_replace(array("&#037;", "&#37;"), "%", $oTemplate->getValue($sFieldName));

					preg_match_all("/%[A-z0-9_]+%/", $sTemplate, $aMarks);
					foreach($aMarks[0] as $sMark)
						$sTemplate = str_replace($sMark, nl2br($_REQUEST[trim($sMark, '% ')]), $sTemplate);
					$sMsgBody[$sFieldName] = $sTemplate;
				}
			}
			return $sMsgBody;
		}
	}
?>
