<?php

//error_reporting(E_ALL);
//ini_set('display_errors',1);

include_once CURRENT_WORKING_DIR . "/classes/modules/content/ext/lib/EMT.php";

abstract class common_typograf {

    public function typograf_processObject($val) {
        $regedit = regedit::getInstance();
        $field_types = $regedit->getVal('//settings/typograf_field_types');
        if (strlen($field_types) > 0) {
            $obj = null;

            if ($val instanceOf iUmiEventPoint) {
                if ($val->getMode() == 'after') {
                    $obj = $val->getRef('object');
                }
            } else if ($val instanceOf umiObject) {
                $obj = $val;
            }

            if (is_object($obj)) {
                $q = l_mysql_query("SELECT f.name, oc.text_val FROM cms3_object_content oc 
				INNER JOIN cms3_object_fields f ON oc.field_id = f.id
				WHERE oc.obj_id = {$obj->getId()} AND oc.text_val != '' AND field_id IN (SELECT id FROM cms3_object_fields WHERE field_type_id IN ({$field_types}))");
                $row = array();

                $content = '';
                $fields = array();
                $separator = '_' . uniqid() . '_';
                while ($row = mysql_fetch_row($q)) {
                    $fields[] = $row[0];
                    $content .= $row[1] . $separator;
                }

                if (strlen($content)) {
                    $content = substr($content, 0, -strlen($separator));
                }

                $content = $this->typograf_convertContent($content);

                $content_arr = explode($separator, $content);

                if (count($content_arr) == count($fields)) {
                    $old_mode = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
                    umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
                    foreach ($fields as $key => $field_name) {
                        $obj->setValue($field_name, $content_arr[$key]);
                    }
                    $obj->commit();
                    umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $old_mode;
                }
            }
        }
    }

    public function typograf_massConvert($limit, $offset = 0) {
        $regedit = regedit::getInstance();
        $result = array();
        $total = 0;

        if ($regedit->getVal('//settings/typograf_is_enabled')) {
            $field_types = $regedit->getVal('//settings/typograf_field_types');
            if (strlen($field_types) > 0) {
                $q = l_mysql_query("SELECT id, name FROM cms3_object_fields WHERE field_type_id IN ({$field_types})");
                $field_ids = array();
                $field_names = array();
                while ($row = mysql_fetch_row($q)) {
                    $field_ids[] = $row[0];
                    $field_names[$row[0]] = $row[1];
                }
                $oc = umiObjectsCollection::getInstance();
                $field_ids = implode(',', $field_ids);
                $objects = array();

                if (strlen($field_ids)) {
                    $q = l_mysql_query("SELECT SQL_CALC_FOUND_ROWS field_id, obj_id, text_val FROM cms3_object_content WHERE field_id IN ({$field_ids}) AND text_val != '' ORDER BY obj_id ASC LIMIT {$offset}, {$limit}");

                    $q_total = l_mysql_query('SELECT FOUND_ROWS()');
                    $row = mysql_fetch_row($q_total);
                    if (isset($row[0]))
                        $total = $row[0];

                    while ($row = mysql_fetch_row($q)) {
                        $content = $this->typograf_convertContent($row[2]);

                        if (strlen($content) && isset($field_names[$row[0]])) {
                            if (!isset($objects[$row[1]])) {
                                $obj = $oc->getObject($row[1]);
                                if (is_object($obj)) {
                                    $objects[$row[1]] = $obj;
                                } else {
                                    continue;
                                }
                            }

                            $obj = $objects[$row[1]];
                            if (is_object($obj)) {
                                $old_mode = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
                                umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;

                                $obj->setValue($field_names[$row[0]], $content);
                                $obj->commit();

                                umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $old_mode;
                            }
                        }
                    }
                }
            }
        }

        return array('total' => $total, 'processed' => $limit + $offset);
    }

    public function typograf_convertContent($content) {
        $regedit = regedit::getInstance();
        $prefix = 'typograf_';
        if ($regedit->getVal('//settings/' . $prefix . 'is_enabled') && strlen($content)) {
            static $typograf_obj;
            if ($typograf_obj == null) {
                $typograf_obj = new EMTypograph();

                $full_options_list = $typograf_obj->get_options_list();

                $setup_options = array(
                    'Etc.unicode_convert' => 'off',
                    'Text.no_repeat_words' => 'off',
                    'Text.breakline' => 'off',
                    'Text.auto_links' => 'off',
                    'Text.paragraphs' => 'off',
                    'Punctmark.auto_comma' => 'off',
                    'Nobr.dots_for_surname_abbr' => 'off',
                    'OptAlign.oa_obracket_coma' => 'off',
                    'OptAlign.all' => 'off',
                    'OptAlign.oa_oquote' => 'off',
                );

                foreach ($full_options_list['group'] as $group) {
                    if (isset($group['options'])) {
                        foreach ($group['options'] as $option_name) {
                            $config_name = $prefix . str_replace('.', '_', $option_name);

                            if (!isset($setup_options[$option_name])) {
                                if ($regedit->getVal('//settings/' . $config_name)) {
                                    $setup_options[$option_name] = 'on';
                                } else if ($regedit->getVal('//settings/' . $config_name) == 0 && $regedit->getVal('//settings/' . $config_name) != NULL) {
                                    $setup_options[$option_name] = 'off';
                                }
                            }
                        }
                    }
                }

                $typograf_obj->setup($setup_options);
            }

            if (is_object($typograf_obj)) {
                $_content = $content;
                $typograf_obj->set_text($_content);
                $_content = $typograf_obj->apply();
                if (strlen($_content)) {
                    $content = $_content;
                }
            }
        }

        return $content;
    }

    public function typograf_isEnabled() {
        $regedit = regedit::getInstance();
        return intval($regedit->getVal('//settings/typograf_is_enabled'));
    }
}
