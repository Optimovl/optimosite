<?php

require_once CURRENT_WORKING_DIR . "/classes/components/content/ext/lib/EMT.php";

class admin_typograf_config extends def_module {

    public $module;

    public function __construct($module) {
        parent::__construct();
     
        $commonTabs = $module->getCommonTabs();
        if (is_object($commonTabs)) {
            $commonTabs->add('typograf_config');
        }
    }

    public function typograf_config() {

        require_once dirname(__FILE__) . "/install_helper.php";
        registrateModule('content', 'typograf', array(
            'config/typograf_config',
            'content/typograf_processElement',
            'content/typograf_processObject',
            'content/typograf_processProperty',
            'content/typograf_convertContent',
            'content/typograf_massConvert',
            'content/typograf_isEnabled'
        ));
        
        $regedit = regedit::getInstance();
        $params = array();
        $prefix = 'typograf_';
        $typograf = new EMTypograph();
        $full_options_list = $typograf->get_options_list();
        $except_options = array(
            'Etc.unicode_convert',
            'Text.no_repeat_words',
            'Text.breakline',
            'Text.auto_links',
            'Text.paragraphs',
            'Punctmark.auto_comma',
            'Nobr.dots_for_surname_abbr',
            'OptAlign.oa_obracket_coma',
            'OptAlign.all',
            'OptAlign.oa_oquote',
            'OptAlign.layout',
            'Quote.quotation',
            'Nobr.phone_builder',
            'Nobr.phone_builder_v2',
            'Nobr.ip_address',
            'Nobr.spaces_nobr_in_surname_abbr',
            'Nobr.nbsp_celcius',
            'Nobr.hyphen_nowrap',
            'Nobr.nowrap',
            'Symbol.no_inches',
            'Punctmark.dot_on_end',
        );
        $options = array();
        $options_list = array();

        foreach ($full_options_list['group'] as $group) {
            if (isset($group['options'])) {
                foreach ($group['options'] as $option) {
                    if (!in_array($option, $except_options)) {
                        $options_list[$prefix . str_replace('.', '_', $option)] = (isset($full_options_list['all'][$option]['disabled']) && $full_options_list['all'][$option]['disabled'] == 1) ? 0 : 1;
                        $options[$prefix . $group['name']][] = array(0 => 'boolean', 1 => $prefix . str_replace('.', '_', $option), 2 => $full_options_list['all'][$option]['description']);
                    }
                }
            }
        }

        if (!$regedit->getVal('//settings/' . $prefix . 'is_first_launch')) {
            $connection = ConnectionPool::getInstance()->getConnection();
            $result = $connection->queryResult("SELECT id, data_type FROM cms3_object_field_types WHERE data_type = 'wysiwyg'");
            $result->setFetchType(IQueryResult::FETCH_ASSOC);
            $field_types_ids = '';
            if ($result->length()) {
                foreach ($result as $row) {
                    $field_types_ids .= $row['id'] . ',';
                }
            }
            
            if (strlen($field_types_ids)) {
                $field_types_ids = substr($field_types_ids, 0, -1);
            }

            $regedit->setVar('//settings/' . $prefix . 'field_types', $field_types_ids);
            foreach ($options_list as $name => $value) {
                $regedit->setVar('//settings/' . $name, $value);
            }
            $regedit->setVar('//settings/typograf_Text_email', 0);
            $regedit->setVar('//settings/typograf_Etc_split_number_to_triads', 0);
            $regedit->setVar('//settings/typograf_OptAlign_oa_oquote', 0);
            $regedit->setVar('//settings/typograf_OptAlign_layout', 1);
            $regedit->setVar('//settings/typograf_Quote_quotation', 0);
            $regedit->setVar('//settings/typograf_Nobr_phone_builder', 0);
            $regedit->setVar('//settings/typograf_Nobr_phone_builder_v2', 0);
            $regedit->setVar('//settings/typograf_Nobr_ip_address', 0);
            $regedit->setVar('//settings/typograf_Nobr_spaces_nobr_in_surname_abbr', 0);
            $regedit->setVar('//settings/typograf_Nobr_nbsp_celcius', 0);
            $regedit->setVar('//settings/typograf_Nobr_hyphen_nowrap', 0);
            $regedit->setVar('//settings/typograf_Nobr_hyphen_nowrap', 1);
            $regedit->setVar('//settings/typograf_Nobr_nowrap', 0);
            $regedit->setVar('//settings/typograf_Symbol_no_inches', 0);
            $regedit->setVar('//settings/typograf_Punctmark_dot_on_end', 0);
            $regedit->setVar('//settings/' . $prefix . 'is_enabled', 0);
            $regedit->setVar('//settings/' . $prefix . 'is_first_launch', 1);
        }

        $settings = array(
            'typograf_globals' => array(
                array('boolean', $prefix . 'is_enabled', ''),
            ),
        );

        $settings = array_merge($settings, $options);

        $mode = (string) getRequest('param0');
        if ($mode == "do") {
            $params = array(
                "globals" => array(
                    'boolean:' . $prefix . 'is_enabled' => NULL,
                )
            );

            foreach ($options_list as $option_name => $not_used) {
                $params['globals'] = array_merge($params['globals'], array('boolean:' . $option_name => NULL));
            }

            $params = $this->module->expectParams($params);

            $regedit->setVar('//settings/' . $prefix . 'is_enabled', $params['globals']['boolean:' . $prefix . 'is_enabled']);

            foreach ($options_list as $option_name => $not_used) {
                $regedit->setVar('//settings/' . $option_name, $params['globals']['boolean:' . $option_name]);
            }

            $this->module->chooseRedirect();
        } else {
            foreach ($settings as $category => $items) {
                foreach ($items as $item) {
                    switch ($item[0]) {
                        case 'select':
                            $params[$category][$item[0] . ':' . $item[1]] = $item[2];
                            $params[$category][$item[0] . ':' . $item[1]]['value'] = $regedit->getVal('//settings/' . $item[1]);
                            break;
                        case 'multiple_select':
                            if (is_array($item[2]) && count($item[2])) {
                                foreach ($item[2] as &$value) {
                                    $regedit_value = array();
                                    if (strlen($regedit->getVal('//settings/' . $item[1]))) {
                                        $regedit_value = explode(',', $regedit->getVal('//settings/' . $item[1]));
                                    }

                                    if (in_array($value['attribute:id'], $regedit_value)) {
                                        $value['attribute:is_selected'] = 1;
                                    }
                                }
                            }
                            $params[$category][$item[0] . ':' . $item[1]] = array('nodes:item' => $item[2]);
                            break;
                        case 'boolean':
                            $params[$category][$item[0] . ':' . $item[1]] = array('attribute:title' => $item[2], 'node:text' => intval($regedit->getVal('//settings/' . $item[1])));
                            break;
                        default:
                            $params[$category][$item[0] . ':' . $item[1]] = $regedit->getVal('//settings/' . $item[1]);
                            break;
                    }
                }
            }

            $this->module->setHeaderLabel("header-config-typograf_config");
        }

        $this->module->setDataType("settings");
        $this->module->setActionType("modify");
        $data = $this->module->prepareData($params, "settings");
        $this->module->setData($data);
        $this->module->doData();
    }

}
