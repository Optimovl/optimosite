<?php

include_once CURRENT_WORKING_DIR . "/classes/components/content/ext/lib/EMT.php";

class common_typograf extends def_module {

    public $modules;

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
                $content = '';
                $fields = array();
                $separator = '_' . uniqid() . '_';
                
                $connection = ConnectionPool::getInstance()->getConnection();
                $result = $connection->queryResult("SELECT f.name, oc.text_val FROM cms3_object_content oc 
                    INNER JOIN cms3_object_fields f ON oc.field_id = f.id
                    WHERE oc.obj_id = {$obj->getId()} AND oc.text_val != '' AND field_id IN (SELECT id FROM cms3_object_fields WHERE field_type_id IN ({$field_types}))");
                $result->setFetchType(IQueryResult::FETCH_ROW);
                if ($result->length()) {
                    foreach($result as $row) {
                        $fields[] = $row[0];
                        $content .= $row[1] . $separator;
                    }
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
                $field_ids = array();
                $field_names = array();

                $connection = ConnectionPool::getInstance()->getConnection();
                $result = $connection->queryResult("SELECT id, name FROM cms3_object_fields WHERE field_type_id IN ({$field_types})");
                $result->setFetchType(IQueryResult::FETCH_ASSOC);
                if ($result->length()) {
                    foreach ($result as $row) {
                        $field_ids[] = $row['id'];
                        $field_names[$row['id']] = $row['name'];
                    }
                }
                $oc = umiObjectsCollection::getInstance();
                $field_ids = implode(',', $field_ids);
                $objects = array();

                if (strlen($field_ids)) {
                    $result->freeResult();
                    $result = $connection->queryResult("SELECT SQL_CALC_FOUND_ROWS field_id, obj_id, text_val FROM cms3_object_content WHERE field_id IN ({$field_ids}) AND text_val != '' ORDER BY obj_id ASC LIMIT {$offset}, {$limit}");
                    $result->setFetchType(IQueryResult::FETCH_ROW);
                    
                    $result_total = $connection->queryResult('SELECT FOUND_ROWS()');
                    $total = intval($result_total->fetch()[0]);
                    
                    if ($result->length()) {
                        foreach ($result as $row) {
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
